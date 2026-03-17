<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receptionist Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'receptionist') {
        header("Location: login.php");
        exit();
    }
    include 'db.php';
    include 'csrf_util.php';
    $conn->select_db("bed_reservation");

    $receptionist_id = $_SESSION['user_id'];

    // Handle approval/rejection
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['request_id'])) {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
            $error = "Invalid CSRF token. Please try again.";
        } else {
        $request_id = intval($_POST['request_id']);
        $action = $_POST['action'];
        $notes = trim(htmlspecialchars($_POST['notes']));

        if ($action == 'approve') {
            $status = 'approved_by_receptionist';
            $stmt = $conn->prepare("UPDATE reservation_requests SET status = ?, receptionist_id = ?, receptionist_notes = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("sisi", $status, $receptionist_id, $notes, $request_id);
        } else {
            $status = 'rejected';
            $stmt = $conn->prepare("UPDATE reservation_requests SET status = ?, receptionist_id = ?, receptionist_notes = ?, updated_at = NOW() WHERE id = ?");
            $stmt->bind_param("sisi", $status, $receptionist_id, $notes, $request_id);
        }

        if ($stmt->execute()) {
            $success = "Request " . ($action == 'approve' ? 'approved' : 'rejected') . " successfully!";
        } else {
            $error = "Error updating request: " . $stmt->error;
        }
        $stmt->close();
        }
    }

    // Get pending requests - use prepared statement
    $stmt = $conn->prepare("SELECT rr.*, b.name as bed_name, u.username, u.full_name, u.phone FROM reservation_requests rr JOIN beds b ON rr.bed_id = b.id JOIN users u ON rr.customer_id = u.id WHERE rr.status = 'pending' ORDER BY rr.created_at ASC");
    $stmt->execute();
    $pending_requests = $stmt->get_result();
    $pending_count = $pending_requests->num_rows;
    $stmt->close();

    $conn->close();
    ?>

    <h1>Receptionist Dashboard</h1>

    <div class="user-section">
        <p>Welcome, <?php echo $_SESSION['username']; ?> (Receptionist) | 
        <a href="notifications.php" style="background:#ffc107;color:#000;padding:8px 16px;border-radius:4px;text-decoration:none;margin:0 10px;position:relative;">
            🔔 Notifications
            <?php if ($pending_count > 0): ?>
                <span style="position:absolute;top:-8px;right:-8px;background:#dc3545;color:white;border-radius:50%;width:24px;height:24px;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:bold;"><?php echo $pending_count; ?></span>
            <?php endif; ?>
        </a> | 
        <a href="logout.php">Logout</a></p>
    </div>

    <?php if (isset($success)) echo "<div class='alert success'>$success</div>"; ?>
    <?php if (isset($error)) echo "<div class='alert error'>$error</div>"; ?>

    <div class="pending-requests">
        <h2>Pending Reservation Requests</h2>

        <?php if ($pending_requests->num_rows > 0): ?>
            <?php while($request = $pending_requests->fetch_assoc()): ?>
                <div class="request-card">
                    <div class="request-header">
                        <h3>Request #<?php echo $request['id']; ?> - <?php echo $request['bed_name']; ?></h3>
                        <span class="request-date">Submitted: <?php echo date('M j, Y H:i', strtotime($request['created_at'])); ?></span>
                    </div>

                    <div class="request-details">
                        <div class="customer-info">
                            <h4>Customer Information</h4>
                            <p><strong>Name:</strong> <?php echo $request['full_name'] ?: $request['username']; ?></p>
                            <p><strong>Phone:</strong> <?php echo $request['phone'] ?: 'Not provided'; ?></p>
                        </div>

                        <div class="reservation-info">
                            <h4>Reservation Details</h4>
                            <p><strong>Bed:</strong> <?php echo $request['bed_name']; ?></p>
                            <p><strong>Check-in:</strong> <?php echo $request['check_in']; ?></p>
                            <p><strong>Check-out:</strong> <?php echo $request['check_out']; ?></p>
                        </div>
                    </div>

                    <div class="request-actions">
                        <form method="post" action="">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                            <textarea name="notes" placeholder="Add notes (optional)" rows="2"></textarea><br><br>
                            <button type="submit" name="action" value="approve" class="approve-btn">Approve</button>
                            <button type="submit" name="action" value="reject" class="reject-btn">Reject</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p>No pending requests at this time.</p>
        <?php endif; ?>
    </div>

    <a href="index.php">Back to Main Dashboard</a>
</body>
</html>