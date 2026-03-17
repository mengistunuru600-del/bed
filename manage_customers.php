<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customer Accounts</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .search-bar { margin: 20px 0; }
        .search-bar input { padding: 10px; width: 300px; border: 1px solid #ddd; border-radius: 4px; }
        .search-bar button { padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; }
        .customer-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .customer-table th, .customer-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .customer-table th { background: #f8f9fa; font-weight: bold; }
        .customer-table tr:hover { background: #f8f9fa; }
        .action-btn { padding: 6px 12px; margin: 0 4px; text-decoration: none; border-radius: 4px; font-size: 14px; }
        .view-btn { background: #17a2b8; color: white; }
        .edit-btn { background: #ffc107; color: #000; }
        .status-active { color: #28a745; font-weight: bold; }
        .status-inactive { color: #dc3545; font-weight: bold; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-card h3 { margin: 0 0 10px 0; color: #666; font-size: 14px; }
        .stat-card .number { font-size: 32px; font-weight: bold; color: #007bff; }
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

    // Search functionality
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    
    // Get customer statistics
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM users WHERE role = 'customer'");
    $stmt->execute();
    $total_customers = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    // Get customers with reservations
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT customer_id) as with_reservations FROM reservation_requests");
    $stmt->execute();
    $customers_with_reservations = $stmt->get_result()->fetch_assoc()['with_reservations'];
    $stmt->close();

    // Get total revenue
    $stmt = $conn->prepare("SELECT COUNT(*) as paid FROM reservation_requests WHERE payment_status = 'completed'");
    $stmt->execute();
    $paid_reservations = $stmt->get_result()->fetch_assoc()['paid'];
    $stmt->close();

    // Get customers based on search
    if ($search) {
        $search_param = "%$search%";
        $stmt = $conn->prepare("SELECT id, username, email, full_name, phone, created_at FROM users WHERE role = 'customer' AND (username LIKE ? OR email LIKE ? OR full_name LIKE ? OR phone LIKE ?) ORDER BY created_at DESC");
        $stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
    } else {
        $stmt = $conn->prepare("SELECT id, username, email, full_name, phone, created_at FROM users WHERE role = 'customer' ORDER BY created_at DESC");
    }
    $stmt->execute();
    $customers = $stmt->get_result();
    $stmt->close();
    ?>

    <h1>Manage Customer Accounts</h1>

    <div class="user-section">
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Receptionist) | 
        <a href="receptionist.php">Reception Dashboard</a> | 
        <a href="index.php">Main Dashboard</a> | 
        <a href="logout.php">Logout</a></p>
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <h3>Total Customers</h3>
            <div class="number"><?php echo $total_customers; ?></div>
        </div>
        <div class="stat-card">
            <h3>Active Customers</h3>
            <div class="number"><?php echo $customers_with_reservations; ?></div>
        </div>
        <div class="stat-card">
            <h3>Paid Reservations</h3>
            <div class="number"><?php echo $paid_reservations; ?></div>
        </div>
    </div>

    <div class="search-bar">
        <form method="get" action="">
            <input type="text" name="search" placeholder="Search by name, email, phone..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">Search</button>
            <?php if ($search): ?>
                <a href="manage_customers.php" style="margin-left: 10px;">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <table class="customer-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Full Name</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Registered</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($customers->num_rows > 0): ?>
                <?php while($customer = $customers->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $customer['id']; ?></td>
                        <td><?php echo htmlspecialchars($customer['username']); ?></td>
                        <td><?php echo htmlspecialchars($customer['full_name'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                        <td><?php echo htmlspecialchars($customer['phone'] ?: 'N/A'); ?></td>
                        <td><?php echo date('M j, Y', strtotime($customer['created_at'])); ?></td>
                        <td>
                            <a href="customer_details.php?id=<?php echo $customer['id']; ?>" class="action-btn view-btn">View</a>
                            <a href="edit_customer.php?id=<?php echo $customer['id']; ?>" class="action-btn edit-btn">Edit</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center;">No customers found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php $conn->close(); ?>
</body>
</html>
