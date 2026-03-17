<?php
echo "<h2>Database Migration - Add Payment Amount</h2>";

include 'db.php';

if ($conn->connect_error) {
    die("<p style='color:red;'>❌ Connection failed: " . $conn->connect_error . "</p>");
}

// Select database
if (!$conn->select_db("bed_reservation")) {
    die("<p style='color:red;'>❌ Database 'bed_reservation' not found. Please run setup.php first.</p>");
}

echo "<p style='color:green;'>✅ Connected to database</p>";

// Check if column already exists
$result = $conn->query("SHOW COLUMNS FROM reservation_requests LIKE 'payment_amount'");
if ($result->num_rows > 0) {
    echo "<p style='color:orange;'>⚠️ Column 'payment_amount' already exists. No migration needed.</p>";
} else {
    // Add payment_amount column
    $sql = "ALTER TABLE reservation_requests ADD COLUMN payment_amount DECIMAL(10,2) DEFAULT 0.00 AFTER payment_reference";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green;'>✅ Column 'payment_amount' added successfully</p>";
        
        // Update existing records with calculated amounts (500 ETB per day)
        $sql = "UPDATE reservation_requests SET payment_amount = DATEDIFF(check_out, check_in) * 500 WHERE payment_amount = 0";
        if ($conn->query($sql) === TRUE) {
            $affected = $conn->affected_rows;
            echo "<p style='color:green;'>✅ Updated $affected existing reservations with calculated amounts</p>";
        } else {
            echo "<p style='color:orange;'>⚠️ Could not update existing records: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:red;'>❌ Error adding column: " . $conn->error . "</p>";
    }
}

$conn->close();

echo "<hr>";
echo "<h3 style='color:green;'>Migration Complete!</h3>";
echo "<p><a href='index.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Go to Dashboard</a></p>";
echo "<p><a href='manage_customers.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;margin-left:10px;'>Manage Customers</a></p>";
?>
