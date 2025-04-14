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
    $user_type = $_POST['user_type'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Additional farmer fields
    $contact_number = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $farming_experience = intval($_POST['farming_experience'] ?? 0);
    
    // For farmers, use the main email as F_email
    $f_email = $email; // Use the main email for F_email

    // Validation
    if (empty($user_type) || empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "All fields are required";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } elseif ($user_type === 'farmer' && (empty($contact_number) || empty($address))) {
        $error = "Contact number and address are required for farmers";
    } else {
        try {
            // Split full name into first and last name
            $name_parts = explode(" ", $full_name, 2);
            $fname = $name_parts[0];
            $lname = isset($name_parts[1]) ? $name_parts[1] : "";

            // Check if email already exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = "Email already exists";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Start transaction
                $conn->begin_transaction();

                try {
                    // Insert into users table with full name as username
                    $stmt = $conn->prepare("INSERT INTO users (email, password, user_type, username) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $email, $hashed_password, $user_type, $full_name);
                    $stmt->execute();
                    $user_id = $conn->insert_id;

                    // Insert into respective role table
                    if ($user_type === 'farmer') {
                        $stmt = $conn->prepare("INSERT INTO farmer (user_id, FName, LName, F_email, contact_number, address, farming_experience) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("issssis", $user_id, $fname, $lname, $email, $contact_number, $address, $farming_experience);
                    } else {
                        $stmt = $conn->prepare("INSERT INTO buyer (user_id) VALUES (?)");
                        $stmt->bind_param("i", $user_id);
                    }
                    $stmt->execute();

                    // Commit transaction
                    $conn->commit();
                    $success = "Registration successful! Please login to continue.";
                    
                    // Redirect to login page after 2 seconds
                    header("refresh:2;url=login.php");
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Registration failed. Please try again.";
                    error_log("Registration error: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            $error = "An error occurred. Please try again.";
            error_log("Database error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - FarmConnect</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .register-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 20px;
        }
        .register-box {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
        }
        .register-box h2 {
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
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #4CAF50;
            outline: none;
        }
        .btn-register {
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
        .btn-register:hover {
            background: #388E3C;
        }
        .login-link {
            text-align: center;
            margin-top: 20px;
        }
        .login-link a {
            color: #4CAF50;
            text-decoration: none;
        }
        .login-link a:hover {
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
        .note {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-top: 15px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
        }
        .farmer-fields {
            display: none;
        }
        .farmer-fields.show {
            display: block;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <div class="register-container">
        <div class="register-box">
            <h2>Join FarmConnect today</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form action="register.php" method="POST">
                <div class="form-group">
                    <label>I am a</label>
                    <select name="user_type" id="user_type" required onchange="toggleFarmerFields()">
                        <option value="">Select user type</option>
                        <option value="farmer">Farmer</option>
                        <option value="buyer">Buyer</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" placeholder="Enter your full name" required>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" placeholder="Enter your email" required>
                </div>

                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Create a password" required>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm_password" placeholder="Confirm your password" required>
                </div>

                <div id="farmer-fields" class="farmer-fields">
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="tel" name="contact_number" placeholder="Enter your contact number">
                    </div>

                    <div class="form-group">
                        <label>Farm Name (Optional)</label>
                        <input type="text" name="farm_name" placeholder="Enter your farm name">
                    </div>

                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" placeholder="Enter your farm location">
                    </div>

                    <div class="form-group">
                        <label>Address</label>
                        <textarea name="address" placeholder="Enter your complete address" rows="3"></textarea>
                    </div>

                    <div class="form-group">
                        <label>Farming Experience (years)</label>
                        <input type="number" name="farming_experience" placeholder="Years of farming experience" min="0">
                    </div>
                </div>

                <button type="submit" class="btn-register">Create Account</button>

                <div class="note">
                    You can update your profile details anytime after logging in.
                </div>

                <div class="login-link">
                    Already have an account? <a href="login.php">Login here</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleFarmerFields() {
            const userType = document.getElementById('user_type').value;
            const farmerFields = document.getElementById('farmer-fields');
            const farmerInputs = farmerFields.getElementsByTagName('input');
            const farmerTextareas = farmerFields.getElementsByTagName('textarea');
            
            if (userType === 'farmer') {
                farmerFields.classList.add('show');
                // Make farmer fields required except farm name
                for (let input of farmerInputs) {
                    if (input.name !== 'farm_name') {
                        input.required = true;
                    }
                }
                for (let textarea of farmerTextareas) {
                    textarea.required = true;
                }
            } else {
                farmerFields.classList.remove('show');
                // Remove required attribute when not farmer
                for (let input of farmerInputs) {
                    input.required = false;
                    input.value = ''; // Clear values
                }
                for (let textarea of farmerTextareas) {
                    textarea.required = false;
                    textarea.value = ''; // Clear values
                }
            }
        }

        // Call on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleFarmerFields();
        });
    </script>
</body>
</html>