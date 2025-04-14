<?php
session_start();
require_once '../../db_connect.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    header('Location: ../../login.php');
    exit();
}

// Get buyer's information
$buyer_query = "SELECT b.*, u.email, u.username 
               FROM buyer b 
               JOIN users u ON b.user_id = u.user_id 
               WHERE b.user_id = ?";
$stmt = $conn->prepare($buyer_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$buyer_result = $stmt->get_result();
$buyer = $buyer_result->fetch_assoc();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate and sanitize input
        $company_name = filter_input(INPUT_POST, 'company_name', FILTER_SANITIZE_STRING);
        $business_type = filter_input(INPUT_POST, 'business_type', FILTER_SANITIZE_STRING);
        $contact_number = filter_input(INPUT_POST, 'contact_number', FILTER_SANITIZE_STRING);
        $address = filter_input(INPUT_POST, 'address', FILTER_SANITIZE_STRING);
        
        // Basic validation
        if (empty($company_name)) $errors[] = "Company name is required";
        if (empty($business_type)) $errors[] = "Business type is required";
        if (empty($contact_number)) $errors[] = "Contact number is required";
        if (empty($address)) $errors[] = "Address is required";
        
        if (empty($errors)) {
            $update_query = "UPDATE buyer SET company_name = ?, business_type = ?, contact_number = ?, address = ? WHERE user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ssssi", $company_name, $business_type, $contact_number, $address, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $success = "Profile updated successfully!";
                // Refresh buyer data
                $stmt = $conn->prepare($buyer_query);
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $buyer_result = $stmt->get_result();
                $buyer = $buyer_result->fetch_assoc();
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
                <p><strong>Username:</strong> <?php echo htmlspecialchars($buyer['username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($buyer['email']); ?></p>
                <p><small>Contact support to update account information</small></p>
            </div>

            <form method="POST" action="profile.php" class="form">
                <div class="form-group">
                    <label for="company_name">Company Name:</label>
                    <input type="text" id="company_name" name="company_name" required
                        value="<?php echo isset($buyer['company_name']) ? htmlspecialchars($buyer['company_name']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="business_type">Business Type:</label>
                    <select id="business_type" name="business_type" required>
                        <option value="">Select Business Type</option>
                        <option value="Retailer" <?php echo ($buyer['business_type'] === 'Retailer') ? 'selected' : ''; ?>>Retailer</option>
                        <option value="Wholesaler" <?php echo ($buyer['business_type'] === 'Wholesaler') ? 'selected' : ''; ?>>Wholesaler</option>
                        <option value="Restaurant" <?php echo ($buyer['business_type'] === 'Restaurant') ? 'selected' : ''; ?>>Restaurant</option>
                        <option value="Food Processor" <?php echo ($buyer['business_type'] === 'Food Processor') ? 'selected' : ''; ?>>Food Processor</option>
                        <option value="Other" <?php echo ($buyer['business_type'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="contact_number">Contact Number:</label>
                    <input type="tel" id="contact_number" name="contact_number" required
                        value="<?php echo isset($buyer['contact_number']) ? htmlspecialchars($buyer['contact_number']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label for="address">Business Address:</label>
                    <textarea id="address" name="address" rows="4" required><?php echo isset($buyer['address']) ? htmlspecialchars($buyer['address']) : ''; ?></textarea>
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