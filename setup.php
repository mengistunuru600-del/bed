<?php
echo "<h2>Bed Reservation Database Setup</h2>";

include 'db.php';

if ($conn->connect_error) {
    die("<p style='color:red;'>❌ Connection failed: " . $conn->connect_error . "</p>");
}

echo "<p style='color:green;'>✅ MySQL connection successful</p>";

// Create database if not exists (don't drop existing)
$sql = "CREATE DATABASE IF NOT EXISTS bed_reservation";
if ($conn->query($sql) === TRUE) {
    echo "<p style='color:green;'>✅ Database 'bed_reservation' created/verified</p>";
} else {
    echo "<p style='color:red;'>❌ Error creating database: " . $conn->error . "</p>";
    exit();
}

// Select database
if ($conn->select_db("bed_reservation")) {
    echo "<p style='color:green;'>✅ Database selected successfully</p>";
} else {
    echo "<p style='color:red;'>❌ Error selecting database: " . $conn->error . "</p>";
    exit();
}

// Create beds table
$sql = "DROP TABLE IF EXISTS beds";
$conn->query($sql);

$sql = "CREATE TABLE beds (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(30) NOT NULL,
    status ENUM('available', 'occupied') DEFAULT 'available',
    room_size VARCHAR(20) DEFAULT 'Standard',
    accessories TEXT
)";

if ($conn->query($sql) === TRUE) {
    echo "Table beds created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create reservations table
$sql = "DROP TABLE IF EXISTS reservations";
$conn->query($sql);

$sql = "CREATE TABLE reservations (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    bed_id INT(6) UNSIGNED,
    guest_name VARCHAR(50) NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    FOREIGN KEY (bed_id) REFERENCES beds(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table reservations created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create users table
$sql = "DROP TABLE IF EXISTS users";
$conn->query($sql);

$sql = "CREATE TABLE users (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(30) NOT NULL UNIQUE,
    email VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('customer', 'receptionist', 'manager', 'admin', 'police') DEFAULT 'customer',
    full_name VARCHAR(100),
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table users created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create reservation_requests table for workflow
$sql = "DROP TABLE IF EXISTS reservation_requests";
$conn->query($sql);

$sql = "CREATE TABLE reservation_requests (
    id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT(6) UNSIGNED,
    bed_id INT(6) UNSIGNED,
    customer_name VARCHAR(100) NOT NULL,
    owner_name VARCHAR(100),
    phone VARCHAR(20) NOT NULL,
    location VARCHAR(100) NOT NULL,
    reason TEXT NOT NULL,
    check_in DATE NOT NULL,
    check_out DATE NOT NULL,
    national_id VARCHAR(20) NOT NULL,
    picture_path VARCHAR(255),
    username VARCHAR(30),
    password VARCHAR(10),
    sms_sent BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'approved_by_receptionist', 'approved_by_manager', 'confirmed', 'rejected', 'cancelled') DEFAULT 'pending',
    receptionist_id INT(6) UNSIGNED NULL,
    manager_id INT(6) UNSIGNED NULL,
    receptionist_notes TEXT,
    manager_notes TEXT,
    payment_method ENUM('cbe', 'telebirr') NULL,
    payment_reference VARCHAR(100) NULL,
    payment_amount DECIMAL(10,2) DEFAULT 0.00,
    payment_status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id),
    FOREIGN KEY (bed_id) REFERENCES beds(id),
    FOREIGN KEY (receptionist_id) REFERENCES users(id),
    FOREIGN KEY (manager_id) REFERENCES users(id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Table reservation_requests created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create police_audit_log table
$sql = "DROP TABLE IF EXISTS police_audit_log";
$conn->query($sql);

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
    echo "Table police_audit_log created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Create security_flags table
$sql = "DROP TABLE IF EXISTS security_flags";
$conn->query($sql);

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
    echo "Table security_flags created successfully<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

// Insert default users
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$manager_password = password_hash('manager123', PASSWORD_DEFAULT);
$receptionist_password = password_hash('reception123', PASSWORD_DEFAULT);
$police_password = password_hash('police123', PASSWORD_DEFAULT);

$sql = "INSERT INTO users (username, email, password, role, full_name) VALUES 
('admin', 'admin@bed.com', '$admin_password', 'admin', 'System Administrator'),
('manager', 'manager@bed.com', '$manager_password', 'manager', 'Hotel Manager'),
('reception', 'reception@bed.com', '$receptionist_password', 'receptionist', 'Front Desk Receptionist'),
('police', 'police@bed.com', '$police_password', 'police', 'Police Officer')";

if ($conn->query($sql) === TRUE) {
    echo "Default users created successfully<br>";
} else {
    echo "Error creating default users: " . $conn->error . "<br>";
}

// Insert sample beds
$sql = "INSERT INTO beds (name, status, room_size, accessories) VALUES
('Bed 1', 'available', 'Single', 'WiFi, TV, Mini Fridge, Air Conditioning'),
('Bed 2', 'available', 'Double', 'WiFi, TV, Mini Fridge, Air Conditioning, Balcony'),
('Bed 3', 'available', 'Single', 'WiFi, TV, Air Conditioning'),
('Bed 4', 'available', 'Double', 'WiFi, TV, Mini Fridge, Air Conditioning, Kitchenette'),
('Bed 5', 'available', 'Suite', 'WiFi, TV, Mini Fridge, Air Conditioning, Balcony, Kitchenette, Sofa')";

if ($conn->query($sql) === TRUE) {
    echo "Sample beds inserted successfully<br>";
} else {
    echo "Error inserting beds: " . $conn->error . "<br>";
}

$conn->close();

echo "<hr>";
echo "<h3 style='color:green;'>🎉 Setup Complete!</h3>";
echo "<p>Database and tables have been created successfully.</p>";
echo "<p><strong>Default Login Credentials:</strong></p>";
echo "<ul>";
echo "<li>Admin: username <code>admin</code>, password <code>admin123</code></li>";
echo "<li>Manager: username <code>manager</code>, password <code>manager123</code></li>";
echo "<li>Reception: username <code>reception</code>, password <code>reception123</code></li>";
echo "<li>Police Officer: username <code>police</code>, password <code>police123</code></li>";
echo "</ul>";
echo "<p><a href='index.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Go to Dashboard</a></p>";
?>