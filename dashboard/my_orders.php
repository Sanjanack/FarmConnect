<?php
session_start();
require_once '../db_connect.php';

// Include the dashboard header
include '../includes/dashboard_header.php';

// Check if user is a buyer
if ($_SESSION['user_type'] !== 'buyer') {
    header("Location: index.php");
    exit();
}

// Get buyer's ID
$stmt = $conn->prepare("SELECT buyer_id FROM buyer WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$buyer = $result->fetch_assoc();

if (!$buyer) {
    die("Buyer profile not found. Please complete your profile first.");
}

$buyer_id = $buyer['buyer_id'];

try {
    $query = "
        SELECT 
            o.*,
            c.crop_name,
            c.unit,
            c.price_per_unit,
            CONCAT(f.FName, ' ', f.LName) as farmer_name,
            f.contact_number as farmer_contact,
            f.address as farmer_address
        FROM orders o
        JOIN crops c ON o.crop_id = c.crop_id
        JOIN farmer f ON c.farmer_id = f.farmer_id
        WHERE o.buyer_id = ?
        ORDER BY o.order_date DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $buyer_id);
    $stmt->execute();
    $orders = $stmt->get_result();
    
} catch (Exception $e) {
    $error = "An error occurred while loading your orders.";
}
?>

<div class="container">
    <div class="page-header">
        <h1>My Orders</h1>
        <p>View and manage your crop orders</p>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error; ?>
        </div>
    <?php else: ?>
        <div class="orders-grid">
            <?php if ($orders->num_rows > 0): ?>
                <?php while ($order = $orders->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <h3><?php echo htmlspecialchars($order['crop_name']); ?></h3>
                            <span class="order-status <?php echo strtolower($order['status']); ?>">
                                <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                            </span>
                        </div>
                        
                        <div class="order-details">
                            <div class="detail-row">
                                <span class="label">Order ID:</span>
                                <span class="value">#<?php echo $order['order_id']; ?></span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="label">Quantity:</span>
                                <span class="value"><?php echo number_format($order['quantity']) . ' ' . htmlspecialchars($order['unit']); ?></span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="label">Price per unit:</span>
                                <span class="value">₹<?php echo number_format($order['price_per_unit'], 2); ?></span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="label">Total Amount:</span>
                                <span class="value total-amount">₹<?php echo number_format($order['total_price'], 2); ?></span>
                            </div>
                            
                            <div class="detail-row">
                                <span class="label">Order Date:</span>
                                <span class="value"><?php echo date('M j, Y g:i A', strtotime($order['order_date'])); ?></span>
                            </div>

                            <?php if ($order['status'] === 'pending'): ?>
                                <div class="order-actions">
                                    <a href="cart.php" class="btn btn-primary">
                                        <i class="fas fa-shopping-cart"></i> Go to Cart
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="farmer-info">
                            <h4>Farmer Details</h4>
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['farmer_name']); ?></p>
                            <p><strong>Contact:</strong> <?php echo htmlspecialchars($order['farmer_contact']); ?></p>
                            <p><strong>Address:</strong> <?php echo htmlspecialchars($order['farmer_address']); ?></p>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-orders">
                    <p>You haven't placed any orders yet.</p>
                    <a href="marketplace.php" class="btn btn-primary">Browse Marketplace</a>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
.container {
    padding: 20px;
    max-width: 1200px;
    margin: 0 auto;
}

.page-header {
    margin-bottom: 30px;
}

.page-header h1 {
    margin: 0 0 10px 0;
    color: #333;
}

.page-header p {
    color: #666;
    font-size: 16px;
}

.alert {
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideIn 0.3s ease-out;
}

.alert i {
    font-size: 20px;
}

.alert-success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.alert-error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.orders-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}

.order-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 20px;
}

.order-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.order-header h3 {
    margin: 0;
    color: #333;
}

.order-status {
    padding: 5px 10px;
    border-radius: 15px;
    font-size: 14px;
    font-weight: 500;
}

.order-status.pending {
    background: #fff3cd;
    color: #856404;
}

.order-status.confirmed {
    background: #cce5ff;
    color: #004085;
}

.order-status.completed {
    background: #d4edda;
    color: #155724;
}

.order-status.cancelled {
    background: #f8d7da;
    color: #721c24;
}

.order-details {
    margin-bottom: 20px;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 10px;
    font-size: 14px;
}

.detail-row .label {
    color: #666;
}

.detail-row .value {
    font-weight: 500;
    color: #333;
}

.total-amount {
    color: #28a745;
    font-size: 16px;
}

.farmer-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-top: 15px;
}

.farmer-info h4 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 16px;
}

.farmer-info p {
    margin: 5px 0;
    font-size: 14px;
    color: #666;
}

.no-orders {
    text-align: center;
    padding: 40px;
    background: white;
    border-radius: 8px;
    grid-column: 1 / -1;
}

.no-orders p {
    color: #666;
    margin-bottom: 20px;
}

@keyframes slideIn {
    from {
        transform: translateY(-20px);
        opacity: 0;
    }
    to {
        transform: translateY(0);
        opacity: 1;
    }
}

@media (max-width: 768px) {
    .orders-grid {
        grid-template-columns: 1fr;
    }
}

.order-actions {
    margin-top: 20px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.status-form {
    display: flex;
    gap: 10px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 8px 16px;
    border: none;
    border-radius: 5px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s, transform 0.1s;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn:active {
    transform: translateY(0);
}

.btn i {
    font-size: 12px;
}

.btn-confirm {
    background-color: #28a745;
    color: white;
}

.btn-confirm:hover {
    background-color: #218838;
}

.btn-cancel {
    background-color: #dc3545;
    color: white;
}

.btn-cancel:hover {
    background-color: #c82333;
}
</style>

<?php include '../includes/dashboard_footer.php'; ?> 