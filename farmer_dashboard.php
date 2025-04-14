<?php
session_start();
require_once 'db_connect.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'farmer') {
    header("Location: login.php");
    exit();
}

// Get farmer details
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT farmer_id, farm_name, location FROM farmer WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();
$farmer_id = $farmer['farmer_id'];

// Get farmer's crops
$stmt = $conn->prepare("
    SELECT * FROM crops 
    WHERE farmer_id = ? 
    ORDER BY listing_date DESC
");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$crops = $stmt->get_result();

// Get pending orders
$stmt = $conn->prepare("
    SELECT o.*, c.crop_name, b.company_name 
    FROM orders o
    JOIN crops c ON o.crop_id = c.crop_id
    JOIN buyer b ON o.buyer_id = b.buyer_id
    WHERE c.farmer_id = ? AND o.status = 'pending'
    ORDER BY o.order_date DESC
");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$pending_orders = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Dashboard - FarmConnect</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <nav class="navbar">
        <ul>
            <li><a href="farmer_dashboard.php">Dashboard</a></li>
            <li><a href="add_crop.php">Add New Crop</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>

        <div class="dashboard-cards">
            <div class="card">
                <h3>Your Crops</h3>
                <?php if ($crops->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Crop Name</th>
                                <th>Quantity</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($crop = $crops->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($crop['crop_name']); ?></td>
                                    <td><?php echo htmlspecialchars($crop['c_quantity']); ?></td>
                                    <td>$<?php echo htmlspecialchars($crop['price']); ?></td>
                                    <td><?php echo htmlspecialchars($crop['status']); ?></td>
                                    <td>
                                        <a href="edit_crop.php?id=<?php echo $crop['crop_id']; ?>" class="btn">Edit</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No crops listed yet. <a href="add_crop.php">Add your first crop</a></p>
                <?php endif; ?>
            </div>

            <div class="card">
                <h3>Pending Orders</h3>
                <?php if ($pending_orders->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Crop</th>
                                <th>Buyer</th>
                                <th>Quantity</th>
                                <th>Total Price</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $pending_orders->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($order['crop_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['company_name']); ?></td>
                                    <td><?php echo htmlspecialchars($order['quantity']); ?></td>
                                    <td>$<?php echo htmlspecialchars($order['total_price']); ?></td>
                                    <td>
                                        <a href="process_order.php?id=<?php echo $order['order_id']; ?>&action=accept" class="btn">Accept</a>
                                        <a href="process_order.php?id=<?php echo $order['order_id']; ?>&action=reject" class="btn" style="background-color: var(--error-color);">Reject</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No pending orders.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 