<?php
session_start();
require_once '../db_connect.php';
include '../includes/dashboard_header.php';

// Check if user is a farmer
if ($_SESSION['user_type'] !== 'farmer') {
    header("Location: index.php");
    exit();
}

// Get farmer's ID
$stmt = $conn->prepare("SELECT farmer_id FROM farmer WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$farmer = $result->fetch_assoc();

if (!$farmer) {
    die("Farmer profile not found. Please complete your profile first.");
}

$farmer_id = $farmer['farmer_id'];
$error = '';
$orders = null;
$debug_info = '';

// Get status filter
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

try {
    // Main query
    $query = "
        SELECT DISTINCT
            o.*,
            c.crop_name,
            c.unit,
            c.price_per_unit,
            b.name as buyer_name,
            b.contact_number as buyer_contact,
            b.address as buyer_address
        FROM crops c
        INNER JOIN orders o ON c.crop_id = o.crop_id
        INNER JOIN buyer b ON o.buyer_id = b.buyer_id
        WHERE c.farmer_id = ?";
    
    if ($status_filter !== 'all') {
        $query .= " AND o.status = ?";
    }
    
    $query .= " ORDER BY o.order_date DESC, o.order_id DESC";

    $stmt = $conn->prepare($query);
    
    if ($status_filter !== 'all') {
        $stmt->bind_param("is", $farmer_id, $status_filter);
    } else {
        $stmt->bind_param("i", $farmer_id);
    }

    if (!$stmt->execute()) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }
    
    $orders = $stmt->get_result();
    
    if (!$orders) {
        throw new Exception("Failed to get results: " . $conn->error);
    }

    // Remove debug information
    $debug_info = "";
} catch (Exception $e) {
    error_log("Error loading orders: " . $e->getMessage());
    $error = "An error occurred while loading orders: " . $e->getMessage();
}
?>

<div class="container">
    <div class="page-header">
        <h1>Recent Orders</h1>
        <p>View and manage orders for your crops</p>
        
        <div class="status-filter">
            <a href="?status=all" class="filter-btn <?php echo (!isset($_GET['status']) || $_GET['status'] === 'all') ? 'active' : ''; ?>">All Orders</a>
            <a href="?status=pending" class="filter-btn <?php echo (isset($_GET['status']) && $_GET['status'] === 'pending') ? 'active' : ''; ?>">Pending</a>
            <a href="?status=confirmed" class="filter-btn <?php echo (isset($_GET['status']) && $_GET['status'] === 'confirmed') ? 'active' : ''; ?>">Confirmed</a>
            <a href="?status=completed" class="filter-btn <?php echo (isset($_GET['status']) && $_GET['status'] === 'completed') ? 'active' : ''; ?>">Completed</a>
            <a href="?status=cancelled" class="filter-btn <?php echo (isset($_GET['status']) && $_GET['status'] === 'cancelled') ? 'active' : ''; ?>">Cancelled</a>
        </div>
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

    <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <?php if ($orders && $orders->num_rows > 0): ?>
        <div class="orders-grid">
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
                    </div>

                    <?php if ($order['status'] !== 'pending' && $order['status'] !== 'cancelled'): ?>
                    <div class="supply-chain-status">
                        <h4>Supply Chain Status</h4>
                        <form method="POST" action="update_supply_chain.php">
                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                            <select name="status" class="status-select" onchange="this.form.submit()">
                                <option value="confirmed" <?php echo $order['status'] === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="processing" <?php echo $order['status'] === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="packed" <?php echo $order['status'] === 'packed' ? 'selected' : ''; ?>>Packed</option>
                                <option value="shipped" <?php echo $order['status'] === 'shipped' ? 'selected' : ''; ?>>Shipped</option>
                                <option value="in_transit" <?php echo $order['status'] === 'in_transit' ? 'selected' : ''; ?>>In Transit</option>
                                <option value="out_for_delivery" <?php echo $order['status'] === 'out_for_delivery' ? 'selected' : ''; ?>>Out for Delivery</option>
                                <option value="delivered" <?php echo $order['status'] === 'delivered' ? 'selected' : ''; ?>>Delivered</option>
                            </select>
                        </form>
                    </div>
                    <?php endif; ?>
                    
                    <div class="buyer-info">
                        <h4>Buyer Details</h4>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($order['buyer_name']); ?></p>
                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($order['buyer_contact']); ?></p>
                        <p><strong>Address:</strong> <?php echo htmlspecialchars($order['buyer_address']); ?></p>
                    </div>

                    <?php if ($order['status'] === 'pending'): ?>
                    <div class="order-actions">
                        <form method="POST" action="update_supply_chain.php" style="display: inline;">
                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                            <input type="hidden" name="status" value="confirmed">
                            <button type="submit" name="update_status" class="btn btn-primary">
                                Confirm Order
                            </button>
                        </form>
                        
                        <form method="POST" action="update_supply_chain.php" style="display: inline;">
                            <input type="hidden" name="order_id" value="<?php echo $order['order_id']; ?>">
                            <input type="hidden" name="status" value="cancelled">
                            <button type="submit" name="update_status" class="btn btn-danger" onclick="return confirm('Are you sure you want to cancel this order?')">
                                Cancel Order
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="no-orders">
            <p>No orders found for your crops.</p>
            <?php if (!empty($debug_info)): ?>
                <div class="debug-info">
                    <h4>Debug Information:</h4>
                    <?php echo $debug_info; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<style>
/* Reusing the same styles as my_orders.php */
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

.buyer-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-top: 15px;
}

.buyer-info h4 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 16px;
}

.buyer-info p {
    margin: 5px 0;
    font-size: 14px;
    color: #666;
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

.btn-complete {
    background-color: #28a745;
    color: white;
}

.btn-complete:hover {
    background-color: #218838;
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

.status-filter {
    margin-top: 20px;
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.filter-btn {
    padding: 8px 16px;
    border-radius: 20px;
    background: #f8f9fa;
    color: #666;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.2s;
}

.filter-btn:hover {
    background: #e9ecef;
    color: #333;
}

.filter-btn.active {
    background: #007bff;
    color: white;
}

.debug-info {
    margin-top: 20px;
    text-align: left;
    padding: 15px;
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 4px;
}

.debug-info h4 {
    color: #333;
    margin-bottom: 10px;
}

.supply-chain-status {
    margin: 15px 0;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 5px;
}

.supply-chain-status h4 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 16px;
}

.status-select {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
    background-color: white;
    cursor: pointer;
}

.status-select:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
}

.order-status.processing { background: #e3f2fd; color: #0d47a1; }
.order-status.packed { background: #f3e5f5; color: #4a148c; }
.order-status.shipped { background: #e8f5e9; color: #1b5e20; }
.order-status.in_transit { background: #fff3e0; color: #e65100; }
.order-status.out_for_delivery { background: #e0f2f1; color: #004d40; }
.order-status.delivered { background: #e8f5e9; color: #1b5e20; }
</style>

<?php include '../includes/dashboard_footer.php'; ?> 