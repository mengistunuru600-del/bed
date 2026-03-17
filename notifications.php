<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .notifications-container { max-width: 1000px; margin: 0 auto; }
        .notification-card { background: white; padding: 20px; margin: 15px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #007bff; }
        .notification-card.unread { background: #e7f3ff; border-left-color: #ffc107; }
        .notification-card.urgent { border-left-color: #dc3545; }
        .notification-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .notification-title { font-size: 16px; font-weight: bold; color: #333; }
        .notification-time { font-size: 12px; color: #999; }
        .notification-body { color: #666; line-height: 1.6; }
        .notification-actions { margin-top: 15px; }
        .action-link { padding: 8px 16px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin-right: 10px; font-size: 14px; }
        .mark-read-btn { background: #6c757d; }
        .filter-tabs { display: flex; gap: 10px; margin: 20px 0; }
        .filter-tab { padding: 10px 20px; background: #f8f9fa; border: none; border-radius: 4px; cursor: pointer; }
        .filter-tab.active { background: #007bff; color: white; }
        .notification-badge { background: #dc3545; color: white; padding: 2px 8px; border-radius: 10px; font-size: 12px; }
        .empty-state { text-align: center; padding: 40px; color: #999; }
    </style>
</head>
<body>
    <?php
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'receptionist') {
        header("Location: login.php");
        exit();
    }
    include 'db.php';
    $conn->select_db("bed_reservation");

    $receptionist_id = $_SESSION['user_id'];

    // Get notification counts
    $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM reservation_requests WHERE status = 'pending'");
    $stmt->execute();
    $pending_count = $stmt->get_result()->fetch_assoc()['pending'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as approved FROM reservation_requests WHERE status = 'approved_by_manager' AND payment_status = 'pending'");
    $stmt->execute();
    $awaiting_payment = $stmt->get_result()->fetch_assoc()['approved'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as recent FROM reservation_requests WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)");
    $stmt->execute();
    $recent_requests = $stmt->get_result()->fetch_assoc()['recent'];
    $stmt->close();

    // Get all notifications (recent activity)
    $notifications = [];

    // Pending requests
    $stmt = $conn->prepare("SELECT rr.id, rr.created_at, b.name as bed_name, u.full_name, u.username FROM reservation_requests rr JOIN beds b ON rr.bed_id = b.id JOIN users u ON rr.customer_id = u.id WHERE rr.status = 'pending' ORDER BY rr.created_at DESC");
    $stmt->execute();
    $pending = $stmt->get_result();
    while ($row = $pending->fetch_assoc()) {
        $notifications[] = [
            'type' => 'pending_request',
            'title' => 'New Reservation Request',
            'message' => ($row['full_name'] ?: $row['username']) . ' requested ' . $row['bed_name'],
            'time' => $row['created_at'],
            'link' => 'receptionist.php',
            'urgent' => true
        ];
    }
    $stmt->close();

    // Awaiting payment
    $stmt = $conn->prepare("SELECT rr.id, rr.updated_at, b.name as bed_name, u.full_name, u.username FROM reservation_requests rr JOIN beds b ON rr.bed_id = b.id JOIN users u ON rr.customer_id = u.id WHERE rr.status = 'approved_by_manager' AND rr.payment_status = 'pending' ORDER BY rr.updated_at DESC");
    $stmt->execute();
    $payments = $stmt->get_result();
    while ($row = $payments->fetch_assoc()) {
        $notifications[] = [
            'type' => 'awaiting_payment',
            'title' => 'Payment Pending',
            'message' => ($row['full_name'] ?: $row['username']) . ' needs to complete payment for ' . $row['bed_name'],
            'time' => $row['updated_at'],
            'link' => 'manage_customers.php',
            'urgent' => false
        ];
    }
    $stmt->close();

    // Recent completed payments
    $stmt = $conn->prepare("SELECT rr.id, rr.updated_at, rr.payment_amount, b.name as bed_name, u.full_name, u.username FROM reservation_requests rr JOIN beds b ON rr.bed_id = b.id JOIN users u ON rr.customer_id = u.id WHERE rr.payment_status = 'completed' AND rr.updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR) ORDER BY rr.updated_at DESC");
    $stmt->execute();
    $completed = $stmt->get_result();
    while ($row = $completed->fetch_assoc()) {
        $notifications[] = [
            'type' => 'payment_completed',
            'title' => 'Payment Received',
            'message' => ($row['full_name'] ?: $row['username']) . ' paid ETB ' . number_format($row['payment_amount'], 2) . ' for ' . $row['bed_name'],
            'time' => $row['updated_at'],
            'link' => 'manage_customers.php',
            'urgent' => false
        ];
    }
    $stmt->close();

    // Sort notifications by time
    usort($notifications, function($a, $b) {
        return strtotime($b['time']) - strtotime($a['time']);
    });

    $conn->close();
    ?>

    <div class="notifications-container">
        <h1>🔔 Notifications</h1>

        <div class="user-section">
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Receptionist) | 
            <a href="receptionist.php">Reception Dashboard</a> | 
            <a href="manage_customers.php">Manage Customers</a> | 
            <a href="index.php">Main Dashboard</a> | 
            <a href="logout.php">Logout</a></p>
        </div>

        <div class="stats-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin:20px 0;">
            <div class="stat-card" style="background:white;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                <h3 style="margin:0 0 10px 0;color:#666;font-size:14px;">Pending Requests</h3>
                <div style="font-size:32px;font-weight:bold;color:#dc3545;"><?php echo $pending_count; ?></div>
            </div>
            <div class="stat-card" style="background:white;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                <h3 style="margin:0 0 10px 0;color:#666;font-size:14px;">Awaiting Payment</h3>
                <div style="font-size:32px;font-weight:bold;color:#ffc107;"><?php echo $awaiting_payment; ?></div>
            </div>
            <div class="stat-card" style="background:white;padding:20px;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                <h3 style="margin:0 0 10px 0;color:#666;font-size:14px;">Recent (24h)</h3>
                <div style="font-size:32px;font-weight:bold;color:#007bff;"><?php echo $recent_requests; ?></div>
            </div>
        </div>

        <h2>Recent Activity</h2>

        <?php if (count($notifications) > 0): ?>
            <table class="reservations-table" style="width:100%;border-collapse:collapse;margin:20px 0;background:white;box-shadow:0 2px 4px rgba(0,0,0,0.1);">
                <thead>
                    <tr style="background:#f8f9fa;">
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #ddd;">Type</th>
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #ddd;">Customer</th>
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #ddd;">Bed</th>
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #ddd;">Details</th>
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #ddd;">Date & Time</th>
                        <th style="padding:12px;text-align:left;border-bottom:2px solid #ddd;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($notifications as $notification): ?>
                        <tr style="border-bottom:1px solid #ddd;background:white;<?php echo $notification['urgent'] ? 'background:#fff3cd;' : ''; ?>">
                            <td style="padding:12px;background:inherit;">
                                <?php if ($notification['type'] == 'pending_request'): ?>
                                    <span style="background:#dc3545;color:white;padding:4px 8px;border-radius:4px;font-size:12px;font-weight:bold;">NEW REQUEST</span>
                                <?php elseif ($notification['type'] == 'awaiting_payment'): ?>
                                    <span style="background:#ffc107;color:#000;padding:4px 8px;border-radius:4px;font-size:12px;font-weight:bold;">PAYMENT PENDING</span>
                                <?php else: ?>
                                    <span style="background:#28a745;color:white;padding:4px 8px;border-radius:4px;font-size:12px;font-weight:bold;">PAYMENT RECEIVED</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding:12px;background:inherit;">
                                <?php 
                                // Extract customer name from message
                                preg_match('/^([^(]+)/', $notification['message'], $matches);
                                echo htmlspecialchars(trim($matches[1]));
                                ?>
                            </td>
                            <td style="padding:12px;background:inherit;">
                                <?php 
                                // Extract bed name from message
                                preg_match('/(Bed \d+)/', $notification['message'], $matches);
                                echo isset($matches[1]) ? htmlspecialchars($matches[1]) : 'N/A';
                                ?>
                            </td>
                            <td style="padding:12px;background:inherit;">
                                <?php 
                                if ($notification['type'] == 'pending_request') {
                                    echo 'New reservation request';
                                } elseif ($notification['type'] == 'awaiting_payment') {
                                    echo 'Needs to complete payment';
                                } else {
                                    // Extract amount from message
                                    preg_match('/ETB ([\d,]+\.\d+)/', $notification['message'], $matches);
                                    echo isset($matches[1]) ? 'Paid ETB ' . $matches[1] : 'Payment completed';
                                }
                                ?>
                            </td>
                            <td style="padding:12px;background:inherit;"><?php echo date('M j, Y H:i', strtotime($notification['time'])); ?></td>
                            <td style="padding:12px;background:inherit;">
                                <a href="<?php echo $notification['link']; ?>" style="background:#007bff;color:white;padding:6px 12px;text-decoration:none;border-radius:4px;font-size:14px;">View Details</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="empty-state">
                <p>📭 No notifications at this time</p>
                <p>All caught up!</p>
            </div>
        <?php endif; ?>
    </div>

    <div style="text-align:center;margin:30px 0;">
        <a href="receptionist.php" style="padding:10px 20px;background:#6c757d;color:white;text-decoration:none;border-radius:4px;">← Back to Dashboard</a>
    </div>
</body>
</html>
