<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    header("Location: ../login.php");
    exit();
}

// Get pending orders
$stmt = $conn->prepare("
    SELECT o.*, c.crop_name, c.unit, c.price_per_unit,
           f.name as farmer_name, f.address as farmer_location, f.contact_number as farmer_contact
    FROM orders o
    JOIN crops c ON o.crop_id = c.crop_id
    JOIN farmer f ON c.farmer_id = f.farmer_id
    WHERE o.buyer_id = ? AND o.status = 'pending'
    ORDER BY o.order_date DESC
");

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$pending_orders = $stmt->get_result();

if ($pending_orders->num_rows === 0) {
    header("Location: marketplace.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $conn->autocommit(FALSE);
        
        $total_amount = 0;
        
        // Process each pending order
        $stmt = $conn->prepare("
            SELECT o.*, c.quantity as available_quantity 
            FROM orders o
            JOIN crops c ON o.crop_id = c.crop_id
            WHERE o.order_id = ? AND o.status = 'pending'
            AND o.buyer_id = ? FOR UPDATE
        ");
        
        $update_crop = $conn->prepare("
            UPDATE crops 
            SET quantity = ?, status = ? 
            WHERE crop_id = ?
        ");
        
        $update_order = $conn->prepare("
            UPDATE orders 
            SET status = 'confirmed' 
            WHERE order_id = ?
        ");
        
        $create_supply_chain = $conn->prepare("
            INSERT INTO supply_chain (
                order_id, 
                status, 
                current_location,
                start_date,
                notes
            ) VALUES (?, 'processing', ?, NOW(), 'Order placed and pending processing')
        ");
        
        foreach ($_POST['order_ids'] as $order_id) {
            $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
            
            if (!$order) {
                throw new Exception("Order ID {$order_id} not found or already processed");
            }
            
            // Check if quantity is still available
            if ($order['quantity'] > $order['available_quantity']) {
                throw new Exception("Crop for order ID {$order_id} no longer available in requested quantity");
            }
            
            // Update crop quantity
            $new_quantity = $order['available_quantity'] - $order['quantity'];
            $update_status = $new_quantity == 0 ? 'sold' : 'available';
            
            $update_crop->bind_param("dsi", $new_quantity, $update_status, $order['crop_id']);
            if (!$update_crop->execute()) {
                throw new Exception("Failed to update crop quantity for order ID {$order_id}");
            }
            
            // Update order status
            $update_order->bind_param("i", $order_id);
            if (!$update_order->execute()) {
                throw new Exception("Failed to update status for order ID {$order_id}");
            }
            
            // Create supply chain entry
            $create_supply_chain->bind_param("is", $order_id, $order['farmer_location']);
            if (!$create_supply_chain->execute()) {
                throw new Exception("Failed to create supply chain entry for order ID {$order_id}");
            }
            
            $total_amount += $order['total_price'];
        }
        
        // Commit transaction
        if (!$conn->commit()) {
            throw new Exception("Failed to commit transaction");
        }
        
        // Reset autocommit to true
        $conn->autocommit(TRUE);
        
        $_SESSION['success_message'] = "Orders confirmed successfully! Total amount paid: ₹" . number_format($total_amount, 2);
        header("Location: my_orders.php");
        exit();
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $conn->autocommit(TRUE);
        
        error_log("Order confirmation error: " . $e->getMessage());
        $_SESSION['error_message'] = "Failed to confirm orders: " . $e->getMessage();
        header("Location: confirm_payment.php");
        exit();
    }
}

// Calculate total value
$total_value = 0;
$orders_array = array();
while ($order = $pending_orders->fetch_assoc()) {
    $total_value += $order['total_price'];
    $orders_array[] = $order;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Payment - FarmConnect</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .confirmation-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .order-details {
            margin: 2rem 0;
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .order-item {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #ddd;
        }
        
        .order-item:last-child {
            border-bottom: none;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }
        
        .detail-label {
            color: #666;
            font-weight: 500;
        }
        
        .detail-value {
            color: #333;
            font-weight: 600;
        }
        
        .farmer-info {
            margin: 1rem 0;
            padding: 1rem;
            background: #E8F5E9;
            border-radius: 8px;
        }
        
        .total-amount {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 2px solid #ddd;
            font-size: 1.25rem;
            color: #2E7D32;
            text-align: right;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }
        
        .btn-confirm {
            flex: 1;
            padding: 1rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
        }
        
        .btn-primary:hover {
            background: #388E3C;
        }
        
        .btn-secondary {
            background: #f44336;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #d32f2f;
        }
    </style>
</head>
<body>
    <?php include '../includes/dashboard_header.php'; ?>
    
    <div class="confirmation-container">
        <h2>Confirm Your Payment</h2>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <div class="order-details">
            <?php foreach ($orders_array as $order): ?>
                <div class="order-item">
                    <h3><?php echo htmlspecialchars($order['crop_name']); ?></h3>
                    
                    <div class="detail-row">
                        <span class="detail-label">Quantity:</span>
                        <span class="detail-value">
                            <?php echo htmlspecialchars($order['quantity']) . ' ' . htmlspecialchars($order['unit']); ?>
                        </span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Price per unit:</span>
                        <span class="detail-value">₹<?php echo number_format($order['price_per_unit'], 2); ?></span>
                    </div>
                    
                    <div class="detail-row">
                        <span class="detail-label">Item Total:</span>
                        <span class="detail-value">₹<?php echo number_format($order['total_price'], 2); ?></span>
                    </div>
                    
                    <div class="farmer-info">
                        <h4>Farmer Details</h4>
                        <div class="detail-row">
                            <span class="detail-label">Name:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['farmer_name']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Location:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['farmer_location']); ?></span>
                        </div>
                        <div class="detail-row">
                            <span class="detail-label">Contact:</span>
                            <span class="detail-value"><?php echo htmlspecialchars($order['farmer_contact']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="total-amount">
                <strong>Total Amount to Pay:</strong> ₹<?php echo number_format($total_value, 2); ?>
            </div>
        </div>
        
        <form method="POST" class="action-buttons">
            <?php foreach ($orders_array as $order): ?>
                <input type="hidden" name="order_ids[]" value="<?php echo $order['order_id']; ?>">
            <?php endforeach; ?>
            
            <button type="submit" class="btn-confirm btn-primary">Confirm Payment</button>
            <a href="marketplace.php" class="btn-confirm btn-secondary">Continue Shopping</a>
        </form>
    </div>
</body>
</html> 