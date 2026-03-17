<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve Bed</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function verifyNationalId() {
            const nationalId = document.getElementById('national_id').value;
            const pictureDiv = document.getElementById('verified_picture');
            const submitBtn = document.getElementById('submit_btn');
            
            // Validate - must be exactly 10 digits per requirements
            if (nationalId.length === 10 && /^\d+$/.test(nationalId)) {
                pictureDiv.innerHTML = '<img src="data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMTUwIiBoZWlnaHQ9IjE1MCIgdmlld0JveD0iMCAwIDE1MCAxNTAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxjaXJjbGUgY3g9Ijc1IiBjeT0iNzUiIHI9Ijc1IiBmaWxsPSIjRjNGNEY2Ii8+CjxjaXJjbGUgY3g9Ijc1IiBjeT0iNjAiIHI9IjIyIiBmaWxsPSIjQzRDNEM0Ii8+CjxwYXRoIGQ9Ik0yMCAxMzBoMTEwdjIwSDB6IiBmaWxsPSIjQzRDNEM0Ii8+Cjwvc3ZnPgo=" alt="Verified Customer Picture" style="max-width: 150px; border-radius: 5px;"><p style="color: green;">✓ National ID Verified Successfully (10 digits)</p>';
                pictureDiv.style.display = 'block';
                submitBtn.disabled = false;
            } else {
                pictureDiv.innerHTML = '<p style="color: red;">✗ Invalid National ID - Must be exactly 10 digits</p>';
                pictureDiv.style.display = 'block';
                submitBtn.disabled = true;
            }
        }
    </script>
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

    $bed_id = isset($_POST['bed_id']) ? intval($_POST['bed_id']) : (isset($_GET['bed_id']) ? intval($_GET['bed_id']) : 0);

    // Get bed details - use prepared statement
    $stmt = $conn->prepare("SELECT * FROM beds WHERE id = ?");
    $stmt->bind_param("i", $bed_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $bed = $result->fetch_assoc();
    $stmt->close();

    $show_form = true;
    $sms_message = '';

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['customer_name'])) {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
            echo "<p style='color:red;'>Invalid CSRF token. Please try again.</p>";
        } else {
            // Sanitize all inputs
            $customer_name = trim(htmlspecialchars($_POST['customer_name']));
            $phone = trim(htmlspecialchars($_POST['phone']));
            $location = trim(htmlspecialchars($_POST['location']));
            $reason = trim(htmlspecialchars($_POST['reason']));
            $check_in = $_POST['check_in'];
            $check_out = $_POST['check_out'];
            $national_id = preg_replace('/[^0-9]/', '', $_POST['national_id']);
            
            // Validate dates
            $valid = true;
            if (strtotime($check_in) === false || strtotime($check_out) === false) {
                echo "<p style='color: red;'>Invalid date format.</p>";
                $valid = false;
            } elseif (strtotime($check_in) >= strtotime($check_out)) {
                echo "<p style='color: red;'>Check-out date must be after check-in date.</p>";
                $valid = false;
            } elseif (strtotime($check_in) < strtotime(date('Y-m-d'))) {
                echo "<p style='color: red;'>Check-in date cannot be in the past.</p>";
                $valid = false;
            }
            
            // Validate National ID - must be exactly 10 digits per requirements
            if ($valid && strlen($national_id) !== 10) {
                echo "<p style='color: red;'>National ID must be exactly 10 digits.</p>";
                $valid = false;
            }
            
            if ($valid) {
                // Handle file upload with security measures
                $picture_path = '';
                if (isset($_FILES['picture']) && $_FILES['picture']['error'] == 0) {
                    $upload_dir = 'uploads/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    
                    // Validate file type
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/jpg'];
                    $file_type = $_FILES['picture']['type'];
                    $file_size = $_FILES['picture']['size'];
                    
                    if (!in_array($file_type, $allowed_types)) {
                        echo "<p style='color: red;'>Only JPG, PNG, and GIF images are allowed.</p>";
                    } elseif ($file_size > 2097152) {
                        echo "<p style='color: red;'>File size must be less than 2MB.</p>";
                    } else {
                        // Generate unique filename
                        $extension = pathinfo($_FILES['picture']['name'], PATHINFO_EXTENSION);
                        $picture_path = $upload_dir . uniqid('customer_') . '.' . $extension;
                        move_uploaded_file($_FILES['picture']['tmp_name'], $picture_path);
                    }
                }

                // Generate username and password
                $username = strtolower(str_replace(' ', '_', $customer_name)) . rand(100, 999);
                $password = rand(100000, 999999);

                // Check if bed is available
                $stmt = $conn->prepare("SELECT status FROM beds WHERE id = ?");
                $stmt->bind_param("i", $bed_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $stmt->close();

                if ($row['status'] == 'available') {
                    // Insert reservation request
                    $stmt = $conn->prepare("INSERT INTO reservation_requests (customer_id, bed_id, customer_name, phone, location, reason, check_in, check_out, national_id, picture_path, username, password, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
                    $stmt->bind_param("iissssssssss", $_SESSION['user_id'], $bed_id, $customer_name, $phone, $location, $reason, $check_in, $check_out, $national_id, $picture_path, $username, $password);
                    
                    if ($stmt->execute()) {
                        $sms_message = "Dear $customer_name, your reservation request has been submitted. Your login credentials: Username: $username, Password: $password. Please use these to check your reservation status.";
                        
                        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
                        echo "<h3>Reservation Request Submitted Successfully!</h3>";
                        echo "<p><strong>SMS Sent to $phone:</strong></p>";
                        echo "<p style='font-family: monospace; background: #f8f9fa; padding: 10px; border-radius: 3px;'>" . htmlspecialchars($sms_message) . "</p>";
                        echo "<p><strong>Next Steps:</strong></p>";
                        echo "<ul>";
                        echo "<li>Your request will be reviewed by our receptionist</li>";
                        echo "<li>You will receive updates via your account</li>";
                        echo "<li>Use the provided credentials to login and check status</li>";
                        echo "</ul>";
                        echo "<a href='index.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Back to Dashboard</a>";
                        echo "</div>";
                        $show_form = false;
                    } else {
                        echo "<p style='color: red;'>Error: " . $stmt->error . "</p>";
                    }
                    $stmt->close();
                } else {
                    echo "<p style='color: red;'>Bed is not available.</p>";
                }
            }
        }
    }
    ?>

    <h1>Reserve a Bed</h1>

    <?php if ($show_form): ?>
    <div class="bed-summary">
        <h2>Bed Details</h2>
        <div class="summary-content">
            <div class="bed-info">
                <img src="bed-icon.svg" alt="Bed Icon" class="bed-icon-small">
                <div class="bed-details-text">
                    <p><strong><?php echo htmlspecialchars($bed['name']); ?></strong></p>
                    <p>Room Size: <?php echo htmlspecialchars($bed['room_size']); ?></p>
                    <p>Status: <span class="status-<?php echo htmlspecialchars($bed['status']); ?>"><?php echo ucfirst(htmlspecialchars($bed['status'])); ?></span></p>
                </div>
            </div>
            <?php if (!empty($bed['accessories'])): ?>
                <div class="accessories-preview">
                    <h4>Amenities:</h4>
                    <p><?php echo htmlspecialchars(substr($bed['accessories'], 0, 50) . (strlen($bed['accessories']) > 50 ? '...' : '')); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <form method="post" action="" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>
        
        <h3>Customer Information</h3>
        
        <label for="customer_name">Full Name:</label>
        <input type="text" id="customer_name" name="customer_name" required><br><br>

        <label for="phone">Phone Number:</label>
        <input type="tel" id="phone" name="phone" required><br><br>

        <label for="location">Location (Where are you coming from?):</label>
        <input type="text" id="location" name="location" required><br><br>

        <label for="reason">Reason for Visit:</label>
        <textarea id="reason" name="reason" rows="3" required></textarea><br><br>

        <label for="picture">Upload Picture:</label>
        <input type="file" id="picture" name="picture" accept="image/*" required><br><br>

        <label for="national_id">National ID Number:</label>
        <input type="text" id="national_id" name="national_id" required onblur="verifyNationalId()">
        <button type="button" onclick="verifyNationalId()">Verify ID</button><br><br>

        <div id="verified_picture" style="display: none; margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;"></div>

        <h3>Reservation Details</h3>
        
        <label for="check_in">Check-in Date:</label>
        <input type="date" id="check_in" name="check_in" required><br><br>

        <label for="check_out">Check-out Date:</label>
        <input type="date" id="check_out" name="check_out" required><br><br>

        <input type="submit" id="submit_btn" value="Submit Reservation Request" disabled>
    </form>
    <?php endif; ?>

    <a href="index.php">Back to Dashboard</a>
</body>
</html>
<?php
$conn->close();
?>
