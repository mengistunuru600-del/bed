<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Details</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .details-container { max-width: 1200px; margin: 0 auto; }
        .info-section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px; }
        .info-item { padding: 10px 0; }
        .info-label { font-weight: bold; color: #666; }
        .info-value { margin-top: 5px; }
        .reservations-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .reservations-table th, .reservations-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .reservations-table th { background: #f8f9fa; }
        .status-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-cancelled { background: #e2e3e5; color: #383d41; }
        .payment-badge { padding: 4px 8px; border-radius: 4px; font-size: 12px; }
        .payment-completed { background: #28a745; color: white; }
        .payment-pending { background: #ffc107; color: #000; }
        .receipt-link { color: #007bff; text-decoration: none; }
        .back-btn { display: inline-block; margin: 20px 0; padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 4px; }
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

    $customer_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    // Get customer information
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'customer'");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $customer = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$customer) {
        echo "<p>Customer not found.</p>";
        echo "<a href='manage_customers.php'>Back to Customer List</a>";
        exit();
    }

    // Get customer reservations with payment info
    $stmt = $conn->prepare("SELECT rr.*, b.name as bed_name FROM reservation_requests rr JOIN beds b ON rr.bed_id = b.id WHERE rr.customer_id = ? ORDER BY rr.created_at DESC");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $reservations = $stmt->get_result();
    $stmt->close();

    // Calculate statistics
    $stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN payment_status = 'completed' THEN 1 ELSE 0 END) as paid FROM reservation_requests WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    ?>

    <div class="details-container">
        <h1>Customer Account Details</h1>

        <div class="user-section">
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Receptionist) | 
            <a href="manage_customers.php">Customer List</a> | 
            <a href="receptionist.php">Reception Dashboard</a> | 
            <a href="logout.php">Logout</a></p>
        </div>

        <div class="info-section">
            <h2>Customer Information</h2>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Customer ID</div>
                    <div class="info-value"><?php echo $customer['id']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Username</div>
                    <div class="info-value"><?php echo htmlspecialchars($customer['username']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Full Name</div>
                    <div class="info-value"><?php echo htmlspecialchars($customer['full_name'] ?: 'Not provided'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($customer['email']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone</div>
                    <div class="info-value"><?php echo htmlspecialchars($customer['phone'] ?: 'Not provided'); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Registered</div>
                    <div class="info-value"><?php echo date('M j, Y H:i', strtotime($customer['created_at'])); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Total Reservations</div>
                    <div class="info-value"><?php echo $stats['total']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Paid Reservations</div>
                    <div class="info-value"><?php echo $stats['paid']; ?></div>
                </div>
            </div>
        </div>

        <div class="info-section">
            <h2>Reservation History & Payment Receipts</h2>
            
            <?php if ($reservations->num_rows > 0): ?>
                <table class="reservations-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Bed</th>
                            <th>Check-in</th>
                            <th>Check-out</th>
                            <th>Days</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Status</th>
                            <th>Payment Method</th>
                            <th>Reference</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($reservation = $reservations->fetch_assoc()): 
                            $check_in = new DateTime($reservation['check_in']);
                            $check_out = new DateTime($reservation['check_out']);
                            $days = $check_out->diff($check_in)->days;
                        ?>
                            <tr>
                                <td>#<?php echo $reservation['id']; ?></td>
                                <td><?php echo htmlspecialchars($reservation['bed_name']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($reservation['check_in'])); ?></td>
                                <td><?php echo date('M j, Y', strtotime($reservation['check_out'])); ?></td>
                                <td><?php echo $days; ?> day<?php echo $days > 1 ? 's' : ''; ?></td>
                                <td><strong>ETB <?php echo number_format($reservation['payment_amount'] ?? 0, 2); ?></strong></td>
                                <td>
                                    <?php 
                                    $status_class = 'status-' . str_replace('_', '-', $reservation['status']);
                                    echo "<span class='status-badge $status_class'>" . ucwords(str_replace('_', ' ', $reservation['status'])) . "</span>";
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $payment_class = 'payment-' . $reservation['payment_status'];
                                    echo "<span class='payment-badge $payment_class'>" . ucfirst($reservation['payment_status']) . "</span>";
                                    ?>
                                </td>
                                <td><?php echo $reservation['payment_method'] ? strtoupper($reservation['payment_method']) : 'N/A'; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($reservation['payment_reference'] ?: 'N/A'); ?>
                                    <?php if ($reservation['picture_path'] && $reservation['payment_status'] == 'completed'): ?>
                                        <br><a href="<?php echo htmlspecialchars($reservation['picture_path']); ?>" target="_blank" style="color:#007bff;font-size:12px;">📷 View Screenshot</a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M j, Y', strtotime($reservation['created_at'])); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No reservations found for this customer.</p>
            <?php endif; ?>
        </div>

        <a href="manage_customers.php" class="back-btn">← Back to Customer List</a>
    </div>

    <?php $conn->close(); ?>
</body>
</html>
