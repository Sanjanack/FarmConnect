<?php
session_start();
require_once '../../db_connect.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'farmer') {
    header('Location: ../../login.php');
    exit();
}

// Get farmer's information
$farmer_query = "SELECT f.* FROM farmer f WHERE f.user_id = ?";
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
        $crop_name = filter_input(INPUT_POST, 'crop_name', FILTER_SANITIZE_STRING);
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT);
        $price_per_unit = filter_input(INPUT_POST, 'price_per_unit', FILTER_VALIDATE_FLOAT);
        $unit = filter_input(INPUT_POST, 'unit', FILTER_SANITIZE_STRING);
        $quality_grade = filter_input(INPUT_POST, 'quality_grade', FILTER_SANITIZE_STRING);
        $harvest_date = filter_input(INPUT_POST, 'harvest_date', FILTER_SANITIZE_STRING);

        // Validation
        if (empty($crop_name)) $errors[] = "Crop name is required";
        if (!$quantity || $quantity <= 0) $errors[] = "Valid quantity is required";
        if (!$price_per_unit || $price_per_unit <= 0) $errors[] = "Valid price per unit is required";
        if (empty($unit)) $errors[] = "Unit is required";
        if (empty($harvest_date)) $errors[] = "Harvest date is required";

        if (empty($errors)) {
            $query = "INSERT INTO crops (farmer_id, crop_name, quantity, price_per_unit, unit, quality_grade, harvest_date) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isddsss", 
                $farmer['farmer_id'],
                $crop_name,
                $quantity,
                $price_per_unit,
                $unit,
                $quality_grade,
                $harvest_date
            );

            if ($stmt->execute()) {
                $success = "Crop listed successfully!";
                // Redirect after 2 seconds
                header("refresh:2;url=index.php");
            } else {
                throw new Exception("Error listing crop");
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
    <title>Add New Crop - FarmConnect</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container">
        <h2>Add New Crop</h2>

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

        <form method="POST" action="add_crop.php" class="form">
            <div class="form-group">
                <label for="crop_name">Crop Name:</label>
                <input type="text" id="crop_name" name="crop_name" required
                    value="<?php echo isset($_POST['crop_name']) ? htmlspecialchars($_POST['crop_name']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="quantity">Quantity:</label>
                <input type="number" id="quantity" name="quantity" step="0.01" min="0" required
                    value="<?php echo isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="unit">Unit:</label>
                <select id="unit" name="unit" required>
                    <option value="">Select Unit</option>
                    <option value="kg" <?php echo (isset($_POST['unit']) && $_POST['unit'] === 'kg') ? 'selected' : ''; ?>>Kilogram (kg)</option>
                    <option value="quintal" <?php echo (isset($_POST['unit']) && $_POST['unit'] === 'quintal') ? 'selected' : ''; ?>>Quintal</option>
                    <option value="tonne" <?php echo (isset($_POST['unit']) && $_POST['unit'] === 'tonne') ? 'selected' : ''; ?>>Tonne</option>
                </select>
            </div>

            <div class="form-group">
                <label for="price_per_unit">Price per Unit (₹):</label>
                <input type="number" id="price_per_unit" name="price_per_unit" step="0.01" min="0" required
                    value="<?php echo isset($_POST['price_per_unit']) ? htmlspecialchars($_POST['price_per_unit']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="quality_grade">Quality Grade:</label>
                <select id="quality_grade" name="quality_grade">
                    <option value="">Select Grade</option>
                    <option value="A" <?php echo (isset($_POST['quality_grade']) && $_POST['quality_grade'] === 'A') ? 'selected' : ''; ?>>Grade A</option>
                    <option value="B" <?php echo (isset($_POST['quality_grade']) && $_POST['quality_grade'] === 'B') ? 'selected' : ''; ?>>Grade B</option>
                    <option value="C" <?php echo (isset($_POST['quality_grade']) && $_POST['quality_grade'] === 'C') ? 'selected' : ''; ?>>Grade C</option>
                </select>
            </div>

            <div class="form-group">
                <label for="harvest_date">Harvest Date:</label>
                <input type="date" id="harvest_date" name="harvest_date" required
                    value="<?php echo isset($_POST['harvest_date']) ? htmlspecialchars($_POST['harvest_date']) : ''; ?>">
            </div>

            <button type="submit" class="btn">Add Crop</button>
            <a href="index.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</body>
</html> 