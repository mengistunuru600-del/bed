<?php
// Diagnostic script to check police accounts
include 'db.php';
$conn->select_db("bed_reservation");

echo "<h2>Police Officer Accounts Check</h2>";

// Check if police accounts exist
$stmt = $conn->prepare("SELECT id, username, email, role, full_name FROM users WHERE role = 'police'");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<p style='color:green;'>Found " . $result->num_rows . " police officer account(s):</p>";
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th></tr>";
    while($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . ($row['full_name'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color:red;'>No police officer accounts found in database!</p>";
    echo "<p>You need to either:</p>";
    echo "<ul>";
    echo "<li>Run <a href='setup.php'>setup.php</a> to create the default police account</li>";
    echo "<li>Or login as admin and create a police officer account manually</li>";
    echo "</ul>";
}

$stmt->close();
$conn->close();
?>
