<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Bed Reservation System</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: #007bff;
            margin-bottom: 10px;
        }
        .login-form input[type="text"],
        .login-form input[type="password"] {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        .login-form label {
            font-weight: bold;
            color: #333;
        }
        .login-form input[type="submit"] {
            width: 100%;
            background: #007bff;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }
        .login-form input[type="submit"]:hover {
            background: #0056b3;
        }
        .user-types {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            font-size: 14px;
        }
        .user-types h3 {
            margin-top: 0;
            color: #666;
            font-size: 14px;
        }
        .user-types ul {
            margin: 5px 0;
            padding-left: 20px;
        }
    </style>
</head>
<body>
    <?php
    session_start();
    
    // Redirect if already logged in
    if (isset($_SESSION['user_id'])) {
        header("Location: index.php");
        exit();
    }
    
    include 'db.php';
    $conn->select_db("bed_reservation");
    include 'csrf_util.php';

    $error = '';
    $success = '';

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !validate_csrf($_POST['csrf_token'])) {
            $error = "Invalid CSRF token. Please try again.";
        } else {
            $username = trim($_POST['username']);
            $password = $_POST['password'];

            if (empty($username) || empty($password)) {
                $error = "Please enter both username and password.";
            } else {
                // First, try to login with regular users table (Admin, Manager, Reception, Police, Registered Customers)
                $stmt = $conn->prepare("SELECT id, username, email, password, role, full_name FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 1) {
                    $user = $result->fetch_assoc();
                    if (password_verify($password, $user['password'])) {
                        // Successful login from users table
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['full_name'] = $user['full_name'];
                        $stmt->close();
                        
                        // Redirect based on role
                        if ($user['role'] == 'admin') {
                            header("Location: admin.php");
                        } elseif ($user['role'] == 'manager') {
                            header("Location: manager.php");
                        } elseif ($user['role'] == 'receptionist') {
                            header("Location: receptionist.php");
                        } elseif ($user['role'] == 'police') {
                            header("Location: police_officer.php");
                        } else {
                            header("Location: index.php");
                        }
                        exit();
                    } else {
                        $error = "Invalid password.";
                    }
                    $stmt->close();
                } else {
                    $stmt->close();
                    
                    // If not found in users table, check reservation_requests table for auto-generated credentials
                    $stmt = $conn->prepare("SELECT customer_id, username, password, customer_name FROM reservation_requests WHERE username = ?");
                    $stmt->bind_param("s", $username);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows == 1) {
                        $reservation = $result->fetch_assoc();
                        // Password is stored as plain text in reservation_requests (auto-generated)
                        if ($password == $reservation['password']) {
                            // Successful login with auto-generated credentials
                            $_SESSION['user_id'] = $reservation['customer_id'];
                            $_SESSION['username'] = $reservation['username'];
                            $_SESSION['role'] = 'customer';
                            $_SESSION['customer_name'] = $reservation['customer_name'];
                            $stmt->close();
                            header("Location: customer_dashboard.php");
                            exit();
                        } else {
                            $error = "Invalid password.";
                        }
                    } else {
                        $error = "User not found. Please check your username.";
                    }
                    $stmt->close();
                }
            }
        }
    }

    $conn->close();
    ?>

    <div class="login-container">
        <div class="login-header">
            <h1>🏨 Login</h1>
            <p>Bed Reservation System</p>
        </div>

        <?php if (!empty($error)): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div style="background: #d4edda; color: #155724; padding: 12px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <form method="post" action="" class="login-form">
            <?php echo csrf_field(); ?>
            
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" placeholder="Enter your username" required autofocus>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required>

            <input type="submit" value="Login">
        </form>

        <div class="user-types">
            <h3>👥 User Types:</h3>
            <ul>
                <li><strong>Admin:</strong> System administration</li>
                <li><strong>Manager:</strong> Approve reservations</li>
                <li><strong>Reception:</strong> Process requests</li>
                <li><strong>Police:</strong> Verify customers</li>
                <li><strong>Customer:</strong> Book reservations</li>
            </ul>
            <p style="margin-top: 10px; font-size: 12px; color: #666;">
                <em>Note: Customers receive login credentials via SMS after submitting a reservation request.</em>
            </p>
        </div>

        <div style="text-align: center; margin-top: 20px;">
            <a href="index.php" style="color: #007bff; text-decoration: none;">← Back to Dashboard</a>
        </div>
    </div>
</body>
</html>