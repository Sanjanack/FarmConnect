<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    header("Location: ../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: marketplace.php");
    exit();
}

try {
    // Get and validate input
    $crop_id = isset($_POST['crop_id']) ? intval($_POST['crop_id']) : 0;
    $buyer_id = isset($_POST['buyer_id']) ? intval($_POST['buyer_id']) : 0;
    $quantity = isset($_POST['quantity']) ? floatval($_POST['quantity']) : 0;

    if ($crop_id <= 0 || $buyer_id <= 0 || $quantity <= 0) {
        throw new Exception("Invalid input parameters");
    }

    // Get crop details and farmer ID
    $stmt = $conn->prepare("
        SELECT c.*, f.user_id as farmer_user_id, f.name as farmer_name 
        FROM crops c 
        JOIN farmer f ON c.farmer_id = f.farmer_id 
        WHERE c.crop_id = ? AND c.status = 'available'
    ");
    $stmt->bind_param("i", $crop_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $crop = $result->fetch_assoc();

    if (!$crop) {
        throw new Exception("Crop not available");
    }

    if ($quantity > $crop['quantity']) {
        throw new Exception("Requested quantity exceeds available amount");
    }

    // Calculate total price
    $total_price = $quantity * $crop['price_per_unit'];

    // Store order details in session for confirmation
    $_SESSION['pending_order'] = [
        'crop_id' => $crop_id,
        'buyer_id' => $buyer_id,
        'quantity' => $quantity,
        'total_price' => $total_price,
        'crop_name' => $crop['crop_name'],
        'farmer_name' => $crop['farmer_name'],
        'price_per_unit' => $crop['price_per_unit'],
        'unit' => $crop['unit'],
        'farmer_location' => $crop['farmer_location'],
        'farmer_contact' => $crop['contact_number']
    ];

    // Redirect to payment confirmation page
    header("Location: confirm_payment.php");
    exit();

} catch (Exception $e) {
    error_log("Order processing error: " . $e->getMessage());
    $_SESSION['error_message'] = "Failed to process order: " . $e->getMessage();
    header("Location: marketplace.php");
    exit();
}
?> 