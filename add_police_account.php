<?php
echo "<h2>Add Police Officer Account</h2>";

include 'db.php';
$conn->select_db("bed_reservation");

// Check if police account already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE username = 'police'");
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<p style='color:orange;'>⚠️ Police officer account already exists!</p>";
    $stmt->close();
} else {
    $stmt->close();
    
    // Create police officer account
    $police_password = password_hash('police123', PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role, full_name) VALUES ('police', 'police@bed.com', ?, 'police', 'Police Officer')");
    $stmt->bind_param("s", $police_password);
    
    if ($stmt->execute()) {
        echo "<div style='background:#d4edda;color:#155724;padding:20px;border-radius:5px;'>";
        echo "<h3>✅ Police Officer Account Created Successfully!</h3>";
        echo "<p><strong>Username:</strong> police</p>";
        echo "<p><strong>Password:</strong> police123</p>";
        echo "<p><strong>Email:</strong> police@bed.com</p>";
        echo "<p>You can now login with these credentials.</p>";
        echo "</div>";
    } else {
        echo "<p style='color:red;'>❌ Error creating police account: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

$conn->close();

echo "<br><a href='login.php' style='background:#007bff;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Go to Login</a>";
echo " ";
echo "<a href='index.php' style='background:#28a745;color:white;padding:10px 20px;text-decoration:none;border-radius:5px;'>Go to Dashboard</a>";
?>
