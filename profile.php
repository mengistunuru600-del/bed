<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Account</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php
    session_start();
    include 'db.php';
    $conn->select_db("bed_reservation");

    if (!isset($_SESSION['user_id'])) {
        header("Location: login.php");
        exit();
    }

    include 'csrf_util.php';
    $user_id = $_SESSION['user_id'];

    // Get user info - use prepared statement
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();

    // Handle password change
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['change_password'])) {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
            $error = "Invalid CSRF token. Please try again.";
        } else {
            $current_password = $_POST['current_password'];
            $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

            if (password_verify($current_password, $user['password'])) {
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $new_password, $user_id);
                
                if ($stmt->execute()) {
                    $success = "Password changed successfully!";
                } else {
                    $error = "Error updating password.";
                }
                $stmt->close();
            } else {
                $error = "Current password is incorrect.";
            }
        }
    }

    // Get user's reservations - use prepared statement
    $stmt = $conn->prepare("SELECT r.id, b.name as bed_name, r.check_in, r.check_out FROM reservations r JOIN beds b ON r.bed_id = b.id WHERE r.guest_name = ?");
    $stmt->bind_param("s", $user['username']);
    $stmt->execute();
    $reservations = $stmt->get_result();
    $stmt->close();

    $conn->close();
    ?>

    <h1>My Account</h1>

    <div class="account-info">
        <h2>Account Information</h2>
        <p><strong>Username:</strong> <?php echo $user['username']; ?></p>
        <p><strong>Email:</strong> <?php echo $user['email']; ?></p>
        <p><strong>Role:</strong> <?php echo ucfirst($user['role']); ?></p>
        <p><strong>Member since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
    </div>

    <div class="change-password">
        <h2>Change Password</h2>
        <form method="post" action="">
            <?php echo csrf_field(); ?>
            
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" required><br><br>

            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" required><br><br>

            <input type="submit" name="change_password" value="Change Password">
        </form>
        <?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    </div>

    <div class="my-reservations">
        <h2>My Reservation Requests</h2>
        <?php
        include 'db.php';
        $conn->select_db("bed_reservation");
        $stmt = $conn->prepare("SELECT rr.*, b.name as bed_name FROM reservation_requests rr JOIN beds b ON rr.bed_id = b.id WHERE rr.customer_id = ? ORDER BY rr.created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $requests = $stmt->get_result();
        $stmt->close();
        ?>

        <?php if ($requests->num_rows > 0): ?>
            <table>
                <tr><th>Bed</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Submitted</th><th>Action</th></tr>
                <?php while($row = $requests->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['bed_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['check_in']); ?></td>
                        <td><?php echo htmlspecialchars($row['check_out']); ?></td>
                        <td><span class="status-<?php echo str_replace('_', '-', $row['status']); ?>"><?php echo ucwords(str_replace('_', ' ', $row['status'])); ?></span></td>
                        <td><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
                        <td>
                            <?php if ($row['status'] == 'approved_by_manager'): ?>
                                <a href="payment.php?request_id=<?php echo intval($row['id']); ?>" class="action-link" style="background:#28a745;color:white;padding:8px 15px;border-radius:4px;text-decoration:none;font-weight:bold;">💳 Make Payment</a>
                                <p style="font-size:12px;color:#666;margin:5px 0 0 0;">CBE or TeleBirr available</p>
                            <?php elseif ($row['status'] == 'confirmed'): ?>
                                <span class="status-confirmed" style="background:#d4edda;color:#155724;padding:8px 15px;border-radius:4px;">✓ Confirmed</span>
                            <?php elseif ($row['status'] == 'rejected'): ?>
                                <span style="color:#dc3545;">Rejected</span>
                            <?php elseif ($row['status'] == 'pending'): ?>
                                <span style="color:#ffc107;">Waiting for review</span>
                            <?php elseif ($row['status'] == 'approved_by_receptionist'): ?>
                                <span style="color:#17a2b8;">Awaiting Manager Approval</span>
                                Pending Review
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>No reservation requests found.</p>
        <?php endif; ?>
    </div>

    <a href="index.php">Back to Dashboard</a>
</body>
</html>