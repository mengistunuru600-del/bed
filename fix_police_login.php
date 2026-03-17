<?php
echo "<h2>🔧 Fix Police Officer Login Issue</h2>";

include 'db.php';
$conn->select_db("bed_reservation");

echo "<div style='background:#f8f9fa;padding:20px;border-radius:5px;margin:20px 0;'>";

// Step 1: Check if 'police' role exists in users table
echo "<h3>Step 1: Checking users table structure...</h3>";
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $type = $row['Type'];
    echo "<p>Current role column: <code>$type</code></p>";
    
    if (strpos($type, 'police') === false) {
        echo "<p style='color:orange;'>⚠️ 'police' role not found in ENUM. Adding it...</p>";
        
        // Alter table to add 'police' role
        $sql = "ALTER TABLE users MODIFY COLUMN role ENUM('customer', 'receptionist', 'manager', 'admin', 'police') DEFAULT 'customer'";
        if ($conn->query($sql) === TRUE) {
            echo "<p style='color:green;'>✅ Successfully added 'police' role to users table!</p>";
        } else {
            echo "<p style='color:red;'>❌ Error adding 'police' role: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:green;'>✅ 'police' role already exists in users table.</p>";
    }
}

// Step 2: Check if police account exists
echo "<h3>Step 2: Checking for police officer account...</h3>";
$stmt = $conn->prepare("SELECT id, username FROM users WHERE username = 'police'");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $police = $result->fetch_assoc();
    echo "<p style='color:green;'>✅ Police officer account exists (ID: " . $police['id'] . ", Username: " . $police['username'] . ")</p>";
    $stmt->close();
} else {
    $stmt->close();
    echo "<p style='color:orange;'>⚠️ Police officer account not found. Creating it...</p>";
    
    // Create police officer account
    $police_password = password_hash('police123', PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, full_name) VALUES ('police', 'police@bed.com', ?, 'police', 'Police Officer')");
    $stmt->bind_param("s", $police_password);
    
    if ($stmt->execute()) {
        echo "<p style='color:green;'>✅ Police officer account created successfully!</p>";
        echo "<p><strong>Username:</strong> police</p>";
        echo "<p><strong>Password:</strong> police123</p>";
    } else {
        echo "<p style='color:red;'>❌ Error creating police account: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

// Step 3: Verify police_audit_log table exists
echo "<h3>Step 3: Checking police_audit_log table...</h3>";
$result = $conn->query("SHOW TABLES LIKE 'police_audit_log'");
if ($result && $result->num_rows > 0) {
    echo "<p style='color:green;'>✅ police_audit_log table exists.</p>";
} else {
    echo "<p style='color:orange;'>⚠️ police_audit_log table not found. Creating it...</p>";
    
    $sql = "CREATE TABLE police_audit_log (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        officer_id INT(6) UNSIGNED NOT NULL,
        action VARCHAR(100) NOT NULL,
        details TEXT,
        ip_address VARCHAR(45),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (officer_id) REFERENCES users(id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green;'>✅ police_audit_log table created successfully!</p>";
    } else {
        echo "<p style='color:red;'>❌ Error creating table: " . $conn->error . "</p>";
    }
}

// Step 4: Verify security_flags table exists
echo "<h3>Step 4: Checking security_flags table...</h3>";
$result = $conn->query("SHOW TABLES LIKE 'security_flags'");
if ($result && $result->num_rows > 0) {
    echo "<p style='color:green;'>✅ security_flags table exists.</p>";
} else {
    echo "<p style='color:orange;'>⚠️ security_flags table not found. Creating it...</p>";
    
    $sql = "CREATE TABLE security_flags (
        id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        reservation_id INT(6) UNSIGNED NOT NULL,
        officer_id INT(6) UNSIGNED NOT NULL,
        reason VARCHAR(100) NOT NULL,
        description TEXT NOT NULL,
        status ENUM('active', 'resolved', 'dismissed') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (reservation_id) REFERENCES reservation_requests(id),
        FOREIGN KEY (officer_id) REFERENCES users(id)
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green;'>✅ security_flags table created successfully!</p>";
    } else {
        echo "<p style='color:red;'>❌ Error creating table: " . $conn->error . "</p>";
    }
}

echo "</div>";

echo "<div style='background:#d4edda;color:#155724;padding:20px;border-radius:5px;margin:20px 0;'>";
echo "<h3>🎉 Fix Complete!</h3>";
echo "<p>You can now login as a police officer with:</p>";
echo "<ul>";
echo "<li><strong>Username:</strong> police</li>";
echo "<li><strong>Password:</strong> police123</li>";
echo "</ul>";
echo "</div>";

$conn->close();

echo "<a href='login.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Go to Login</a>";
echo " ";
echo "<a href='index.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Go to Dashboard</a>";
?>
