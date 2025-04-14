<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../includes/dashboard_header.php';

// Initialize variables
$orders = null;
$error = '';

try {
    // Get orders based on user type
    if ($_SESSION['user_type'] === 'farmer') {
        $query = "
            SELECT 
                o.*,
                c.crop_name,
                c.unit,
                c.price_per_unit,
                b.name as buyer_name,
                b.contact_number as buyer_contact,
                b.address as buyer_address
            FROM orders o
            JOIN crops c ON o.crop_id = c.crop_id
            JOIN buyer b ON o.buyer_id = b.buyer_id
            JOIN farmer f ON c.farmer_id = f.farmer_id
            WHERE f.user_id = ?
            ORDER BY o.order_date DESC";
    } else {
        $query = "
            SELECT 
                o.*,
                c.crop_name,
                c.unit,
                c.price_per_unit,
                f.name as farmer_name,
                f.contact_number as farmer_contact,
                f.address as farmer_address
            FROM orders o
            JOIN crops c ON o.crop_id = c.crop_id
            JOIN farmer f ON c.farmer_id = f.farmer_id
            JOIN buyer b ON o.buyer_id = b.buyer_id
            WHERE b.user_id = ?
            ORDER BY o.order_date DESC";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $orders = $stmt->get_result();
} catch (Exception $e) {
    error_log("Orders page error: " . $e->getMessage());
    $error = "An error occurred while loading the orders.";
}
?>

<div class="container">
    <h1>My Orders</h1>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($orders && $orders->num_rows > 0): ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Crop</th>
                        <th><?php echo $_SESSION['user_type'] === 'farmer' ? 'Buyer' : 'Farmer'; ?></th>
                        <th>Quantity</th>
                        <th>Price per Unit</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Order Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td><?php echo htmlspecialchars($order['crop_name']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($_SESSION['user_type'] === 'farmer' ? 
                                    $order['buyer_name'] : $order['farmer_name']); ?>
                            </td>
                            <td><?php echo $order['quantity']; ?></td>
                            <td>₹<?php echo number_format($order['price_per_unit'], 2); ?></td>
                            <td>₹<?php echo number_format($order['total_price'], 2); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($order['order_date'])); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div class="no-data-message">
            <?php if ($_SESSION['user_type'] === 'farmer'): ?>
                <p>You haven't received any orders yet. Once buyers purchase your crops, their orders will appear here.</p>
            <?php else: ?>
                <p>You haven't placed any orders yet. Visit the marketplace to start purchasing crops!</p>
                <a href="marketplace.php" class="btn btn-primary">Browse Marketplace</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.container {
    padding: 20px;
}

h1 {
    margin-bottom: 30px;
    color: #333;
}

.table-responsive {
    overflow-x: auto;
    margin-top: 20px;
}

.table {
    width: 100%;
    border-collapse: collapse;
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.table th,
.table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #eee;
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    color: #333;
}

.table tbody tr:hover {
    background-color: #f5f5f5;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 14px;
}

.status-pending {
    background: #fff3e0;
    color: #e65100;
}

.status-confirmed {
    background: #e3f2fd;
    color: #1565c0;
}

.status-completed {
    background: #e8f5e9;
    color: #2e7d32;
}

.status-cancelled {
    background: #ffebee;
    color: #c62828;
}

.no-data-message {
    text-align: center;
    padding: 40px 20px;
    background: #f8f9fa;
    border-radius: 8px;
    margin: 20px 0;
}

.no-data-message p {
    color: #666;
    font-size: 16px;
    margin-bottom: 20px;
}

.btn-primary {
    background: #4CAF50;
    color: white;
    padding: 10px 20px;
    border-radius: 5px;
    text-decoration: none;
    display: inline-block;
    transition: background 0.3s ease;
}

.btn-primary:hover {
    background: #388E3C;
}
</style>

<?php include '../includes/dashboard_footer.php'; ?> 