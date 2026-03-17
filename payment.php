<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php
    session_start();
    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }
    include 'db.php';
    include 'csrf_util.php';
    $conn->select_db("bed_reservation");

    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];

    // Get request ID from URL
    if (!isset($_GET['request_id'])) {
        header("Location: index.php");
        exit();
    }

    $request_id = intval($_GET['request_id']);

    // Check if user owns this request or is staff - use prepared statement
    if ($user_role == 'customer') {
        $stmt = $conn->prepare("SELECT rr.*, b.name as bed_name, u.username, u.full_name FROM reservation_requests rr JOIN beds b ON rr.bed_id = b.id JOIN users u ON rr.customer_id = u.id WHERE rr.id = ? AND rr.status = 'approved_by_manager' AND rr.customer_id = ?");
        $stmt->bind_param("ii", $request_id, $user_id);
    } else {
        $stmt = $conn->prepare("SELECT rr.*, b.name as bed_name, u.username, u.full_name FROM reservation_requests rr JOIN beds b ON rr.bed_id = b.id JOIN users u ON rr.customer_id = u.id WHERE rr.id = ? AND rr.status = 'approved_by_manager'");
        $stmt->bind_param("i", $request_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $stmt->close();
        header("Location: index.php");
        exit();
    }

    $request = $result->fetch_assoc();
    $stmt->close();

    // Handle payment submission
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
            $error = "Invalid CSRF token. Please try again.";
        } else {
            $payment_method = $_POST['payment_method'];
            $payment_reference = trim(htmlspecialchars($_POST['payment_reference']));
            $payment_amount = floatval($_POST['payment_amount']);

            // Handle payment screenshot upload
            $payment_screenshot = null;
            if (isset($_FILES['payment_screenshot']) && $_FILES['payment_screenshot']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['payment_screenshot']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                if (in_array($ext, $allowed)) {
                    $new_filename = 'payment_' . $request_id . '_' . time() . '.' . $ext;
                    $upload_path = 'uploads/' . $new_filename;
                    
                    if (move_uploaded_file($_FILES['payment_screenshot']['tmp_name'], $upload_path)) {
                        $payment_screenshot = $upload_path;
                    } else {
                        $error = "Error uploading payment screenshot.";
                    }
                } else {
                    $error = "Invalid file type. Please upload JPG, PNG, or GIF.";
                }
            }

            if (!isset($error)) {
                // Calculate days and amount
                $check_in = new DateTime($request['check_in']);
                $check_out = new DateTime($request['check_out']);
                $days = $check_out->diff($check_in)->days;
                $expected_amount = $days * 500; // 500 ETB per day

                // Validate amount
                if ($payment_amount < $expected_amount) {
                    $error = "Payment amount (ETB " . number_format($payment_amount, 2) . ") is less than required amount (ETB " . number_format($expected_amount, 2) . ")";
                } else {
                    // In a real system, you'd verify payment here
                    // For demo, we'll mark as completed - use prepared statement
                    if ($payment_screenshot) {
                        $stmt = $conn->prepare("UPDATE reservation_requests SET status = 'confirmed', payment_method = ?, payment_reference = ?, payment_amount = ?, payment_status = 'completed', picture_path = ?, updated_at = NOW() WHERE id = ?");
                        $stmt_check = ($stmt !== false);
                        if ($stmt_check) {
                            $stmt->bind_param("ssdsi", $payment_method, $payment_reference, $payment_amount, $payment_screenshot, $request_id);
                        }
                    } else {
                        $stmt = $conn->prepare("UPDATE reservation_requests SET status = 'confirmed', payment_method = ?, payment_reference = ?, payment_amount = ?, payment_status = 'completed', updated_at = NOW() WHERE id = ?");
                        $stmt_check = ($stmt !== false);
                        if ($stmt_check) {
                            $stmt->bind_param("ssdi", $payment_method, $payment_reference, $payment_amount, $request_id);
                        }
                    }
                    
                    if ($stmt === false) {
                        $error = "Database error: payment_amount column not found. Please run <a href='migrate_payment_amount.php'>migration script</a> to update the database.";
                    } else {
                        if ($stmt->execute()) {
                            // Update bed status - use prepared statement
                            $bed_id = $request['bed_id'];
                            $stmt->close();
                            
                            $stmt = $conn->prepare("UPDATE beds SET status = 'occupied' WHERE id = ?");
                            $stmt->bind_param("i", $bed_id);
                            $stmt->execute();
                            $stmt->close();

                            // Create actual reservation - use prepared statement
                            $stmt = $conn->prepare("INSERT INTO reservations (bed_id, guest_name, check_in, check_out) VALUES (?, ?, ?, ?)");
                            $stmt->bind_param("isss", $bed_id, $request['username'], $request['check_in'], $request['check_out']);
                            $stmt->execute();
                            $stmt->close();

                            $success = "Payment of ETB " . number_format($payment_amount, 2) . " processed successfully! Your reservation is now confirmed.";
                            $show_receipt_download = true;
                            $receipt_request_id = $request_id;
                        } else {
                            $error = "Error processing payment: " . $stmt->error;
                            $stmt->close();
                        }
                    }
                }
            }
        }
    }

    // Calculate reservation details
    $check_in = new DateTime($request['check_in']);
    $check_out = new DateTime($request['check_out']);
    $days = $check_out->diff($check_in)->days;
    $price_per_day = 500; // ETB per day
    $total_amount = $days * $price_per_day;

    $conn->close();
    ?>

    <h1>Complete Payment</h1>

    <?php if (isset($success)): ?>
        <div class="alert success"><?php echo $success; ?></div>
        <?php if (isset($show_receipt_download) && $show_receipt_download): ?>
            <div style="text-align:center;margin:20px 0;">
                <a href="download_receipt.php?request_id=<?php echo $receipt_request_id; ?>" class="action-button" style="background:#28a745;padding:15px 30px;font-size:16px;">📄 Download Receipt</a>
            </div>
        <?php endif; ?>
        <a href="index.php" class="action-button">Return to Dashboard</a>
    <?php elseif (isset($error)): ?>
        <div class="alert error"><?php echo $error; ?></div>
    <?php else: ?>

    <div class="payment-summary">
        <h2>Reservation Summary</h2>
        <div class="summary-details">
            <div class="summary-item">
                <strong>Customer:</strong> <?php echo $request['full_name'] ?: $request['username']; ?>
            </div>
            <div class="summary-item">
                <strong>Bed:</strong> <?php echo $request['bed_name']; ?>
            </div>
            <div class="summary-item">
                <strong>Check-in:</strong> <?php echo date('M j, Y', strtotime($request['check_in'])); ?>
            </div>
            <div class="summary-item">
                <strong>Check-out:</strong> <?php echo date('M j, Y', strtotime($request['check_out'])); ?>
            </div>
            <div class="summary-item">
                <strong>Number of Days:</strong> <?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?>
            </div>
            <div class="summary-item">
                <strong>Price per Day:</strong> ETB <?php echo number_format($price_per_day, 2); ?>
            </div>
            <div class="summary-item" style="font-size:18px;color:#28a745;">
                <strong>Total Amount:</strong> ETB <?php echo number_format($total_amount, 2); ?>
            </div>
        </div>
    </div>

    <div class="payment-methods">
        <h2>Select Payment Method</h2>
        
        <div style="background:#e7f3ff;padding:15px;border-radius:5px;margin-bottom:20px;">
            <strong>💡 How to make payment:</strong>
            <ol style="margin:10px 0 0 0;">
                <li>Choose your preferred payment method (CBE or TeleBirr)</li>
                <li>Make the transfer/payment to the account shown</li>
                <li>Enter your transaction reference number in the field below</li>
                <li>Click the payment button to complete your reservation</li>
            </ol>
        </div>

        <div class="payment-options">
            <div class="payment-option">
                <h3>Commercial Bank of Ethiopia (CBE)</h3>
                <p><strong>Account Number:</strong> 1000263735707</p>
                <p><strong>Account Name:</strong> Hotel Reservation System</p>
                <p><strong>Amount to Pay:</strong> <span style="color:#28a745;font-size:18px;font-weight:bold;">ETB <?php echo number_format($total_amount, 2); ?></span></p>
                <form method="post" action="" enctype="multipart/form-data">
                    <input type="hidden" name="payment_method" value="cbe">
                    <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
                    <input type="hidden" name="payment_amount" value="<?php echo $total_amount; ?>">
                    <?php echo csrf_field(); ?>
                    <label for="cbe_reference">Transaction Reference:</label>
                    <input type="text" id="cbe_reference" name="payment_reference" required placeholder="Enter CBE transaction ID">
                    
                    <label for="cbe_screenshot" style="margin-top:15px;display:block;">Upload Payment Screenshot: <span style="color:red;">*</span></label>
                    <input type="file" id="cbe_screenshot" name="payment_screenshot" accept="image/*" required style="margin:10px 0;padding:10px;border:1px solid #ddd;border-radius:4px;width:100%;">
                    <small style="color:#666;">Upload a screenshot of your CBE payment confirmation</small>
                    
                    <button type="submit" class="payment-btn cbe-btn" style="margin-top:15px;">Pay ETB <?php echo number_format($total_amount, 2); ?> with CBE</button>
                </form>
            </div>

            <div class="payment-option">
                <h3>TeleBirr</h3>
                <p><strong>Phone Number:</strong> +251917516532</p>
                <p><strong>Merchant Name:</strong> Hotel Reservation</p>
                <p><strong>Amount to Pay:</strong> <span style="color:#28a745;font-size:18px;font-weight:bold;">ETB <?php echo number_format($total_amount, 2); ?></span></p>
                <form method="post" action="" enctype="multipart/form-data">
                    <input type="hidden" name="payment_method" value="telebirr">
                    <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
                    <input type="hidden" name="payment_amount" value="<?php echo $total_amount; ?>">
                    <?php echo csrf_field(); ?>
                    <label for="telebirr_reference">Transaction Reference:</label>
                    <input type="text" id="telebirr_reference" name="payment_reference" required placeholder="Enter TeleBirr transaction ID">
                    
                    <label for="telebirr_screenshot" style="margin-top:15px;display:block;">Upload Payment Screenshot: <span style="color:red;">*</span></label>
                    <input type="file" id="telebirr_screenshot" name="payment_screenshot" accept="image/*" required style="margin:10px 0;padding:10px;border:1px solid #ddd;border-radius:4px;width:100%;">
                    <small style="color:#666;">Upload a screenshot of your TeleBirr payment confirmation</small>
                    
                    <button type="submit" class="payment-btn telebirr-btn" style="margin-top:15px;">Pay ETB <?php echo number_format($total_amount, 2); ?> with TeleBirr</button>
                </form>
            </div>
        </div>
    </div>

    <?php endif; ?>

    <a href="index.php">Back to Dashboard</a>
</body>
</html>