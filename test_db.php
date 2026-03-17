<?php
// Test database connection
$servername = "localhost";
$username = "root";
$password = "";

echo "<h2>Database Connection Test</h2>";

// Test basic connection
$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    echo "<p style='color:red;'>❌ Connection failed: " . $conn->connect_error . "</p>";
    exit();
} else {
    echo "<p style='color:green;'>✅ MySQL connection successful</p>";
}

// Test if we can create database
$sql = "CREATE DATABASE IF NOT EXISTS bed_reservation";
if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✅ Database 'bed_reservation' created/exists</p>";
} else {
    echo "<p style='color:red;'>❌ Error creating database: " . $conn->error . "</p>";
}

// Test if we can select the database
if ($conn->select_db("bed_reservation")) {
    echo "<p style='color:green;'>✅ Database 'bed_reservation' selected successfully</p>";
} else {
    echo "<p style='color:red;'>❌ Error selecting database: " . $conn->error . "</p>";
}

// Show existing databases
$result = $conn->query("SHOW DATABASES");
echo "<h3>Available Databases:</h3><ul>";
while ($row = $result->fetch_assoc()) {
    echo "<li>" . $row['Database'] . "</li>";
}
echo "</ul>";

$conn->close();
?>