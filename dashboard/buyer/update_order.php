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

// Check if order ID and action are provided
if (!isset($_GET['id']) || !isset($_GET['action'])) {
    $_SESSION['error'] = "Invalid request";
    header('Location: index.php');
    exit();
}

$order_id = $_GET['id'];
$action = $_GET['action'];

// Verify that the order belongs to this buyer and is in the correct state
$order_query = "SELECT o.* FROM orders o WHERE o.order_id = ? AND o.buyer_id = ? AND o.status = 'confirmed'";
$stmt = $conn->prepare($order_query);
$stmt->bind_param("ii", $order_id, $buyer['buyer_id']);
$stmt->execute();
$order_result = $stmt->get_result();
$order = $order_result->fetch_assoc();

if (!$order) {
    $_SESSION['error'] = "Invalid order or unauthorized access";
    header('Location: index.php');
    exit();
}

if ($action === 'complete') {
    // Start transaction
    $conn->begin_transaction();

    try {
        // Update order status to completed
        $update_query = "UPDATE orders SET status = 'completed' WHERE order_id = ?";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();

        // Commit transaction
        $conn->commit();
        $_SESSION['success'] = "Order has been marked as completed successfully";
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        $_SESSION['error'] = "Failed to update order status: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid action";
}

// Redirect back to order details
header('Location: view_order.php?id=' . $order_id);
exit(); 