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

// Check if order ID is provided
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit();
}

// Get order details
$order_query = "SELECT o.*, c.crop_name, c.unit, c.price_per_unit, c.quality_grade,
                b.company_name, b.contact_number as buyer_contact, b.address as buyer_address,
                b.business_type
                FROM orders o 
                JOIN crops c ON o.crop_id = c.crop_id 
                JOIN buyer b ON o.buyer_id = b.buyer_id 
                WHERE o.order_id = ? AND c.farmer_id = ?";
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

// Format dates
$order_date = new DateTime($order['order_date']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - FarmConnect</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container">
        <h2>Order Details</h2>

        <div class="order-details-container">
            <div class="card order-summary">
                <h3>Order Summary</h3>
                <p><strong>Order ID:</strong> #<?php echo htmlspecialchars($order['order_id']); ?></p>
                <p><strong>Date:</strong> <?php echo $order_date->format('F j, Y, g:i a'); ?></p>
                <p><strong>Status:</strong> <span class="status-badge <?php echo strtolower($order['status']); ?>"><?php echo ucfirst(htmlspecialchars($order['status'])); ?></span></p>
            </div>

            <div class="card buyer-info">
                <h3>Buyer Information</h3>
                <p><strong>Company:</strong> <?php echo htmlspecialchars($order['company_name']); ?></p>
                <p><strong>Business Type:</strong> <?php echo htmlspecialchars($order['business_type']); ?></p>
                <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($order['buyer_contact']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($order['buyer_address']); ?></p>
            </div>

            <div class="card crop-details">
                <h3>Crop Details</h3>
                <p><strong>Crop Name:</strong> <?php echo htmlspecialchars($order['crop_name']); ?></p>
                <p><strong>Quantity:</strong> <?php echo htmlspecialchars($order['quantity']) . ' ' . htmlspecialchars($order['unit']); ?></p>
                <p><strong>Price per Unit:</strong> ₹<?php echo htmlspecialchars($order['price_per_unit']); ?></p>
                <p><strong>Total Price:</strong> ₹<?php echo htmlspecialchars($order['total_price']); ?></p>
                <p><strong>Quality Grade:</strong> <?php echo htmlspecialchars($order['quality_grade']); ?></p>
            </div>

            <div class="actions">
                <a href="index.php" class="btn btn-secondary">Back to Dashboard</a>
                <?php if ($order['status'] === 'pending'): ?>
                <a href="update_order.php?id=<?php echo $order['order_id']; ?>&action=confirm" class="btn">Confirm Order</a>
                <a href="update_order.php?id=<?php echo $order['order_id']; ?>&action=cancel" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this order?')">Cancel Order</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
        .order-details-container {
            max-width: 1000px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            padding: 1rem;
        }

        .card {
            padding: 1.5rem;
        }

        .card h3 {
            margin-bottom: 1rem;
            color: var(--primary-color);
        }

        .card p {
            margin: 0.5rem 0;
        }

        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-weight: 500;
            text-transform: capitalize;
        }

        .status-badge.pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-badge.confirmed {
            background-color: #d4edda;
            color: #155724;
        }

        .status-badge.completed {
            background-color: #cce5ff;
            color: #004085;
        }

        .status-badge.cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }

        .actions {
            grid-column: 1 / -1;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1rem;
        }

        @media (max-width: 768px) {
            .actions {
                flex-direction: column;
            }
            
            .actions .btn {
                width: 100%;
            }
        }
    </style>
</body>
</html> 