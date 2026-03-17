<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Register</h1>

    <?php
    session_start();
    include 'db.php';
    $conn->select_db("bed_reservation");
    include 'csrf_util.php';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
            $error = "Invalid CSRF token. Please try again.";
        } else {
            $username = trim($_POST['username']);
            $email = trim($_POST['email']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        // Use prepared statement to check if username exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Username already exists. Please choose a different username.";
            $stmt->close();
        } else {
            // Check if email already exists
            $stmt->close();
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email already registered. Please use a different email.";
            } else {
                // Proceed with registration using prepared statement
                $stmt->close();
                $stmt = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $username, $email, $password);
                
                if ($stmt->execute()) {
                    $_SESSION['user_id'] = $conn->insert_id;
                    $_SESSION['username'] = $username;
                    $_SESSION['role'] = 'customer';
                    $stmt->close();
                    header("Location: index.php");
                    exit();
                } else {
                    $error = "Error: " . $stmt->error;
                }
            }
            $stmt->close();
        }
        }
    }

    $conn->close();
    ?>

    <form method="post" action="">
        <?php echo csrf_field(); ?>
        
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required><br><br>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required><br><br>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required><br><br>

        <input type="submit" value="Register">
    </form>

    <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>

    <p>Already have an account? <a href="login.php">Login here</a></p>
    <a href="index.php">Back to Dashboard</a>
</body>
</html>