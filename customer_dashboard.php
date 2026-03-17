<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'customer') {
    header("Location: login.php");
    exit();
}
include 'db.php';
include 'csrf_util.php';
$conn->select_db("bed_reservation");

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Bed Reservation</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Customer Dashboard</h1>

    <div class="user-section">
        <p>Welcome, <?php echo htmlspecialchars($username); ?> | 
        <a href="profile.php">My Account</a> | 
        <a href="index.php">Home</a> | 
        <a href="logout.php">Logout</a></p>
    </div>

    <?php
    // Get user's reservation requests with status
    $stmt = $conn->prepare("
        SELECT rr.*, b.name as bed_name, b.room_size 
        FROM reservation_requests rr 
        JOIN beds b ON rr.bed_id = b.id 
        WHERE rr.customer_id = ? 
        ORDER BY rr.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $requests = $stmt->get_result();
    $stmt->close();

    // Count pending items
    $pending_payment = 0;
    $confirmed = 0;
    $pending_review = 0;
    while ($req = $requests->fetch_assoc()) {
        if ($req['status'] == 'approved_by_manager') $pending_payment++;
        if ($req['status'] == 'confirmed') $confirmed++;
        if ($req['status'] == 'pending' || $req['status'] == 'approved_by_receptionist') $pending_review++;
    }
    
    // Re-run query
    $stmt = $conn->prepare("
        SELECT rr.*, b.name as bed_name, b.room_size 
        FROM reservation_requests rr 
        JOIN beds b ON rr.bed_id = b.id 
        WHERE rr.customer_id = ? 
        ORDER BY rr.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $requests = $stmt->get_result();
    $stmt->close();
    ?>

    <!-- Quick Stats -->
    <div class="admin-dashboard">
        <h2>Your Reservation Status</h2>
        <div class="stats">
            <div class="stat">
                <h3>Pending Review</h3>
                <p><?php echo $pending_review; ?></p>
            </div>
            <div class="stat">
                <h3>Awaiting Payment</h3>
                <p><?php echo $pending_payment; ?></p>
            </div>
            <div class="stat">
                <h3>Confirmed</h3>
                <p><?php echo $confirmed; ?></p>
            </div>
        </div>
    </div>

    <?php if ($pending_payment > 0): ?>
    <div style="background: #fff3cd; border: 2px solid #ffc107; border-radius: 10px; padding: 20px; margin: 20px 0;">
        <h2 style="color: #856404; margin-top: 0;">⚠️ Payment Required!</h2>
        <p>You have <?php echo $pending_payment; ?> reservation(s) awaiting payment. Complete your payment now to confirm your reservation.</p>
        <a href="profile.php#payments" style="background: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">💳 Make Payment Now</a>
    </div>
    <?php endif; ?>

    <div class="my-reservations">
        <h2>My Reservation Requests</h2>
        
        <?php if ($requests->num_rows > 0): ?>
            <table>
                <tr>
                    <th>Bed</th>
                    <th>Room Type</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
                <?php while($row = $requests->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['bed_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['room_size']); ?></td>
                        <td><?php echo htmlspecialchars($row['check_in']); ?></td>
                        <td><?php echo htmlspecialchars($row['check_out']); ?></td>
                        <td>
                            <?php 
                            $status = $row['status'];
                            $status_class = '';
                            $status_text = '';
                            
                            switch($status) {
                                case 'pending':
                                    $status_class = 'style="background:#fff3cd;color:#856404;padding:5px 10px;border-radius:4px;"';
                                    $status_text = '⏳ Pending Review';
                                    break;
                                case 'approved_by_receptionist':
                                    $status_class = 'style="background:#cce5ff;color:#004085;padding:5px 10px;border-radius:4px;"';
                                    $status_text = '📋 Awaiting Manager';
                                    break;
                                case 'approved_by_manager':
                                    $status_class = 'style="background:#f8d7da;color:#721c24;padding:5px 10px;border-radius:4px;"';
                                    $status_text = '💰 Payment Required!';
                                    break;
                                case 'confirmed':
                                    $status_class = 'style="background:#d4edda;color:#155724;padding:5px 10px;border-radius:4px;"';
                                    $status_text = '✅ Confirmed';
                                    break;
                                case 'rejected':
                                    $status_class = 'style="background:#f8d7da;color:#721c24;padding:5px 10px;border-radius:4px;"';
                                    $status_text = '❌ Rejected';
                                    break;
                                default:
                                    $status_text = ucwords(str_replace('_', ' ', $status));
                            }
                            ?>
                            <span <?php echo $status_class; ?>><?php echo $status_text; ?></span>
                        </td>
                        <td>
                            <?php if ($row['status'] == 'approved_by_manager'): ?>
                                <a href="payment.php?request_id=<?php echo intval($row['id']); ?>" style="background:#28a745;color:white;padding:8px 15px;text-decoration:none;border-radius:4px;font-weight:bold;">💳 Pay Now</a>
                            <?php elseif ($row['status'] == 'confirmed'): ?>
                                <span style="color:#28a745;font-weight:bold;">✓ Reserved</span>
                            <?php elseif ($row['status'] == 'rejected'): ?>
                                <span style="color:#dc3545;">Contact Support</span>
                            <?php else: ?>
                                <span style="color:#6c757d;">Waiting...</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        <?php else: ?>
            <p>You haven't made any reservation requests yet.</p>
        <?php endif; ?>
    </div>

    <div class="admin-panel">
        <h2>Quick Actions</h2>
        <div style="display: flex; gap: 15px; flex-wrap: wrap;">
            <a href="request_reservation.php" style="background:#007bff;color:white;padding:15px 25px;text-decoration:none;border-radius:5px;font-weight:bold;">🛏️ New Reservation</a>
            <a href="profile.php" style="background:#6c757d;color:white;padding:15px 25px;text-decoration:none;border-radius:5px;font-weight:bold;">👤 My Account</a>
        </div>
    </div>

    <?php $conn->close(); ?>
    
    <div style="margin-top: 30px; text-align: center;">
        <a href="index.php" style="color: #007bff;">← Back to Main Dashboard</a>
    </div>
</body>
</html>
