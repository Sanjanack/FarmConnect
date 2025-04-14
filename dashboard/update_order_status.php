<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: my_orders.php");
    exit();
}

try {
    $order_id = isset($_POST['order_id']) ? intval($_POST['order_id']) : 0;
    $new_status = isset($_POST['status']) ? $_POST['status'] : '';
    $is_farmer = isset($_POST['is_farmer']) ? true : false;
    
    // Validate input
    $allowed_statuses = $is_farmer ? ['completed'] : ['confirmed', 'cancelled'];
    if ($order_id <= 0 || !in_array($new_status, $allowed_statuses)) {
        throw new Exception("Invalid input parameters");
    }

    // Start transaction
    $conn->autocommit(FALSE);

    if ($is_farmer) {
        // Check if order exists and belongs to the current farmer
        $stmt = $conn->prepare("
            SELECT o.*, c.quantity as crop_quantity, c.crop_id, c.status as crop_status
            FROM orders o
            JOIN crops c ON o.crop_id = c.crop_id
            JOIN farmer f ON c.farmer_id = f.farmer_id
            WHERE o.order_id = ? AND f.user_id = ? AND o.status = 'confirmed'
        ");
        $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    } else {
        // Check if order exists and belongs to the current buyer
        $stmt = $conn->prepare("
            SELECT o.*, c.quantity as crop_quantity, c.crop_id, c.status as crop_status
            FROM orders o
            JOIN crops c ON o.crop_id = c.crop_id
            JOIN buyer b ON o.buyer_id = b.buyer_id
            WHERE o.order_id = ? AND b.user_id = ? AND o.status = 'pending'
        ");
        $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result->fetch_assoc();

    if (!$order) {
        throw new Exception("Order not found or cannot be updated");
    }

    // Update order status
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE order_id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update order status");
    }

    // If order is cancelled, return quantity to crop
    if ($new_status === 'cancelled') {
        $new_crop_quantity = $order['crop_quantity'] + $order['quantity'];
        $stmt = $conn->prepare("
            UPDATE crops 
            SET quantity = ?, status = 'available' 
            WHERE crop_id = ?
        ");
        $stmt->bind_param("di", $new_crop_quantity, $order['crop_id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update crop quantity");
        }
    }

    // Commit transaction
    if (!$conn->commit()) {
        throw new Exception("Failed to commit transaction");
    }

    // Reset autocommit
    $conn->autocommit(TRUE);

    $_SESSION['success_message'] = "Order status updated successfully to " . ucfirst($new_status);
    header("Location: " . ($is_farmer ? "farmer_orders.php" : "my_orders.php"));
    exit();

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $conn->autocommit(TRUE);
    
    $_SESSION['error_message'] = "Failed to update order status: " . $e->getMessage();
    header("Location: " . ($is_farmer ? "farmer_orders.php" : "my_orders.php"));
    exit();
}
?> 