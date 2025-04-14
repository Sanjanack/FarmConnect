<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is a buyer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'buyer') {
    header("Location: login.php");
    exit();
}

// Get buyer details
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT buyer_id, company_name FROM buyer WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$buyer = $stmt->get_result()->fetch_assoc();
$buyer_id = $buyer['buyer_id'];

// Get available crops
$stmt = $conn->prepare("
    SELECT c.*, f.farm_name, f.location 
    FROM crops c
    JOIN farmer f ON c.farmer_id = f.farmer_id
    WHERE c.status = 'available'
    ORDER BY c.listing_date DESC
");
$stmt->execute();
$available_crops = $stmt->get_result();

// Get buyer's orders
$stmt = $conn->prepare("
    SELECT o.*, c.crop_name, c.price as unit_price, f.farm_name
    FROM orders o
    JOIN crops c ON o.crop_id = c.crop_id
    JOIN farmer f ON c.farmer_id = f.farmer_id
    WHERE o.buyer_id = ?
    ORDER BY o.order_date DESC
");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buyer Dashboard - FarmConnect</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <nav class="navbar">
        <ul>
            <li><a href="buyer_dashboard.php">Dashboard</a></li>
            <li><a href="marketplace.php">Marketplace</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>

        <div class="dashboard-cards">
            <div class="card">
                <h3>Available Crops</h3>
                <?php if ($available_crops->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Crop</th>
                                <th>Farm</th>
                                <th>Location</th>
                                <th>Quantity (kg)</th>
                                <th>Price/kg</th>
                                <th>Quality</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($crop = $available_crops->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($crop['crop_name']); ?></td>
                                    <td><?php echo htmlspecialchars($crop['farm_name']); ?></td>
                                    <td><?php echo htmlspecialchars($crop['location']); ?></td>
                                    <td><?php echo htmlspecialchars($crop['c_quantity']); ?></td>
                                    <td>$<?php echo htmlspecialchars($crop['price']); ?></td>
                                    <td><?php echo htmlspecialchars($crop['quality']); ?></td>
                                    <td>
                                        <a href="place_order.php?crop_id=<?php echo $crop['crop_id']; ?>" class="btn">Order Now</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No crops available at the moment.</p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>Your Orders</h3>
                <?php if ($orders->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Crop</th>
                                <th>Farm</th>
                                <th>Quantity</th>
                                <th>Total Price</th>
                                <th>Status</th>
                                <th>Order Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $orders->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($order['order_id']); ?></td>
                                    <td><?php echo htmlspecialchars($order['crop_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['farm_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['quantity']); ?> kg</td>
                                    <td>$<?php echo htmlspecialchars($order['total_price']); ?></td>
                                    <td><?php echo htmlspecialchars($order['status']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No orders placed yet.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 