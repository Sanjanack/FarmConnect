<?php
session_start();
require_once 'db_connect.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password']; // Don't trim password as it might contain spaces
    $user_type = $_POST['user_type'];
    
    if (empty($username) || empty($password) || empty($user_type)) {
        $error = "Please fill in all fields";
    } else {
        try {
            // Debug log
            error_log("Login attempt - Username: " . $username . ", User Type: " . $user_type);
            
            // First check if user exists
            $stmt = $conn->prepare("SELECT user_id, username, password FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();
                error_log("User found - User ID: " . $user['user_id']);
                
                if (password_verify($password, $user['password'])) {
                    // Get additional user details based on user type
                    if ($user_type === 'farmer') {
                        $stmt = $conn->prepare("SELECT farmer_id, FName, LName FROM farmer WHERE user_id = ?");
                        $stmt->bind_param("i", $user['user_id']);
                        $stmt->execute();
                        $farmer = $stmt->get_result()->fetch_assoc();
                        $_SESSION['role_id'] = $farmer['farmer_id'];
                        $_SESSION['name'] = $farmer['FName'] . ' ' . $farmer['LName'];
                    } else {
                        $stmt = $conn->prepare("SELECT buyer_id, name, contact_number, address FROM buyer WHERE user_id = ?");
                        $stmt->bind_param("i", $user['user_id']);
                        $stmt->execute();
                        $buyer = $stmt->get_result()->fetch_assoc();
                        $_SESSION['role_id'] = $buyer['buyer_id'];
                        $_SESSION['name'] = $buyer['name'];
                        
                        // Check if buyer profile is incomplete
                        if (!$buyer['contact_number'] || !$buyer['address']) {
                            $_SESSION['user_id'] = $user['user_id'];
                            $_SESSION['username'] = $user['username'];
                            $_SESSION['user_type'] = $user_type;
                            $_SESSION['profile_incomplete'] = true;
                            header("Location: dashboard/profile.php");
                            exit();
                        }
                    }

                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_type'] = $user_type;
                    
                    header("Location: dashboard/index.php");
                    exit();
                } else {
                    $error = "Invalid password";
                }
            } else {
                $error = "Username not found";
            }
        } catch (Exception $e) {
            $error = "An error occurred. Please try again.";
            error_log("Login error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FarmConnect</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
        }

        .login-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }

        .login-box h2 {
            text-align: center;
            color: #333;
            margin-bottom: 30px;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #4CAF50;
            outline: none;
        }

        .btn-login {
            background: #4CAF50;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            width: 100%;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .btn-login:hover {
            background: #388E3C;
        }

        .register-link {
            text-align: center;
            margin-top: 20px;
        }

        .register-link a {
            color: #4CAF50;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .alert {
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }

        .alert-error {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }

        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .password-field {
            position: relative;
        }

        .password-field input {
            padding-right: 40px;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            border: none;
            background: none;
            cursor: pointer;
            color: #666;
            padding: 5px;
        }

        .toggle-password:focus {
            outline: none;
            color: #4CAF50;
        }

        .toggle-password:hover {
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="login-container">
        <div class="login-box">
            <h2>Login to FarmConnect</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label>Username (Full Name)</label>
                    <input type="text" name="username" placeholder="Enter your full name" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <div class="password-field">
                        <input type="password" name="password" id="password" placeholder="Enter your password" required>
                        <button type="button" class="toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Login As</label>
                    <select name="user_type" required>
                        <option value="">Select User Type</option>
                        <option value="farmer">Farmer</option>
                        <option value="buyer">Buyer</option>
                    </select>
                </div>

                <button type="submit" class="btn-login">Login</button>
                
                <div class="register-link">
                    Don't have an account? <a href="register.php">Register here</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleButton = document.querySelector('.toggle-password i');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleButton.classList.remove('fa-eye');
                toggleButton.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                toggleButton.classList.remove('fa-eye-slash');
                toggleButton.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html> 