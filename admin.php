<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php
    session_start();
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
        header("Location: login.php");
        exit();
    }
    include 'db.php';
    include 'csrf_util.php';
    $conn->select_db("bed_reservation");
    ?>
    <h1>Admin Dashboard</h1>

    <div class="admin-panel">
        <h2>Manage Beds</h2>
        <form method="post" action="">
            <?php echo csrf_field(); ?>
            
            <label for="bed_name">New Bed Name:</label>
            <input type="text" id="bed_name" name="bed_name" required>
            <label for="room_size">Room Size:</label>
            <select id="room_size" name="room_size">
                <option value="Single">Single</option>
                <option value="Double">Double</option>
                <option value="Suite">Suite</option>
                <option value="Family">Family</option>
            </select>
            <label for="accessories">Accessories (comma-separated):</label>
            <input type="text" id="accessories" name="accessories" placeholder="WiFi, TV, Mini Fridge, etc.">
            <input type="submit" name="add_bed" value="Add Bed">
        </form>

        <?php
        // Handle edit bed form - use prepared statement
        if (isset($_GET['edit_bed'])) {
            $edit_id = intval($_GET['edit_bed']);
            $stmt = $conn->prepare("SELECT * FROM beds WHERE id = ?");
            $stmt->bind_param("i", $edit_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $edit_bed = $result->fetch_assoc();
            $stmt->close();
            ?>
            <h3>Edit Bed</h3>
            <form method="post" action="">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="edit_bed_id" value="<?php echo $edit_id; ?>">
                <label for="edit_bed_name">Bed Name:</label>
                <input type="text" id="edit_bed_name" name="edit_bed_name" value="<?php echo $edit_bed['name']; ?>" required>
                <label for="edit_room_size">Room Size:</label>
                <select id="edit_room_size" name="edit_room_size">
                    <option value="Single" <?php echo ($edit_bed['room_size'] == 'Single') ? 'selected' : ''; ?>>Single</option>
                    <option value="Double" <?php echo ($edit_bed['room_size'] == 'Double') ? 'selected' : ''; ?>>Double</option>
                    <option value="Suite" <?php echo ($edit_bed['room_size'] == 'Suite') ? 'selected' : ''; ?>>Suite</option>
                    <option value="Family" <?php echo ($edit_bed['room_size'] == 'Family') ? 'selected' : ''; ?>>Family</option>
                </select>
                <label for="edit_accessories">Accessories (comma-separated):</label>
                <input type="text" id="edit_accessories" name="edit_accessories" value="<?php echo $edit_bed['accessories']; ?>" placeholder="WiFi, TV, Mini Fridge, etc.">
                <input type="submit" name="update_bed" value="Update Bed">
            </form>
            <?php
        }
        ?>

        <h3>All Beds</h3>
        <table>
            <tr><th>ID</th><th>Name</th><th>Room Size</th><th>Accessories</th><th>Status</th><th>Action</th></tr>
            <?php
            // Use prepared statement
            $stmt = $conn->prepare("SELECT * FROM beds");
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["name"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["room_size"]) . "</td>";
                echo "<td>" . (empty($row["accessories"]) ? 'None' : htmlspecialchars(substr($row["accessories"], 0, 30) . (strlen($row["accessories"]) > 30 ? '...' : ''))) . "</td>";
                echo "<td>" . htmlspecialchars($row["status"]) . "</td>";
                echo "<td><a href='admin.php?toggle_status=" . intval($row["id"]) . "'>Toggle Status</a> | <a href='admin.php?edit_bed=" . intval($row["id"]) . "'>Edit</a> | <a href='admin.php?delete_bed=" . intval($row["id"]) . "'>Delete</a></td>";
                echo "</tr>";
            }
            $stmt->close();
            ?>
        </table>
    </div>

    <div class="admin-panel">
        <h2>All Reservations</h2>
        <table>
            <tr><th>ID</th><th>Bed</th><th>Guest</th><th>Check-in</th><th>Check-out</th><th>Action</th></tr>
            <?php
            // Use prepared statement with JOIN
            $stmt = $conn->prepare("SELECT r.id, b.name as bed_name, r.guest_name, r.check_in, r.check_out FROM reservations r JOIN beds b ON r.bed_id = b.id");
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["bed_name"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["guest_name"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["check_in"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["check_out"]) . "</td>";
                echo "<td><a href='admin.php?cancel_reservation=" . intval($row["id"]) . "'>Cancel</a></td>";
                echo "</tr>";
            }
            $stmt->close();
            ?>
        </table>
    </div>

    <div class="admin-panel">
        <h2>👮 Manage Police Officers</h2>
        
        <h3>Create New Police Officer Account</h3>
        <form method="post" action="" style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <?php echo csrf_field(); ?>
            
            <label for="police_username">Username:</label>
            <input type="text" id="police_username" name="police_username" required placeholder="e.g., officer_john">
            
            <label for="police_email">Email:</label>
            <input type="email" id="police_email" name="police_email" required placeholder="officer@police.gov">
            
            <label for="police_password">Password:</label>
            <input type="password" id="police_password" name="police_password" required minlength="6" placeholder="Minimum 6 characters">
            
            <label for="police_full_name">Full Name:</label>
            <input type="text" id="police_full_name" name="police_full_name" required placeholder="Officer Full Name">
            
            <label for="police_phone">Phone Number:</label>
            <input type="tel" id="police_phone" name="police_phone" placeholder="Optional">
            
            <input type="submit" name="create_police" value="Create Police Officer Account" style="background: #007bff;">
        </form>

        <h3>All Police Officers</h3>
        <table>
            <tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Phone</th><th>Created</th><th>Action</th></tr>
            <?php
            // Get all police officers
            $stmt = $conn->prepare("SELECT id, username, full_name, email, phone, created_at FROM users WHERE role = 'police' ORDER BY created_at DESC");
            $stmt->execute();
            $result = $stmt->get_result();
            while($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["username"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["full_name"] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($row["email"]) . "</td>";
                echo "<td>" . htmlspecialchars($row["phone"] ?? 'N/A') . "</td>";
                echo "<td>" . date('M j, Y', strtotime($row["created_at"])) . "</td>";
                echo "<td><a href='admin.php?delete_police=" . intval($row["id"]) . "' onclick='return confirm(\"Are you sure you want to delete this police officer account?\")'>Delete</a></td>";
                echo "</tr>";
            }
            $stmt->close();
            ?>
        </table>
    </div>

    <?php
    // Handle form submissions
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
            echo "<p style='color:red;'>Invalid CSRF token. Please try again.</p>";
        } else {
        if (isset($_POST['add_bed'])) {
            $bed_name = trim($_POST['bed_name']);
            $room_size = $_POST['room_size'];
            $accessories = trim($_POST['accessories']);
            
            // Use prepared statement
            $stmt = $conn->prepare("INSERT INTO beds (name, status, room_size, accessories) VALUES (?, 'available', ?, ?)");
            $stmt->bind_param("sss", $bed_name, $room_size, $accessories);
            
            if ($stmt->execute()) {
                echo "<p>Bed added successfully!</p>";
                header("Refresh:0");
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        }

        if (isset($_POST['update_bed'])) {
            $bed_id = intval($_POST['edit_bed_id']);
            $bed_name = trim($_POST['edit_bed_name']);
            $room_size = $_POST['edit_room_size'];
            $accessories = trim($_POST['edit_accessories']);
            
            // Use prepared statement
            $stmt = $conn->prepare("UPDATE beds SET name = ?, room_size = ?, accessories = ? WHERE id = ?");
            $stmt->bind_param("sssi", $bed_name, $room_size, $accessories, $bed_id);
            
            if ($stmt->execute()) {
                echo "<p>Bed updated successfully!</p>";
                header("Location: admin.php");
            } else {
                echo "Error: " . $stmt->error;
            }
            $stmt->close();
        }

        if (isset($_POST['create_police'])) {
            $username = trim($_POST['police_username']);
            $email = trim($_POST['police_email']);
            $password = $_POST['police_password'];
            $full_name = trim($_POST['police_full_name']);
            $phone = trim($_POST['police_phone']);
            
            // Validate inputs
            if (strlen($password) < 6) {
                echo "<p style='color:red;'>Password must be at least 6 characters long.</p>";
            } else {
                // Check if username or email already exists
                $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->bind_param("ss", $username, $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    echo "<p style='color:red;'>Username or email already exists. Please choose different credentials.</p>";
                    $stmt->close();
                } else {
                    $stmt->close();
                    
                    // Hash the password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert new police officer
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, full_name, phone) VALUES (?, ?, ?, 'police', ?, ?)");
                    $stmt->bind_param("sssss", $username, $email, $hashed_password, $full_name, $phone);
                    
                    if ($stmt->execute()) {
                        echo "<div style='background:#d4edda;color:#155724;padding:15px;border-radius:5px;margin:20px 0;'>";
                        echo "<h3>✅ Police Officer Account Created Successfully!</h3>";
                        echo "<p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
                        echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . " (Please save this - it won't be shown again)</p>";
                        echo "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>";
                        echo "</div>";
                        // Clear form by redirecting after 3 seconds
                        echo "<script>setTimeout(function(){ window.location.href='admin.php'; }, 3000);</script>";
                    } else {
                        echo "<p style='color:red;'>Error creating police officer account: " . $stmt->error . "</p>";
                    }
                    $stmt->close();
                }
            }
        }
        }
    }

    // Handle GET actions - validate and sanitize all inputs
    if (isset($_GET['toggle_status'])) {
        $bed_id = intval($_GET['toggle_status']);
        
        $stmt = $conn->prepare("SELECT status FROM beds WHERE id = ?");
        $stmt->bind_param("i", $bed_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $new_status = ($row['status'] == 'available') ? 'occupied' : 'available';
        $stmt->close();
        
        $stmt = $conn->prepare("UPDATE beds SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $bed_id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: admin.php");
        exit();
    }

    if (isset($_GET['delete_bed'])) {
        $bed_id = intval($_GET['delete_bed']);
        
        // First cancel any reservations for this bed
        $stmt = $conn->prepare("DELETE FROM reservations WHERE bed_id = ?");
        $stmt->bind_param("i", $bed_id);
        $stmt->execute();
        $stmt->close();
        
        // Then delete the bed
        $stmt = $conn->prepare("DELETE FROM beds WHERE id = ?");
        $stmt->bind_param("i", $bed_id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: admin.php");
        exit();
    }

    if (isset($_GET['cancel_reservation'])) {
        $reservation_id = intval($_GET['cancel_reservation']);
        
        // Get bed_id
        $stmt = $conn->prepare("SELECT bed_id FROM reservations WHERE id = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $bed_id = $row['bed_id'];
        $stmt->close();
        
        // Delete reservation
        $stmt = $conn->prepare("DELETE FROM reservations WHERE id = ?");
        $stmt->bind_param("i", $reservation_id);
        $stmt->execute();
        $stmt->close();
        
        // Update bed status to available
        $stmt = $conn->prepare("UPDATE beds SET status = 'available' WHERE id = ?");
        $stmt->bind_param("i", $bed_id);
        $stmt->execute();
        $stmt->close();
        
        header("Location: admin.php");
        exit();
    }

    if (isset($_GET['delete_police'])) {
        $police_id = intval($_GET['delete_police']);
        
        // Verify it's a police officer account
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ? AND role = 'police'");
        $stmt->bind_param("i", $police_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $stmt->close();
            
            // Delete related audit logs first
            $stmt = $conn->prepare("DELETE FROM police_audit_log WHERE officer_id = ?");
            $stmt->bind_param("i", $police_id);
            $stmt->execute();
            $stmt->close();
            
            // Delete related security flags
            $stmt = $conn->prepare("DELETE FROM security_flags WHERE officer_id = ?");
            $stmt->bind_param("i", $police_id);
            $stmt->execute();
            $stmt->close();
            
            // Delete the police officer account
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $police_id);
            $stmt->execute();
            $stmt->close();
            
            echo "<script>alert('Police officer account deleted successfully.'); window.location.href='admin.php';</script>";
        } else {
            $stmt->close();
            echo "<script>alert('Invalid police officer ID.'); window.location.href='admin.php';</script>";
        }
        exit();
    }

    $conn->close();
    ?>

    <a href="index.php">Back to Main Dashboard</a>
</body>
</html>