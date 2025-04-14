<?php
session_start();
require_once '../../db_connect.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    header('Location: ../../login.php');
    exit();
}

// Get buyer's information
$buyer_query = "SELECT b.* FROM buyer b WHERE b.user_id = ?";
$stmt = $conn->prepare($buyer_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$buyer_result = $stmt->get_result();
$buyer = $buyer_result->fetch_assoc();

$errors = [];
$success = '';

// Check if crop_id is provided
if (!isset($_GET['crop_id'])) {
    header('Location: index.php');
    exit();
}

// Get crop information
$crop_query = "SELECT c.*, f.farm_name, f.location 
               FROM crops c 
               JOIN farmer f ON c.farmer_id = f.farmer_id 
               WHERE c.crop_id = ? AND c.status = 'available'";
$stmt = $conn->prepare($crop_query);
$stmt->bind_param("i", $_GET['crop_id']);
$stmt->execute();
$crop_result = $stmt->get_result();
$crop = $crop_result->fetch_assoc();

if (!$crop) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validate and sanitize input
        $quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_FLOAT);
        
        // Validation
        if (!$quantity || $quantity <= 0) {
            $errors[] = "Valid quantity is required";
        } elseif ($quantity > $crop['quantity']) {
            $errors[] = "Requested quantity exceeds available quantity";
        }

        if (empty($errors)) {
            // Calculate total price
            $total_price = $quantity * $crop['price_per_unit'];

            // Start transaction
            $conn->begin_transaction();

            try {
                // Insert order
                $order_query = "INSERT INTO orders (buyer_id, crop_id, quantity, total_price) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($order_query);
                $stmt->bind_param("iidd", $buyer['buyer_id'], $crop['crop_id'], $quantity, $total_price);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error creating order");
                }

                // Update crop quantity and status
                $new_quantity = $crop['quantity'] - $quantity;
                $new_status = $new_quantity > 0 ? 'available' : 'reserved';
                
                $update_query = "UPDATE crops SET quantity = ?, status = ? WHERE crop_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("dsi", $new_quantity, $new_status, $crop['crop_id']);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error updating crop quantity");
                }

                // Commit transaction
                $conn->commit();
                
                $success = "Order placed successfully!";
                // Redirect after 2 seconds
                header("refresh:2;url=index.php");
                
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
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
    <title>Place Order - FarmConnect</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container">
        <h2>Place Order</h2>

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

        <div class="order-details">
            <div class="card">
                <h3>Crop Details</h3>
                <p><strong>Crop:</strong> <?php echo htmlspecialchars($crop['crop_name']); ?></p>
                <p><strong>Farm:</strong> <?php echo htmlspecialchars($crop['farm_name']); ?></p>
                <p><strong>Location:</strong> <?php echo htmlspecialchars($crop['location']); ?></p>
                <p><strong>Available Quantity:</strong> <?php echo htmlspecialchars($crop['quantity']) . ' ' . htmlspecialchars($crop['unit']); ?></p>
                <p><strong>Price per Unit:</strong> ₹<?php echo htmlspecialchars($crop['price_per_unit']); ?>/<?php echo htmlspecialchars($crop['unit']); ?></p>
                <p><strong>Quality Grade:</strong> <?php echo htmlspecialchars($crop['quality_grade']); ?></p>
            </div>

            <form method="POST" action="place_order.php?crop_id=<?php echo $crop['crop_id']; ?>" class="form">
                <div class="form-group">
                    <label for="quantity">Order Quantity (<?php echo htmlspecialchars($crop['unit']); ?>):</label>
                    <input type="number" id="quantity" name="quantity" step="0.01" min="0.01" 
                           max="<?php echo htmlspecialchars($crop['quantity']); ?>" required
                           value="<?php echo isset($_POST['quantity']) ? htmlspecialchars($_POST['quantity']) : ''; ?>"
                           onchange="updateTotal()">
                </div>

                <div class="form-group">
                    <label>Total Price:</label>
                    <p class="total-price">₹<span id="totalPrice">0.00</span></p>
                </div>

                <button type="submit" class="btn">Place Order</button>
                <a href="index.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>

    <style>
        .order-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        @media (max-width: 768px) {
            .order-details {
                grid-template-columns: 1fr;
            }
        }

        .total-price {
            font-size: 1.25rem;
            font-weight: bold;
            color: var(--primary-color);
        }
    </style>

    <script>
        function updateTotal() {
            const quantity = document.getElementById('quantity').value;
            const pricePerUnit = <?php echo $crop['price_per_unit']; ?>;
            const total = (quantity * pricePerUnit).toFixed(2);
            document.getElementById('totalPrice').textContent = total;
        }

        // Initialize total on page load
        updateTotal();
    </script>
</body>
</html> 