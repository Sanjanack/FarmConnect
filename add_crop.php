<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'farmer') {
    header("Location: login.php");
    exit();
}

// Get farmer ID
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT farmer_id FROM farmer WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();
$farmer_id = $farmer['farmer_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $crop_name = filter_input(INPUT_POST, 'crop_name', FILTER_SANITIZE_STRING);
    $quantity = filter_input(INPUT_POST, 'quantity', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $price = filter_input(INPUT_POST, 'price', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $quality = filter_input(INPUT_POST, 'quality', FILTER_SANITIZE_STRING);
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_STRING);
    $harvest_date = filter_input(INPUT_POST, 'harvest_date', FILTER_SANITIZE_STRING);

    $errors = [];
    if (empty($crop_name)) $errors[] = "Crop name is required";
    if (empty($quantity)) $errors[] = "Quantity is required";
    if (empty($price)) $errors[] = "Price is required";
    if (empty($quality)) $errors[] = "Quality is required";
    if (empty($location)) $errors[] = "Location is required";
    if (empty($harvest_date)) $errors[] = "Harvest date is required";

    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO crops (farmer_id, crop_name, c_quantity, price, quality, crop_location, harvest_date)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isddsss", $farmer_id, $crop_name, $quantity, $price, $quality, $location, $harvest_date);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Crop added successfully!";
            header("Location: farmer_dashboard.php");
            exit();
        } else {
            $errors[] = "Failed to add crop. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Crop - FarmConnect</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <nav class="navbar">
        <ul>
            <li><a href="farmer_dashboard.php">Dashboard</a></li>
            <li><a href="add_crop.php">Add New Crop</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h2>Add New Crop</h2>

        <?php if (!empty($errors)): ?>
            <div class="error-messages">
                <?php foreach ($errors as $error): ?>
                    <p class="error"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="add_crop.php" class="form">
            <div class="form-group">
                <label for="crop_name">Crop Name:</label>
                <input type="text" id="crop_name" name="crop_name" required>
            </div>

            <div class="form-group">
                <label for="quantity">Quantity (in kg):</label>
                <input type="number" id="quantity" name="quantity" step="0.01" min="0" required>
            </div>

            <div class="form-group">
                <label for="price">Price per kg ($):</label>
                <input type="number" id="price" name="price" step="0.01" min="0" required>
            </div>

            <div class="form-group">
                <label for="quality">Quality:</label>
                <select id="quality" name="quality" required>
                    <option value="">Select quality</option>
                    <option value="Premium">Premium</option>
                    <option value="Standard">Standard</option>
                    <option value="Economy">Economy</option>
                </select>
            </div>

            <div class="form-group">
                <label for="location">Crop Location:</label>
                <input type="text" id="location" name="location" required>
            </div>

            <div class="form-group">
                <label for="harvest_date">Harvest Date:</label>
                <input type="date" id="harvest_date" name="harvest_date" required>
            </div>

            <button type="submit" class="btn">Add Crop</button>
        </form>
    </div>
</body>
</html> 