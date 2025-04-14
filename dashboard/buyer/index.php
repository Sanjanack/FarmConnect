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

// Get recent orders
$orders_query = "SELECT o.*, c.crop_name, c.unit, c.price_per_unit, c.quality_grade,
                f.name as farmer_name, f.contact_number as farmer_contact
                FROM orders o 
                JOIN crops c ON o.crop_id = c.crop_id 
                JOIN farmer f ON c.farmer_id = f.farmer_id 
                WHERE o.buyer_id = ? 
                ORDER BY o.order_date DESC 
                LIMIT 5";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $buyer['buyer_id']);
$stmt->execute();
$orders_result = $stmt->get_result();

// Get payment statistics
$payment_query = "SELECT 
                    COUNT(*) as total_payments,
                    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as total_paid,
                    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_amount
                FROM payment p 
                JOIN orders o ON p.order_id = o.order_id 
                WHERE o.buyer_id = ?";
$stmt = $conn->prepare($payment_query);
$stmt->bind_param("i", $buyer['buyer_id']);
$stmt->execute();
$payment_stats = $stmt->get_result()->fetch_assoc();

// Get available crops for marketplace
$crops_query = "SELECT c.*, f.farm_name, f.location 
                FROM crops c 
                JOIN farmer f ON c.farmer_id = f.farmer_id 
                WHERE c.status = 'available' 
                ORDER BY c.listing_date DESC";
$crops_result = $conn->query($crops_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Dashboard - FarmConnect</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($buyer['company_name']); ?>!</h2>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message">
                <?php 
                echo htmlspecialchars($_SESSION['success']);
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message">
                <?php 
                echo htmlspecialchars($_SESSION['error']);
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-grid">
            <!-- Profile Summary -->
            <div class="card">
                <h3>Company Profile</h3>
                <p><strong>Company:</strong> <?php echo htmlspecialchars($buyer['company_name']); ?></p>
                <p><strong>Business Type:</strong> <?php echo htmlspecialchars($buyer['business_type']); ?></p>
                <p><strong>Contact:</strong> <?php echo htmlspecialchars($buyer['contact_number']); ?></p>
                <a href="edit_profile.php" class="btn btn-secondary">Edit Profile</a>
            </div>

            <!-- Payment Summary -->
            <div class="card">
                <h3>Payment Summary</h3>
                <p><strong>Total Payments:</strong> ₹<?php echo number_format($payment_stats['total_paid'], 2); ?></p>
                <p><strong>Pending Amount:</strong> ₹<?php echo number_format($payment_stats['pending_amount'], 2); ?></p>
                <div class="button-group">
                    <a href="view_payments.php" class="btn btn-secondary">View All Payments</a>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <h3>Quick Actions</h3>
                <div class="button-group">
                    <a href="../marketplace.php" class="btn">Browse Marketplace</a>
                    <a href="view_orders.php" class="btn btn-secondary">View All Orders</a>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="card full-width">
                <h3>Recent Orders</h3>
                <?php if ($orders_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Farmer</th>
                                    <th>Crop</th>
                                    <th>Quantity</th>
                                    <th>Total Price</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orders_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['farmer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['crop_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['quantity']) . ' ' . htmlspecialchars($order['unit']); ?></td>
                                        <td>₹<?php echo htmlspecialchars($order['total_price']); ?></td>
                                        <td><span class="status-badge <?php echo strtolower($order['status']); ?>"><?php echo ucfirst(htmlspecialchars($order['status'])); ?></span></td>
                                        <td>
                                            <a href="view_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-small">View</a>
                                            <?php if ($order['status'] === 'confirmed'): ?>
                                                <a href="update_order.php?id=<?php echo $order['order_id']; ?>&action=complete" class="btn btn-small">Mark Complete</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No orders yet. <a href="../marketplace.php">Browse the marketplace</a> to place your first order!</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="content-section">
            <h3>Available Crops</h3>
            <div class="marketplace-grid">
                <?php while ($crop = $crops_result->fetch_assoc()): ?>
                <div class="crop-card">
                    <h4><?php echo htmlspecialchars($crop['crop_name']); ?></h4>
                    <p class="farm-info">
                        <strong>Farm:</strong> <?php echo htmlspecialchars($crop['farm_name']); ?><br>
                        <strong>Location:</strong> <?php echo htmlspecialchars($crop['location']); ?>
                    </p>
                    <p class="crop-details">
                        <strong>Quantity:</strong> <?php echo htmlspecialchars($crop['quantity']) . ' ' . htmlspecialchars($crop['unit']); ?><br>
                        <strong>Price:</strong> ₹<?php echo htmlspecialchars($crop['price_per_unit']); ?>/<?php echo htmlspecialchars($crop['unit']); ?><br>
                        <strong>Quality:</strong> Grade <?php echo htmlspecialchars($crop['quality_grade']); ?>
                    </p>
                    <a href="place_order.php?crop_id=<?php echo $crop['crop_id']; ?>" class="btn">Place Order</a>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <style>
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 1.5rem;
        padding: 1rem;
    }

    .card {
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .full-width {
        grid-column: 1 / -1;
    }

    .button-group {
        display: flex;
        gap: 1rem;
        margin-top: 1rem;
    }

    .table-responsive {
        overflow-x: auto;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 1rem;
    }

    th, td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    th {
        background-color: #f8f9fa;
        font-weight: 600;
    }

    .btn-small {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }

    .status-badge {
        display: inline-block;
        padding: 0.25rem 0.75rem;
        border-radius: 1rem;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .status-badge.pending { background-color: #fff3cd; color: #856404; }
    .status-badge.confirmed { background-color: #d4edda; color: #155724; }
    .status-badge.completed { background-color: #cce5ff; color: #004085; }
    .status-badge.cancelled { background-color: #f8d7da; color: #721c24; }

    @media (max-width: 768px) {
        .button-group {
            flex-direction: column;
        }
        
        .btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }
    }

    .marketplace-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 1rem;
    }

    .crop-card {
        background: white;
        padding: 1.5rem;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .crop-card h4 {
        margin: 0 0 1rem;
        color: var(--primary-color);
    }

    .farm-info, .crop-details {
        margin-bottom: 1rem;
    }

    .crop-card .btn {
        width: 100%;
        margin-top: 1rem;
    }
    </style>
</body>
</html> 