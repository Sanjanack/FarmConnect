<?php
session_start();
require_once '../db_connect.php';

// Include the dashboard header
include '../includes/dashboard_header.php';

// Check if user is a farmer
if ($_SESSION['user_type'] !== 'farmer') {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get farmer's ID
    $stmt = $conn->prepare("SELECT farmer_id FROM farmer WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $farmer = $result->fetch_assoc();
    
    if (!$farmer) {
        $error = "Farmer profile not found. Please complete your profile first.";
    } else {
        $farmer_id = $farmer['farmer_id'];

        // Get and sanitize input
        $name = sanitize_input($_POST['name']);
        $description = sanitize_input($_POST['description']);
        $quantity = floatval($_POST['quantity']);
        $unit = sanitize_input($_POST['unit']);
        $price = floatval($_POST['price']);
        
        // Validate input
        if (empty($name) || empty($description) || $quantity <= 0 || empty($unit) || $price <= 0) {
            $error = "Please fill in all fields correctly";
        } else {
            try {
                // Handle image upload
                $image_url = '';
                if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
                    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
                    $file_type = $_FILES['image']['type'];
                    
                    if (!in_array($file_type, $allowed_types)) {
                        throw new Exception("Only JPG and PNG images are allowed");
                    }

                    $file_name = uniqid() . '_' . $_FILES['image']['name'];
                    $upload_path = '../uploads/crops/' . $file_name;
                    
                    // Create directory if it doesn't exist
                    if (!file_exists('../uploads/crops')) {
                        mkdir('../uploads/crops', 0777, true);
                    }

                    if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                        $image_url = 'uploads/crops/' . $file_name;
                    } else {
                        throw new Exception("Failed to upload image");
                    }
                }

                // Insert crop into database
                $stmt = $conn->prepare("
                    INSERT INTO crops (farmer_id, crop_name, description, quantity, unit, price_per_unit, image_url, status, quality_grade) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'available', 'Standard')
                ");

                // Convert numeric values to strings for binding
                $quantity_str = strval($quantity);
                $price_str = strval($price);

                if ($stmt->bind_param("issssss", 
                    $farmer_id,
                    $name,
                    $description,
                    $quantity_str,
                    $unit,
                    $price_str,
                    $image_url
                )) {
                    if ($stmt->execute()) {
                        $success = "Crop added successfully!";
                        // Clear form data
                        $_POST = array();
                    } else {
                        throw new Exception("Failed to add crop");
                    }
                } else {
                    throw new Exception("Failed to bind parameters");
                }
            } catch (Exception $e) {
                error_log("Add crop error: " . $e->getMessage());
                $error = $e->getMessage();
            }
        }
    }
}
?>

<div class="container">
    <div class="page-header">
        <h1>Add New Crop</h1>
        <p>List your crop for buyers</p>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="form-container">
        <form method="POST" action="" enctype="multipart/form-data" class="crop-form">
            <div class="form-group">
                <label for="name">Crop Name</label>
                <input type="text" id="name" name="name" required
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                       placeholder="Enter crop name">
            </div>

            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" required rows="4"
                          placeholder="Describe your crop"><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="quantity">Quantity</label>
                    <input type="number" id="quantity" name="quantity" required min="0" step="0.01"
                           value="<?php echo isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : ''; ?>"
                           placeholder="Enter quantity">
                </div>

                <div class="form-group">
                    <label for="unit">Unit</label>
                    <select id="unit" name="unit" required>
                        <option value="">Select unit</option>
                        <option value="kg" <?php echo isset($_POST['unit']) && $_POST['unit'] === 'kg' ? 'selected' : ''; ?>>Kilogram (kg)</option>
                        <option value="quintal" <?php echo isset($_POST['unit']) && $_POST['unit'] === 'quintal' ? 'selected' : ''; ?>>Quintal</option>
                        <option value="ton" <?php echo isset($_POST['unit']) && $_POST['unit'] === 'ton' ? 'selected' : ''; ?>>Ton</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="price">Price per Unit (₹)</label>
                    <input type="number" id="price" name="price" required min="0" step="0.01"
                           value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>"
                           placeholder="Enter price per unit">
                </div>
            </div>

            <div class="form-group">
                <label for="image">Crop Image</label>
                <input type="file" id="image" name="image" accept="image/jpeg,image/png">
                <small class="form-text">Upload a clear image of your crop (JPG or PNG only)</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Add Crop</button>
                <a href="my_crops.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<style>
.container {
    padding: 20px;
    max-width: 800px;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 30px;
}

.page-header h1 {
    margin: 0 0 10px 0;
    color: #333;
}

.page-header p {
    color: #666;
    font-size: 16px;
}

.form-container {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 30px;
}

.form-group {
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
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

.form-group input[type="file"] {
    padding: 8px;
}

.form-text {
    display: block;
    color: #666;
    font-size: 12px;
    margin-top: 5px;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #4CAF50;
    outline: none;
}

.form-actions {
    margin-top: 30px;
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.btn {
    padding: 12px 24px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    text-decoration: none;
    transition: background 0.3s ease;
}

.btn-primary {
    background: #4CAF50;
    color: white;
}

.btn-primary:hover {
    background: #388E3C;
}

.btn-secondary {
    background: #f5f5f5;
    color: #333;
}

.btn-secondary:hover {
    background: #e0e0e0;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.alert-success {
    background: #e8f5e9;
    color: #2e7d32;
    border: 1px solid #c8e6c9;
}

.alert-error {
    background: #ffebee;
    color: #c62828;
    border: 1px solid #ffcdd2;
}
</style>

<?php include '../includes/dashboard_footer.php'; ?> 