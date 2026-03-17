<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';
$conn->select_db("bed_reservation");

// Validate and sanitize input
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    echo "Invalid reservation ID.";
    header("Location: index.php");
    exit();
}

// Check if reservation belongs to user or user is admin - use prepared statement
if ($_SESSION['role'] == 'admin') {
    $stmt = $conn->prepare("SELECT bed_id FROM reservations WHERE id = ?");
    $stmt->bind_param("i", $id);
} else {
    $username = $_SESSION['username'];
    $stmt = $conn->prepare("SELECT bed_id FROM reservations WHERE id = ? AND guest_name = ?");
    $stmt->bind_param("is", $id, $username);
}

$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $row = $result->fetch_assoc();
    $bed_id = $row['bed_id'];
    $stmt->close();

    // Delete reservation using prepared statement
    $stmt = $conn->prepare("DELETE FROM reservations WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    
    // Update bed status to available using prepared statement
    $stmt = $conn->prepare("UPDATE beds SET status = 'available' WHERE id = ?");
    $stmt->bind_param("i", $bed_id);
    $stmt->execute();
    $stmt->close();
    
    echo "Reservation cancelled successfully.";
} else {
    $stmt->close();
    echo "Reservation not found or access denied.";
}

header("Location: index.php");
exit();
?>
