<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Verification Details</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .detail-section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .detail-grid {
            display: grid;
            grid-template-columns: 200px 1fr;
            gap: 15px;
            margin: 10px 0;
        }
        .detail-label {
            font-weight: bold;
            color: #666;
        }
        .customer-photo {
            max-width: 300px;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin: 10px 0;
        }
        .flag-form {
            background: #fff3cd;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .history-table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .history-table th, .history-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .history-table th {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'police') {
        header("Location: login.php");
        exit();
    }
    include 'db.php';
    include 'csrf_util.php';
    $conn->select_db("bed_reservation");

    $reservation_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // Handle flag submission
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['flag_reason'])) {
        if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
            $error = "Invalid CSRF token.";
        } else {
            $flag_reason = trim(htmlspecialchars($_POST['flag_reason']));
            $flag_description = trim(htmlspecialchars($_POST['flag_description']));
            
            // Update reservation status to flagged
            $stmt = $conn->prepare("UPDATE reservation_requests SET status = 'flagged' WHERE id = ?");
            $stmt->bind_param("i", $reservation_id);
            $stmt->execute();
            $stmt->close();
            
            // Insert flag record
            $officer_id = $_SESSION['user_id'];
            $stmt = $conn->prepare("INSERT INTO security_flags (reservation_id, officer_id, reason, description) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiss", $reservation_id, $officer_id, $flag_reason, $flag_description);
            $stmt->execute();
            $stmt->close();
            
            // Log the action
            $action = "Added security flag";
            $details = "Flagged reservation #$reservation_id - Reason: $flag_reason";
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt = $conn->prepare("INSERT INTO police_audit_log (officer_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $officer_id, $action, $details, $ip);
            $stmt->execute();
            $stmt->close();
            
            $success = "Security flag added successfully. Manager and Admin have been notified.";
        }
    }

    // Get reservation details
    $stmt = $conn->prepare("SELECT rr.*, b.name as bed_name, b.room_size, u.username 
                           FROM reservation_requests rr 
                           LEFT JOIN beds b ON rr.bed_id = b.id 
                           LEFT JOIN users u ON rr.customer_id = u.id 
                           WHERE rr.id = ?");
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $reservation = $result->fetch_assoc();
    $stmt->close();

    if (!$reservation) {
        echo "<p style='color:red;'>Reservation not found.</p>";
        echo "<a href='police_officer.php'>← Back to Dashboard</a>";
        exit();
    }

    // Get customer's reservation history
    $stmt = $conn->prepare("SELECT rr.*, b.name as bed_name 
                           FROM reservation_requests rr 
                           LEFT JOIN beds b ON rr.bed_id = b.id 
                           WHERE rr.customer_id = ? 
                           ORDER BY rr.created_at DESC");
    $stmt->bind_param("i", $reservation['customer_id']);
    $stmt->execute();
    $history = $stmt->get_result();
    $stmt->close();

    // Get existing flags
    $stmt = $conn->prepare("SELECT sf.*, u.username as officer_name 
                           FROM security_flags sf 
                           LEFT JOIN users u ON sf.officer_id = u.id 
                           WHERE sf.reservation_id = ? 
                           ORDER BY sf.created_at DESC");
    $stmt->bind_param("i", $reservation_id);
    $stmt->execute();
    $flags = $stmt->get_result();
    $stmt->close();

    // Log this access
    $officer_id = $_SESSION['user_id'];
    $action = "Viewed customer details";
    $details = "Accessed verification record for reservation #$reservation_id (Customer: {$reservation['customer_name']})";
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO police_audit_log (officer_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $officer_id, $action, $details, $ip);
    $stmt->execute();
    $stmt->close();
    ?>

    <div class="header">
        <h1>🔍 Customer Verification Details</h1>
        <p>Reservation ID: #<?php echo $reservation_id; ?></p>
    </div>

    <?php if (isset($success)): ?>
        <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="detail-section">
        <h2>Customer Information</h2>
        <div class="detail-grid">
            <div class="detail-label">Full Name:</div>
            <div><?php echo htmlspecialchars($reservation['customer_name']); ?></div>
            
            <div class="detail-label">Owner Name:</div>
            <div><?php echo htmlspecialchars($reservation['owner_name']); ?></div>
            
            <div class="detail-label">National ID:</div>
            <div><?php echo htmlspecialchars($reservation['national_id']); ?></div>
            
            <div class="detail-label">Phone Number:</div>
            <div><?php echo htmlspecialchars($reservation['phone']); ?></div>
            
            <div class="detail-label">Location:</div>
            <div><?php echo htmlspecialchars($reservation['location']); ?></div>
            
            <div class="detail-label">Reason for Visit:</div>
            <div><?php echo htmlspecialchars($reservation['reason']); ?></div>
            
            <div class="detail-label">Username:</div>
            <div><?php echo htmlspecialchars($reservation['username'] ?? 'N/A'); ?></div>
        </div>

        <?php if (!empty($reservation['picture_path']) && file_exists($reservation['picture_path'])): ?>
            <h3>Customer Photo</h3>
            <img src="<?php echo htmlspecialchars($reservation['picture_path']); ?>" 
                 alt="Customer Photo" class="customer-photo">
        <?php endif; ?>
    </div>

    <div class="detail-section">
        <h2>Reservation Details</h2>
        <div class="detail-grid">
            <div class="detail-label">Bed:</div>
            <div><?php echo htmlspecialchars($reservation['bed_name']); ?> (<?php echo htmlspecialchars($reservation['room_size']); ?>)</div>
            
            <div class="detail-label">Check-in Date:</div>
            <div><?php echo date('F j, Y', strtotime($reservation['check_in'])); ?></div>
            
            <div class="detail-label">Check-out Date:</div>
            <div><?php echo date('F j, Y', strtotime($reservation['check_out'])); ?></div>
            
            <div class="detail-label">Duration:</div>
            <div><?php 
                $days = (strtotime($reservation['check_out']) - strtotime($reservation['check_in'])) / 86400;
                echo $days . ' day' . ($days != 1 ? 's' : '');
            ?></div>
            
            <div class="detail-label">Status:</div>
            <div><strong><?php echo ucwords(str_replace('_', ' ', $reservation['status'])); ?></strong></div>
            
            <div class="detail-label">Submitted:</div>
            <div><?php echo date('F j, Y g:i A', strtotime($reservation['created_at'])); ?></div>
        </div>
    </div>

    <?php if ($flags->num_rows > 0): ?>
        <div class="detail-section" style="background: #fff3cd;">
            <h2>🚩 Security Flags</h2>
            <?php while($flag = $flags->fetch_assoc()): ?>
                <div style="border: 1px solid #ffc107; padding: 15px; margin: 10px 0; border-radius: 5px; background: white;">
                    <p><strong>Flagged by:</strong> <?php echo htmlspecialchars($flag['officer_name']); ?></p>
                    <p><strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($flag['created_at'])); ?></p>
                    <p><strong>Reason:</strong> <?php echo htmlspecialchars($flag['reason']); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($flag['description']); ?></p>
                </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>

    <div class="detail-section">
        <h2>Reservation History</h2>
        <table class="history-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Bed</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Status</th>
                    <th>Date Submitted</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($history->num_rows > 0): ?>
                    <?php while($row = $history->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['bed_name']); ?></td>
                            <td><?php echo date('M j, Y', strtotime($row['check_in'])); ?></td>
                            <td><?php echo date('M j, Y', strtotime($row['check_out'])); ?></td>
                            <td><?php echo ucwords(str_replace('_', ' ', $row['status'])); ?></td>
                            <td><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" style="text-align: center;">No reservation history</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($reservation['status'] != 'flagged'): ?>
        <div class="flag-form">
            <h2>🚩 Add Security Flag</h2>
            <p>Use this form to flag suspicious activity or security concerns.</p>
            <form method="post" action="">
                <?php echo csrf_field(); ?>
                
                <label for="flag_reason">Reason:</label>
                <select id="flag_reason" name="flag_reason" required style="width: 100%; padding: 10px; margin: 10px 0;">
                    <option value="">Select a reason...</option>
                    <option value="Suspicious Identity">Suspicious Identity</option>
                    <option value="Fraudulent Documents">Fraudulent Documents</option>
                    <option value="Criminal Investigation">Criminal Investigation</option>
                    <option value="Security Threat">Security Threat</option>
                    <option value="Other">Other</option>
                </select>

                <label for="flag_description">Description:</label>
                <textarea id="flag_description" name="flag_description" rows="4" required 
                          style="width: 100%; padding: 10px; margin: 10px 0;"
                          placeholder="Provide detailed information about the security concern..."></textarea>

                <input type="submit" value="Add Security Flag" 
                       style="background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
            </form>
        </div>
    <?php endif; ?>

    <a href="police_officer.php" style="display: inline-block; margin: 20px 0;">← Back to Dashboard</a>

    <?php $conn->close(); ?>
</body>
</html>
