<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Police Officer Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
        .search-box {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .customer-table {
            width: 100%;
            background: white;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .customer-table th, .customer-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .customer-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        .flag-badge {
            background: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
        }
        .verified-badge {
            background: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
        }
        .pending-badge {
            background: #ffc107;
            color: #000;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
        }
        .privacy-notice {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
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
    $conn->select_db("bed_reservation");

    // Handle patient registration
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_patient'])) {
        $customer_name = trim($_POST['customer_name']);
        $owner_name = trim($_POST['owner_name']);
        $phone = trim($_POST['phone']);
        $national_id = trim($_POST['national_id']);
        $location = trim($_POST['location']);
        $reason = trim($_POST['reason']);
        $check_in = $_POST['check_in'];
        $check_out = $_POST['check_out'];
        $bed_id = $_POST['bed_id'];

        $stmt = $conn->prepare("INSERT INTO reservation_requests (customer_name, owner_name, phone, national_id, location, reason, check_in, check_out, bed_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->bind_param("sssssssss", $customer_name, $owner_name, $phone, $national_id, $location, $reason, $check_in, $check_out, $bed_id);
        
        if ($stmt->execute()) {
            $success = "Patient registered successfully.";
        } else {
            $error = "Error registering patient: " . $stmt->error;
        }
        $stmt->close();
    }

    // Get search parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';

    // Get statistics
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reservation_requests WHERE status IN ('confirmed', 'approved_by_manager')");
    $stmt->execute();
    $active_reservations = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reservation_requests WHERE status = 'pending'");
    $stmt->execute();
    $pending_verifications = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reservation_requests WHERE status = 'flagged'");
    $stmt->execute();
    $flagged_records = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();

    $stmt = $conn->prepare("SELECT COUNT(DISTINCT customer_id) as total FROM reservation_requests");
    $stmt->execute();
    $total_customers = $stmt->get_result()->fetch_assoc()['total'];
    $stmt->close();
    ?>

    <div class="header">
        <h1>🚔 Police Officer Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (Police Officer) | <a href="logout.php">Logout</a></p>
    </div>

    <?php if (isset($success)) echo "<p style='color:green; text-align:center;'>$success</p>"; ?>
    <?php if (isset($error)) echo "<p style='color:red; text-align:center;'>$error</p>"; ?>

    <div style="background: white; padding: 20px; border-radius: 8px; margin: 20px 0; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2>Register New Patient</h2>
        <form method="post" action="">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                <div>
                    <label for="customer_name">Patient Name:</label>
                    <input type="text" id="customer_name" name="customer_name" required style="width: 100%; padding: 8px;">
                </div>
                <div>
                    <label for="owner_name">Owner Name:</label>
                    <input type="text" id="owner_name" name="owner_name" required style="width: 100%; padding: 8px;">
                </div>
                <div>
                    <label for="phone">Phone:</label>
                    <input type="text" id="phone" name="phone" required style="width: 100%; padding: 8px;">
                </div>
                <div>
                    <label for="national_id">National ID:</label>
                    <input type="text" id="national_id" name="national_id" required style="width: 100%; padding: 8px;">
                </div>
                <div>
                    <label for="location">Location:</label>
                    <input type="text" id="location" name="location" required style="width: 100%; padding: 8px;">
                </div>
                <div>
                    <label for="check_in">Check-in Date:</label>
                    <input type="date" id="check_in" name="check_in" required style="width: 100%; padding: 8px;">
                </div>
                <div>
                    <label for="check_out">Check-out Date:</label>
                    <input type="date" id="check_out" name="check_out" required style="width: 100%; padding: 8px;">
                </div>
                <div>
                    <label for="bed_id">Bed:</label>
                    <select id="bed_id" name="bed_id" required style="width: 100%; padding: 8px;">
                        <?php
                        $stmt = $conn->prepare("SELECT id, name FROM beds WHERE status = 'available'");
                        $stmt->execute();
                        $beds = $stmt->get_result();
                        while($bed = $beds->fetch_assoc()) {
                            echo "<option value='" . $bed['id'] . "'>" . htmlspecialchars($bed['name']) . "</option>";
                        }
                        $stmt->close();
                        ?>
                    </select>
                </div>
            </div>
            <div style="margin-top: 10px;">
                <label for="reason">Reason:</label>
                <textarea id="reason" name="reason" required style="width: 100%; padding: 8px; height: 60px;"></textarea>
            </div>
            <input type="submit" name="register_patient" value="Register Patient" style="margin-top: 10px; padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;">
        </form>
    </div>

    <div class="privacy-notice">
        <strong>⚠️ Privacy Notice:</strong> This system contains sensitive personal information. Access is restricted to authorized law enforcement personnel only. All activities are logged and monitored. Use of this system constitutes consent to monitoring and auditing.
    </div>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $active_reservations; ?></div>
            <div>Active Reservations</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $pending_verifications; ?></div>
            <div>Pending Verifications</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $flagged_records; ?></div>
            <div>Flagged Records</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_customers; ?></div>
            <div>Total Customers</div>
        </div>
    </div>

    <div class="search-box">
        <h2>🔍 Search Customer Records</h2>
        <form method="get" action="">
            <input type="text" name="search" placeholder="Search by name, owner name, National ID, or reservation ID..." 
                   value="<?php echo htmlspecialchars($search); ?>" style="width: 60%; padding: 10px;">
            
            <select name="status" style="padding: 10px;">
                <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                <option value="confirmed" <?php echo $filter_status == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                <option value="flagged" <?php echo $filter_status == 'flagged' ? 'selected' : ''; ?>>Flagged</option>
            </select>
            
            <input type="submit" value="Search" style="padding: 10px 20px;">
        </form>
    </div>

    <?php
    // Build query based on search and filter
    $query = "SELECT rr.*, b.name as bed_name, u.username 
              FROM reservation_requests rr 
              LEFT JOIN beds b ON rr.bed_id = b.id 
              LEFT JOIN users u ON rr.customer_id = u.id 
              WHERE 1=1";
    
    $params = [];
    $types = "";
    
    if (!empty($search)) {
        $query .= " AND (rr.customer_name LIKE ? OR rr.national_id LIKE ? OR rr.id = ? OR rr.owner_name LIKE ?)";
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $params[] = $search;
        $params[] = $search_param;
        $types .= "ssis";
    }
    
    if ($filter_status != 'all') {
        $query .= " AND rr.status = ?";
        $params[] = $filter_status;
        $types .= "s";
    }
    
    $query .= " ORDER BY rr.created_at DESC LIMIT 50";
    
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $reservations = $stmt->get_result();
    $stmt->close();
    ?>

    <h2>Customer Verification Records</h2>
    <table class="customer-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer Name</th>
                <th>Owner Name</th>
                <th>National ID</th>
                <th>Phone</th>
                <th>Bed</th>
                <th>Check-in</th>
                <th>Check-out</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($reservations->num_rows > 0): ?>
                <?php while($row = $reservations->fetch_assoc()): ?>
                    <tr>
                        <td>#<?php echo $row['id']; ?></td>
                        <td><?php echo htmlspecialchars($row['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['owner_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['national_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['phone']); ?></td>
                        <td><?php echo htmlspecialchars($row['bed_name']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($row['check_in'])); ?></td>
                        <td><?php echo date('M j, Y', strtotime($row['check_out'])); ?></td>
                        <td>
                            <?php if ($row['status'] == 'flagged'): ?>
                                <span class="flag-badge">🚩 Flagged</span>
                            <?php elseif ($row['status'] == 'confirmed'): ?>
                                <span class="verified-badge">✓ Confirmed</span>
                            <?php else: ?>
                                <span class="pending-badge">⏳ <?php echo ucwords(str_replace('_', ' ', $row['status'])); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="police_view_customer.php?id=<?php echo $row['id']; ?>" 
                               style="background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;">
                                View Details
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="10" style="text-align: center; padding: 20px;">No records found</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php
    // Log this access
    $officer_id = $_SESSION['user_id'];
    $action = "Accessed dashboard";
    $details = "Viewed customer records list";
    $stmt = $conn->prepare("INSERT INTO police_audit_log (officer_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param("isss", $officer_id, $action, $details, $ip);
    $stmt->execute();
    $stmt->close();

    $conn->close();
    ?>

    <a href="index.php" style="display: inline-block; margin: 20px 0;">← Back to Main Dashboard</a>
</body>
</html>
