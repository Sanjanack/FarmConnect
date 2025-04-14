<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Check if order_id and status are provided
if (!isset($_POST['order_id']) || !isset($_POST['status'])) {
    $_SESSION['error_message'] = "Missing required parameters";
    header("Location: farmer_orders.php");
    exit();
}

$order_id = $_POST['order_id'];
$new_status = $_POST['status'];

// Define valid status transitions
$valid_statuses = [
    'pending' => 'Order placed and awaiting confirmation',
    'confirmed' => 'Order confirmed by farmer',
    'processing' => 'Order is being processed',
    'packed' => 'Order has been packed',
    'shipped' => 'Order has been shipped',
    'in_transit' => 'Order is in transit',
    'out_for_delivery' => 'Order is out for delivery',
    'delivered' => 'Order has been delivered',
    'cancelled' => 'Order has been cancelled'
];

if (!array_key_exists($new_status, $valid_statuses)) {
    $_SESSION['error_message'] = "Invalid status";
    header("Location: farmer_orders.php");
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Get current order details
    $stmt = $conn->prepare("
        SELECT o.*, c.farmer_id 
        FROM orders o 
        JOIN crops c ON o.crop_id = c.crop_id 
        WHERE o.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if (!$order) {
        throw new Exception("Order not found");
    }

    // Verify user has permission to update this order
    if ($_SESSION['user_type'] === 'farmer') {
        $check = $conn->prepare("SELECT farmer_id FROM farmer WHERE user_id = ? AND farmer_id = ?");
        $check->bind_param("ii", $_SESSION['user_id'], $order['farmer_id']);
        $check->execute();
        if ($check->get_result()->num_rows === 0) {
            throw new Exception("Unauthorized access");
        }
    }

    // Update order status
    if (in_array($new_status, ['confirmed', 'pending'])) {
        $update_order = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
        $update_order->bind_param("si", $new_status, $order_id);
        if (!$update_order->execute()) {
            throw new Exception("Failed to update order status");
        }
    } else {
        // Handle supply chain status separately
        $create_entry = $conn->prepare("
            INSERT INTO supply_chain (
                order_id,
                status
            ) VALUES (?, ?)
        ");
        $create_entry->bind_param("is", $order_id, $new_status);
        if (!$create_entry->execute()) {
            throw new Exception("Failed to create supply chain entry");
        }
    }

    // Update total earnings for the farmer
    if ($new_status === 'shipped') {
        $update_earnings = $conn->prepare("UPDATE farmer SET earnings = earnings + ? WHERE farmer_id = ?");
        $update_earnings->bind_param("di", $order['total_price'], $order['farmer_id']);
        if (!$update_earnings->execute()) {
            throw new Exception("Failed to update farmer earnings");
        }

        // Update amount spent by the buyer
        $update_spent = $conn->prepare("UPDATE buyer SET amount_spent = amount_spent + ? WHERE buyer_id = ?");
        $update_spent->bind_param("di", $order['total_price'], $order['buyer_id']);
        if (!$update_spent->execute()) {
            throw new Exception("Failed to update buyer's amount spent");
        }
    }

    // If order is cancelled, restore crop quantity
    if ($new_status === 'cancelled' && $order['status'] !== 'cancelled') {
        $restore_quantity = $conn->prepare("
            UPDATE crops 
            SET quantity = quantity + ?,
                status = CASE 
                    WHEN quantity + ? > 0 THEN 'available'
                    ELSE status 
                END
            WHERE crop_id = ?
        ");
        $restore_quantity->bind_param("ddi", $order['quantity'], $order['quantity'], $order['crop_id']);
        if (!$restore_quantity->execute()) {
            throw new Exception("Failed to restore crop quantity");
        }
    }

    // Commit transaction
    $conn->commit();
    $_SESSION['success_message'] = "Order status updated to " . ucfirst($new_status);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    error_log("Supply chain update error: " . $e->getMessage());
    $_SESSION['error_message'] = "Failed to update order status: " . $e->getMessage();
}

// Redirect back
header("Location: farmer_orders.php");
exit();
?> 