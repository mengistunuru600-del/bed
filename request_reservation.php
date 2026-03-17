<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Reservation</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
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

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
            $error = "Invalid CSRF token. Please try again.";
        } else {
        $bed_id = intval($_POST['bed_id']);
        $check_in = $_POST['check_in'];
        $check_out = $_POST['check_out'];

        // Validate dates
        if (strtotime($check_in) === false || strtotime($check_out) === false) {
            $error = "Invalid date format.";
        } elseif (strtotime($check_in) >= strtotime($check_out)) {
            $error = "Check-out date must be after check-in date.";
        } elseif (strtotime($check_in) < strtotime(date('Y-m-d'))) {
            $error = "Check-in date cannot be in the past.";
        } else {
            // Check if bed is already reserved for the selected dates
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reservation_requests WHERE bed_id = ? AND status IN ('pending', 'approved_by_receptionist', 'approved_by_manager', 'confirmed') AND ((check_in <= ? AND check_out > ?) OR (check_in < ? AND check_out >= ?) OR (check_in >= ? AND check_out <= ?))");
            $stmt->bind_param("issssss", $bed_id, $check_in, $check_in, $check_out, $check_out, $check_in, $check_out);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing_count = $result->fetch_assoc()['count'];
            $stmt->close();
            
            if ($existing_count > 0) {
                $error = "This bed is already reserved for the selected dates. Please choose another bed or different dates.";
            } else {
                // Check if customer already has a pending request for this bed
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM reservation_requests WHERE customer_id = ? AND bed_id = ? AND status IN ('pending', 'approved_by_receptionist', 'approved_by_manager')");
                $stmt->bind_param("ii", $user_id, $bed_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $duplicate_count = $result->fetch_assoc()['count'];
                $stmt->close();
                
                if ($duplicate_count > 0) {
                    $error = "You already have a pending request for this bed. Please wait for approval or choose a different bed.";
                } else {
                    // Use prepared statement
                    $stmt = $conn->prepare("INSERT INTO reservation_requests (customer_id, bed_id, check_in, check_out) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("iiss", $user_id, $bed_id, $check_in, $check_out);
                    
                    if ($stmt->execute()) {
                        $success = "Reservation request submitted successfully! It will be reviewed by our staff.";
                    } else {
                        $error = "Error: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
        }
    }

    // Get available beds - use prepared statement
    $stmt = $conn->prepare("SELECT * FROM beds WHERE status = 'available'");
    $stmt->execute();
    $beds = $stmt->get_result();
    $stmt->close();

    $conn->close();
    ?>

    <h1>Request Bed Reservation</h1>

    <div class="request-form">
        <h2>Submit Reservation Request</h2>
        <p>Your request will be reviewed by our reception staff and manager before confirmation.</p>

        <form method="post" action="">
            <?php echo csrf_field(); ?>
            
            <label for="bed_id">Select Bed:</label>
            <select id="bed_id" name="bed_id" required>
                <option value="">Choose a bed...</option>
                <?php while($bed = $beds->fetch_assoc()): ?>
                    <option value="<?php echo $bed['id']; ?>"><?php echo $bed['name']; ?></option>
                <?php endwhile; ?>
            </select><br><br>

            <label for="check_in">Check-in Date:</label>
            <input type="date" id="check_in" name="check_in" required><br><br>

            <label for="check_out">Check-out Date:</label>
            <input type="date" id="check_out" name="check_out" required><br><br>

            <input type="submit" value="Submit Request">
        </form>

        <?php if (isset($success)) echo "<p style='color:green;'>$success</p>"; ?>
        <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
    </div>

    <div class="my-requests">
        <h2>My Reservation Requests</h2>
        <?php
        include 'db.php';
        $conn->select_db("bed_reservation");
        $stmt = $conn->prepare("SELECT rr.*, b.name as bed_name FROM reservation_requests rr JOIN beds b ON rr.bed_id = b.id WHERE rr.customer_id = ? ORDER BY rr.created_at DESC");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $requests = $stmt->get_result();
        $stmt->close();
        $conn->close();
        ?>

        <?php if ($requests->num_rows > 0): ?>
            <table>
                <tr><th>Bed</th><th>Check-in</th><th>Check-out</th><th>Status</th><th>Submitted</th></tr>
                <?php while($row = $requests->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['bed_name']; ?></td>
                        <td><?php echo $row['check_in']; ?></td>
                        <td><?php echo $row['check_out']; ?></td>
                        <td><span class="status-<?php echo str_replace('_', '-', $row['status']); ?>"><?php echo ucwords(str_replace('_', ' ', $row['status'])); ?></span></td>
                        <td><?php echo date('M j, Y', strtotime($row['created_at'])); ?></td>
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