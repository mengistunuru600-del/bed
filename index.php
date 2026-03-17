<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bed Reservation Dashboard hhhhh</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php
    session_start();
    include 'db.php';
    
    // Check if database exists
    $db_exists = $conn->select_db("bed_reservation");
    if (!$db_exists) {
        echo "<div style='background:#f8d7da;color:#721c24;padding:20px;margin:20px;border-radius:5px;text-align:center;'>
                <h2>⚠️ Database Not Found</h2>
                <p>The bed reservation database has not been set up yet.</p>
                <p><strong><a href='setup.php' style='color:#0056b3;'>Click here to run the database setup</a></strong></p>
              </div>";
        exit();
    }
    
    // Get notification count for reception users
    $notification_count = 0;
    if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'receptionist') {
        $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM reservation_requests WHERE status = 'pending'");
        if ($stmt !== false) {
            $stmt->execute();
            $notification_count = $stmt->get_result()->fetch_assoc()['pending'];
            $stmt->close();
        }
    }
    ?>
    <h1>Bed Reservation Dashboard</h1>

    <div class="user-section">
        <?php if (isset($_SESSION['user_id'])): ?>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo ucfirst(htmlspecialchars($_SESSION['role'])); ?>) | 
            <a href="profile.php">My Account</a> | 
            <a href="logout.php">Logout</a>
            <?php if ($_SESSION['role'] == 'admin'): ?>
                | <a href="admin.php">Admin Panel</a>
            <?php elseif ($_SESSION['role'] == 'manager'): ?>
                | <a href="manager.php">Manager Dashboard</a>
            <?php elseif ($_SESSION['role'] == 'receptionist'): ?>
                | <a href="receptionist.php">Reception Dashboard</a>
                | <a href="notifications.php" style="position:relative;">
                    Notifications
                    <?php if ($notification_count > 0): ?>
                        <span style="position:absolute;top:-8px;right:-8px;background:#dc3545;color:white;border-radius:50%;width:20px;height:20px;display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:bold;"><?php echo $notification_count; ?></span>
                    <?php endif; ?>
                </a>
            <?php elseif ($_SESSION['role'] == 'customer'): ?>
                | <a href="customer_dashboard.php">My Dashboard</a>
                | <a href="request_reservation.php">Request Reservation</a>
            <?php elseif ($_SESSION['role'] == 'police'): ?>
                | <a href="police_officer.php">Police Dashboard</a>
            <?php endif; ?>
            </p>
            
            <?php if ($_SESSION['role'] == 'customer'): ?>
                <?php
                // Check for pending payments
                $stmt = $conn->prepare("SELECT COUNT(*) as pending FROM reservation_requests WHERE customer_id = ? AND status = 'approved_by_manager'");
                if ($stmt !== false) {
                    $stmt->bind_param("i", $_SESSION['user_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $pending_count = $result->fetch_assoc()['pending'];
                    $stmt->close();
                    
                    if ($pending_count > 0) {
                        echo "<div style='background:#fff3cd;color:#856404;padding:10px;border-radius:5px;margin-top:10px;'>
                            <strong>⚠️ Payment Required:</strong> You have $pending_count reservation(s) awaiting payment. 
                            <a href='profile.php' style='color:#0056b3;'>Click here to make payment</a>
                        </div>";
                    }
                }
                ?>
            <?php endif; ?>
        <?php else: ?>
            <p><a href="login.php">Login</a> | <a href="register.php">Register</a></p>
        <?php endif; ?>
    </div>

    <div class="admin-dashboard">
    
        <div class="admin-actions">
            <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'admin'): ?>
                <a href="admin.php" class="admin-button">Go to Admin Panel</a>
                                <a href="customer_dashboard.php" class="admin-button">customer dashboard</a>

                                                <a href="manager.php" class="admin-button">manager</a>
                           <a href="receptionist.php" class="admin-button">receptionist</a>
            <?php elseif (isset($_SESSION['user_id']) && $_SESSION['role'] == 'customer'): ?>
                <a href="customer_dashboard.php" class="admin-button" style="background:#28a745;">My Dashboard</a>
            <?php else: ?>
                <a href="admin.php" class="admin-button">Go to Admin Panel</a>
            <?php endif; ?>
        </div>
        <div class="stats">
            <?php
            // Get total beds - use prepared statement
            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM beds");
            if ($stmt === false) {
                echo "<div class='stat'><h3>Database Error</h3><p>Please run <a href='setup.php'>setup.php</a> first</p></div>";
            } else {
                $stmt->execute();
                $result_total = $stmt->get_result();
                $total_beds = $result_total->fetch_assoc()['total'];
                $stmt->close();

                // Get available beds
                $stmt = $conn->prepare("SELECT COUNT(*) as available FROM beds WHERE status = 'available'");
                $stmt->execute();
                $result_available = $stmt->get_result();
                $available_beds = $result_available->fetch_assoc()['available'];
                $stmt->close();

                // Occupied beds
                $occupied_beds = $total_beds - $available_beds;

                // Current reservations
                $stmt = $conn->prepare("SELECT COUNT(*) as reservations FROM reservations");
                $stmt->execute();
                $result_reservations = $stmt->get_result();
                $current_reservations = $result_reservations->fetch_assoc()['reservations'];
                $stmt->close();
            ?>
            <div class="stat">
                <h3>Total Beds</h3>
                <p><?php echo $total_beds; ?></p>
            </div>
            <div class="stat">
                <h3>Available Beds</h3>
                <p><?php echo $available_beds; ?></p>
            </div>
            <div class="stat">
                <h3>Occupied Beds</h3>
                <p><?php echo $occupied_beds; ?></p>
            </div>
            <div class="stat">
                <h3>Current Reservations</h3>
                <p><?php echo $current_reservations; ?></p>
            </div>
            <?php } ?>
        </div>
    </div>

    <h2>Available Beds</h2>
    <div class="beds">
        <?php
        $stmt = $conn->prepare("SELECT b.*, 
            (SELECT COUNT(*) FROM reservation_requests rr WHERE rr.bed_id = b.id AND rr.status IN ('pending', 'approved_by_receptionist', 'approved_by_manager')) as pending_count,
            (SELECT status FROM reservation_requests rr WHERE rr.bed_id = b.id AND rr.status IN ('pending', 'approved_by_receptionist', 'approved_by_manager') ORDER BY rr.created_at DESC LIMIT 1) as reservation_status
            FROM beds b WHERE b.status = 'available'");
        if ($stmt === false) {
            echo "<p>Error: Unable to prepare statement. Database table 'beds' may not exist.</p>";
            echo "<p>Please run <a href='setup.php'>setup.php</a> to create the database tables.</p>";
        } else {
            $stmt->execute();
            $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $has_pending = $row['pending_count'] > 0;
                $reservation_status = $row['reservation_status'];
                
                echo "<div class='bed available'>";
                echo "<img src='bed-icon.svg' alt='Bed Icon' class='bed-icon'>";
                echo "<h3>" . htmlspecialchars($row["name"]) . "</h3>";
                echo "<p>Status: " . htmlspecialchars($row["status"]) . "</p>";
                
                // Display reservation status if pending
                if ($has_pending) {
                    $status_text = '';
                    $status_color = '';
                    if ($reservation_status == 'pending') {
                        $status_text = '⏳ Under Review by Reception';
                        $status_color = '#ffc107';
                    } elseif ($reservation_status == 'approved_by_receptionist') {
                        $status_text = '⏳ Awaiting Manager Approval';
                        $status_color = '#17a2b8';
                    } elseif ($reservation_status == 'approved_by_manager') {
                        $status_text = '⏳ Awaiting Payment';
                        $status_color = '#28a745';
                    }
                    echo "<p style='background:" . $status_color . ";color:white;padding:8px;border-radius:4px;font-size:12px;margin:10px 0;'>" . $status_text . "</p>";
                }
                
                echo "<div class='bed-actions'>";
                echo "<a href='bed_details.php?id=" . intval($row["id"]) . "' class='details-button'>View Details</a>";
                
                if ($has_pending) {
                    echo "<button class='reserve-button' style='background:#6c757d;cursor:not-allowed;' disabled>Reservation Pending</button>";
                } else {
                    if (isset($_SESSION['user_id'])) {
                        echo "<a href='reserve.php?bed_id=" . intval($row["id"]) . "' class='reserve-button'>Reserve</a>";
                    } else {
                        echo "<a href='login.php' class='reserve-button'>Reserve</a>";
                    }
                }
                echo "</div>";
                echo "</div>";
            }
        } else {
            echo "No available beds.";
        }
        $stmt->close();
        }
        ?>
    </div>

    <h2>Current Reservations</h2>
    <div class="reservations">
        <?php
        $stmt = $conn->prepare("SELECT r.id, b.name as bed_name, r.guest_name, r.check_in, r.check_out FROM reservations r JOIN beds b ON r.bed_id = b.id");
        if ($stmt === false) {
            echo "<p>Error: Unable to prepare statement. Database tables may not exist.</p>";
            echo "<p>Please run <a href='setup.php'>setup.php</a> to create the database tables.</p>";
        } else {
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                echo "<table>";
                echo "<tr><th>Bed</th><th>Guest</th><th>Check-in</th><th>Check-out</th><th>Action</th></tr>";
                while($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>" . htmlspecialchars($row["bed_name"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["guest_name"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["check_in"]) . "</td>";
                    echo "<td>" . htmlspecialchars($row["check_out"]) . "</td>";
                    echo "<td><a href='cancel.php?id=" . intval($row["id"]) . "'>Cancel</a></td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "No current reservations.";
            }
            $stmt->close();
        }

        $conn->close();
        ?>
    </div>
</body>
</html>
