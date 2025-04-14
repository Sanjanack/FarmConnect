<?php
session_start();
require_once '../../db_connect.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'farmer') {
    header('Location: ../../login.php');
    exit();
}

// Get farmer's information
$farmer_query = "SELECT f.*, u.email, u.username 
                FROM farmer f 
                JOIN users u ON f.user_id = u.user_id 
                WHERE f.user_id = ?";
$stmt = $conn->prepare($farmer_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$farmer_result = $stmt->get_result();
$farmer = $farmer_result->fetch_assoc();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate and sanitize input
        $farm_name = filter_input(INPUT_POST, 'farm_name', FILTER_SANITIZE_STRING);
        $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
        $contact_number = filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_STRING);
        $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);
        
        // Basic validation
        if (empty($farm_name)) $errors[] = "Farm name is required";
        if (empty($location)) $errors[] = "Location is required";
        if (empty($contact_number)) $errors[] = "Contact number is required";
        
        if (empty($errors)) {
            $update_query = "UPDATE farmer SET farm_name = ?, location = ?, contact_number = ?, description = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssssi", $farm_name, $location, $contact_number, $description, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $success = "Profile updated successfully!";
                // Refresh farmer data
                $stmt = $conn->prepare($farmer_query);
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $farmer_result = $stmt->get_result();
                $farmer = $farmer_result->fetch_assoc();
            } else {
                throw new Exception("Error updating profile");
            }
        }
    } catch (Exception $e) {
        $errors[] = "System error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Update Profile - FarmConnect</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container">
        <h2>Update Profile</h2>

        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success-message">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="profile-container">
            <div class="card">
                <h3>Account Information</h3>
                <p><strong>Username:</strong> <?php echo htmlspecialchars($farmer['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($farmer['email']); ?></p>
                <p><small>Contact support to update account information</small></p>
            </div>

            <form method="POST" action="profile.php" class="form">
                <div class="form-group">
                    <label for="farm_name">Farm Name:</label>
                    <input type="text" id="farm_name" name="farm_name" required
                        value="<?php echo isset($farmer['farm_name']) ? htmlspecialchars($farmer['farm_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="location">Location:</label>
                    <input type="text" id="location" name="location" required
                        value="<?php echo isset($farmer['location']) ? htmlspecialchars($farmer['location']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="contact_number">Contact Number:</label>
                    <input type="tel" id="contact_number" name="contact_number" required
                        value="<?php echo isset($farmer['contact_number']) ? htmlspecialchars($farmer['contact_number']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="description">Farm Description:</label>
                    <textarea id="description" name="description" rows="4"><?php echo isset($farmer['description']) ? htmlspecialchars($farmer['description']) : ''; ?></textarea>
                </div>

                <button type="submit" class="btn">Update Profile</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>

    <style>
        .profile-container {
            max-width: 800px;
            margin: 0 auto;
        }
        
        .profile-container .card {
            margin-bottom: 2rem;
            padding: 1.5rem;
        }
        
        .profile-container .card p {
            margin: 0.5rem 0;
        }
        
        .profile-container .card small {
            color: #666;
        }
    </style>
</body>
</html> 