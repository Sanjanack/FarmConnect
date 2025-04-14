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

// Get farmer's crops
$crops_query = "SELECT * FROM crops WHERE farmer_id = ? ORDER BY listing_date DESC";
$stmt = $conn->prepare($crops_query);
$stmt->bind_param("i", $farmer['farmer_id']);
$stmt->execute();
$crops_result = $stmt->get_result();

// Get recent orders
$orders_query = "SELECT o.*, c.crop_name, c.unit, b.company_name as buyer_name 
                FROM orders o 
                JOIN crops c ON o.crop_id = c.crop_id 
                JOIN buyer b ON o.buyer_id = b.buyer_id 
                WHERE c.farmer_id = ? 
                ORDER BY o.order_date DESC 
                LIMIT 5";
$stmt = $conn->prepare($orders_query);
$stmt->bind_param("i", $farmer['farmer_id']);
$stmt->execute();
$orders_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard - FarmConnect</title>
    <link rel="stylesheet" href="../../css/styles.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>

    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($farmer['name']); ?>!</h2>

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
                <h3>Profile Summary</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($farmer['name']); ?></p>
                <p><strong>Contact:</strong> <?php echo htmlspecialchars($farmer['contact_number']); ?></p>
                <p><strong>Experience:</strong> <?php echo htmlspecialchars($farmer['farming_experience']); ?> years</p>
                <a href="edit_profile.php" class="btn btn-secondary">Edit Profile</a>
            </div>

            <!-- Quick Actions -->
            <div class="card">
                <h3>Quick Actions</h3>
                <div class="button-group">
                    <a href="add_crop.php" class="btn">Add New Crop</a>
                    <a href="view_orders.php" class="btn btn-secondary">View All Orders</a>
                </div>
            </div>

            <!-- Listed Crops -->
            <div class="card full-width">
                <h3>Your Listed Crops</h3>
                <?php if ($crops_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Crop Name</th>
                                    <th>Quantity</th>
                                    <th>Price/Unit</th>
                                    <th>Quality Grade</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($crop = $crops_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($crop['crop_name']); ?></td>
                                        <td><?php echo htmlspecialchars($crop['quantity']) . ' ' . htmlspecialchars($crop['unit']); ?></td>
                                        <td>₹<?php echo htmlspecialchars($crop['price_per_unit']); ?></td>
                                        <td><?php echo htmlspecialchars($crop['quality_grade']); ?></td>
                                        <td><span class="status-badge <?php echo strtolower($crop['status']); ?>"><?php echo ucfirst(htmlspecialchars($crop['status'])); ?></span></td>
                                        <td>
                                            <a href="edit_crop.php?id=<?php echo $crop['crop_id']; ?>" class="btn btn-small">Edit</a>
                                            <?php if ($crop['status'] === 'available'): ?>
                                                <a href="delete_crop.php?id=<?php echo $crop['crop_id']; ?>" class="btn btn-small btn-danger" onclick="return confirm('Are you sure you want to delete this crop?')">Delete</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No crops listed yet. <a href="add_crop.php">Add your first crop</a></p>
                <?php endif; ?>
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
                                    <th>Buyer</th>
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
                                        <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['crop_name']); ?></td>
                                        <td><?php echo htmlspecialchars($order['quantity']) . ' ' . htmlspecialchars($order['unit']); ?></td>
                                        <td>₹<?php echo htmlspecialchars($order['total_price']); ?></td>
                                        <td><span class="status-badge <?php echo strtolower($order['status']); ?>"><?php echo ucfirst(htmlspecialchars($order['status'])); ?></span></td>
                                        <td>
                                            <a href="view_order.php?id=<?php echo $order['order_id']; ?>" class="btn btn-small">View</a>
                                            <?php if ($order['status'] === 'pending'): ?>
                                                <a href="update_order.php?id=<?php echo $order['order_id']; ?>&action=confirm" class="btn btn-small">Confirm</a>
                                                <a href="update_order.php?id=<?php echo $order['order_id']; ?>&action=cancel" class="btn btn-small btn-danger" onclick="return confirm('Are you sure you want to cancel this order?')">Cancel</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No orders yet.</p>
                <?php endif; ?>
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

    .status-badge.available { background-color: #d4edda; color: #155724; }
    .status-badge.reserved { background-color: #fff3cd; color: #856404; }
    .status-badge.sold { background-color: #cce5ff; color: #004085; }
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
    </style>
</body>
</html> 