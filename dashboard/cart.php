<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    header("Location: ../login.php");
    exit();
}

// Get buyer's ID
$stmt = $conn->prepare("SELECT buyer_id FROM buyer WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$buyer = $result->fetch_assoc();

if (!$buyer) {
    die("Buyer profile not found. Please complete your profile first.");
}

$buyer_id = $buyer['buyer_id'];

// Get pending orders
$stmt = $conn->prepare("
    SELECT o.*, c.crop_name, c.unit, c.price_per_unit, c.quantity as available_quantity,
           CONCAT(f.FName, ' ', f.LName) as farmer_name, f.address as farmer_location, f.contact_number as farmer_contact
    FROM orders o
    JOIN crops c ON o.crop_id = c.crop_id
    JOIN farmer f ON c.farmer_id = f.farmer_id
    WHERE o.buyer_id = ? AND o.status = 'pending'
    ORDER BY o.order_date DESC
");

$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$cart_items = $stmt->get_result();

// Handle remove from cart
if (isset($_POST['remove_item'])) {
    $order_id = $_POST['order_id'];
    
    $stmt = $conn->prepare("DELETE FROM orders WHERE order_id = ? AND buyer_id = ? AND status = 'pending'");
    $stmt->bind_param("ii", $order_id, $buyer_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Item removed from cart successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to remove item from cart.";
    }
    
    header("Location: cart.php");
    exit();
}

// Handle quantity update
if (isset($_POST['update_quantity'])) {
    $order_id = $_POST['order_id'];
    $new_quantity = $_POST['quantity'];
    $price_per_unit = $_POST['price_per_unit'];
    
    // Calculate new total
    $new_total = $new_quantity * $price_per_unit;
    
    $stmt = $conn->prepare("
        UPDATE orders 
        SET quantity = ?, total_price = ? 
        WHERE order_id = ? AND buyer_id = ? AND status = 'pending'
    ");
    
    $stmt->bind_param("ddii", $new_quantity, $new_total, $order_id, $buyer_id);
    
    if ($stmt->execute()) {
        $_SESSION['success_message'] = "Quantity updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update quantity.";
    }
    
    header("Location: cart.php");
    exit();
}

// Handle payment confirmation
if (isset($_POST['confirm_payment'])) {
    try {
        $conn->begin_transaction();
        
        // Get all pending orders for this buyer
        $stmt = $conn->prepare("
            SELECT o.*, c.quantity as available_quantity 
            FROM orders o
            JOIN crops c ON o.crop_id = c.crop_id
            WHERE o.buyer_id = ? AND o.status = 'pending'
        ");
        $stmt->bind_param("i", $buyer_id);
        $stmt->execute();
        $orders = $stmt->get_result();
        
        while ($order = $orders->fetch_assoc()) {
            // Check if crop is still available
            if ($order['quantity'] > $order['available_quantity']) {
                throw new Exception("Some items are no longer available in the requested quantity.");
            }
            
            // Update crop quantity
            $new_quantity = $order['available_quantity'] - $order['quantity'];
            $stmt = $conn->prepare("
                UPDATE crops 
                SET quantity = ?,
                    status = CASE WHEN ? = 0 THEN 'sold' ELSE 'available' END
                WHERE crop_id = ?
            ");
            $stmt->bind_param("dii", $new_quantity, $new_quantity, $order['crop_id']);
            $stmt->execute();
            
            // Update order status
            $stmt = $conn->prepare("
                UPDATE orders 
                SET status = 'confirmed'
                WHERE order_id = ?
            ");
            $stmt->bind_param("i", $order['order_id']);
            $stmt->execute();
            
            // Create payment entry
            $payment_method = $_POST['payment_method'];
            $stmt = $conn->prepare("
                INSERT INTO payment (
                    order_id,
                    amount,
                    payment_date,
                    payment_method,
                    status
                ) VALUES (
                    ?,
                    ?,
                    CURRENT_TIMESTAMP,
                    ?,
                    'confirmed'
                )
            ");
            $stmt->bind_param("ids", 
                $order['order_id'],
                $order['total_price'],
                $payment_method
            );
            $stmt->execute();

            // Create supply chain entry
            $stmt = $conn->prepare("
                INSERT INTO supply_chain (
                    order_id,
                    status
                ) VALUES (
                    ?,
                    'confirmed'
                )
            ");
            $stmt->bind_param("i", 
                $order['order_id']
            );
            $stmt->execute();
        }
        
        $conn->commit();
        $_SESSION['success_message'] = "Payment successful! Your order has been confirmed.";
        header("Location: my_orders.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: cart.php");
        exit();
    }
}

// Calculate total cart value
$total_cart_value = 0;
$cart_items_array = array();
while ($item = $cart_items->fetch_assoc()) {
    $total_cart_value += $item['total_price'];
    $cart_items_array[] = $item;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - FarmConnect</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .cart-container {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .cart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .cart-item {
            display: flex;
            align-items: start;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            gap: 2rem;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-title {
            font-size: 1.25rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .farmer-details {
            font-size: 0.9rem;
            color: #666;
            margin-bottom: 1rem;
        }
        
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 1rem 0;
        }
        
        .quantity-input {
            width: 80px;
            padding: 0.5rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-align: center;
        }
        
        .price-details {
            text-align: right;
            min-width: 150px;
        }
        
        .unit-price {
            color: #666;
            font-size: 0.9rem;
        }
        
        .total-price {
            color: #2E7D32;
            font-size: 1.1rem;
            font-weight: 500;
            margin-top: 0.5rem;
        }
        
        .cart-summary {
            margin-top: 2rem;
            padding-top: 1rem;
            border-top: 2px solid #ddd;
            text-align: right;
        }
        
        .cart-total {
            font-size: 1.5rem;
            color: #2E7D32;
            margin-bottom: 1rem;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
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
        
        .empty-cart {
            text-align: center;
            padding: 3rem;
        }
        
        .empty-cart i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .empty-cart h3 {
            color: #666;
            margin-bottom: 1rem;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
        }
        
        .alert-success {
            background: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #A5D6A7;
        }
        
        .alert-danger {
            background: #FFEBEE;
            color: #C62828;
            border: 1px solid #FFCDD2;
        }
        
        .payment-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .payment-section h4 {
            margin-bottom: 15px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #666;
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .payment-details {
            margin: 20px 0;
            padding: 15px 0;
            border-top: 1px solid #ddd;
            border-bottom: 1px solid #ddd;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: #666;
        }
        
        .detail-row.total {
            color: #2E7D32;
            font-weight: 500;
            font-size: 1.1em;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px dashed #ddd;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .btn-primary {
            background: #4CAF50;
            color: white;
            padding: 12px 24px;
            font-size: 16px;
            font-weight: 500;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <?php include '../includes/dashboard_header.php'; ?>
    
    <div class="cart-container">
        <div class="cart-header">
            <h2>Shopping Cart</h2>
            <a href="marketplace.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Continue Shopping
            </a>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($cart_items_array)): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h3>Your cart is empty</h3>
                <p>Browse our marketplace to find fresh crops from local farmers!</p>
                <a href="marketplace.php" class="btn btn-primary">Start Shopping</a>
            </div>
        <?php else: ?>
            <?php foreach ($cart_items_array as $item): ?>
                <div class="cart-item">
                    <div class="item-details">
                        <h3 class="item-title"><?php echo htmlspecialchars($item['crop_name']); ?></h3>
                        <div class="farmer-details">
                            <p>Farmer: <?php echo htmlspecialchars($item['farmer_name']); ?></p>
                            <p>Location: <?php echo htmlspecialchars($item['farmer_location']); ?></p>
                        </div>
                        
                        <form method="POST" class="quantity-control">
                            <input type="hidden" name="order_id" value="<?php echo $item['order_id']; ?>">
                            <input type="hidden" name="price_per_unit" value="<?php echo $item['price_per_unit']; ?>">
                            <label>Quantity:</label>
                            <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                   min="1" max="<?php echo $item['available_quantity']; ?>" 
                                   class="quantity-input" required>
                            <span><?php echo htmlspecialchars($item['unit']); ?></span>
                            <button type="submit" name="update_quantity" class="btn btn-primary">
                                Update
                            </button>
                            <button type="submit" name="remove_item" class="btn btn-secondary">
                                Remove
                            </button>
                        </form>
                    </div>
                    
                    <div class="price-details">
                        <div class="unit-price">
                            ₹<?php echo number_format($item['price_per_unit'], 2); ?> per <?php echo htmlspecialchars($item['unit']); ?>
                        </div>
                        <div class="total-price">
                            Total: ₹<?php echo number_format($item['total_price'], 2); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="cart-summary">
                <h3 class="cart-total">Total: ₹<?php echo number_format($total_cart_value, 2); ?></h3>
                
                <?php if ($total_cart_value > 0): ?>
                    <div class="payment-section">
                        <h4>Payment Details</h4>
                        <form method="POST" id="paymentForm">
                            <div class="form-group">
                                <label>Payment Method</label>
                                <select name="payment_method" required class="form-control">
                                    <option value="upi">UPI</option>
                                    <option value="card">Credit/Debit Card</option>
                                    <option value="netbanking">Net Banking</option>
                                    <option value="cod">Cash on Delivery</option>
                                </select>
                            </div>
                            
                            <div class="payment-details">
                                <div class="detail-row">
                                    <span>Subtotal:</span>
                                    <span>₹<?php echo number_format($total_cart_value, 2); ?></span>
                                </div>
                                <div class="detail-row">
                                    <span>Delivery Fee:</span>
                                    <span>₹50.00</span>
                                </div>
                                <div class="detail-row total">
                                    <span>Total Amount:</span>
                                    <span>₹<?php echo number_format($total_cart_value + 50, 2); ?></span>
                                </div>
                            </div>

                            <div class="action-buttons">
                                <a href="marketplace.php" class="btn btn-secondary">Continue Shopping</a>
                                <button type="submit" name="confirm_payment" class="btn btn-primary">
                                    Confirm & Pay ₹<?php echo number_format($total_cart_value + 50, 2); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="empty-cart">
                        <i class="fas fa-shopping-cart"></i>
                        <h3>Your cart is empty</h3>
                        <p>Browse our marketplace to find fresh crops from local farmers!</p>
                        <a href="marketplace.php" class="btn btn-primary">Start Shopping</a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 