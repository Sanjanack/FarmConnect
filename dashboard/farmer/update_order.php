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

// Check if order ID and action are provided
if (!isset($_GET['id']) || !isset($_GET['action']) || !in_array($_GET['action'], ['confirm', 'cancel'])) {
    header('Location: index.php');
    exit();
}

try {
    // Get order information and verify ownership
    $order_query = "SELECT o.*, c.farmer_id, c.crop_name, c.quantity as available_quantity, b.company_name 
                   FROM orders o 
                   JOIN crops c ON o.crop_id = c.crop_id 
                   JOIN buyer b ON o.buyer_id = b.buyer_id 
                   WHERE o.order_id = ? AND c.farmer_id = ? AND o.status = 'pending'";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("ii", $_GET['id'], $farmer['farmer_id']);
    $stmt->execute();
    $order_result = $stmt->get_result();
    $order = $order_result->fetch_assoc();

    if (!$order) {
        $_SESSION['error'] = "Invalid order or unauthorized access";
        header('Location: index.php');
        exit();
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        $new_status = ($_GET['action'] === 'confirm') ? 'confirmed' : 'cancelled';
        
        // Update order status
        $update_order = "UPDATE orders SET status = ? WHERE order_id = ?";
        $stmt = $conn->prepare($update_order);
        $stmt->bind_param("si", $new_status, $_GET['id']);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating order status");
        }

        // If cancelling, restore crop quantity
        if ($new_status === 'cancelled') {
            $update_crop = "UPDATE crops SET 
                          quantity = quantity + ?,
                          status = 'available'
                          WHERE crop_id = ?";
            $stmt = $conn->prepare($update_crop);
            $stmt->bind_param("di", $order['quantity'], $order['crop_id']);
            
            if (!$stmt->execute()) {
                throw new Exception("Error restoring crop quantity");
            }
        }

        // Commit transaction
        $conn->commit();
        
        $_SESSION['success'] = "Order " . ($new_status === 'confirmed' ? 'confirmed' : 'cancelled') . " successfully";
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "System error: " . $e->getMessage();
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = "System error: " . $e->getMessage();
}

header('Location: index.php');
exit(); 