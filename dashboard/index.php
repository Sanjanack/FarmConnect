<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Include the dashboard header
include '../includes/dashboard_header.php';

// Initialize variables
$total_crops = 0;
$total_orders = 0;
$earnings = 0;
$stats = [
    'pending_orders' => 0,
    'total_spent' => 0
];
$recent_orders = null;

// Get user data and statistics
try {
    if ($_SESSION['user_type'] === 'farmer') {
        // Get farmer's ID
        $stmt = $conn->prepare("SELECT farmer_id FROM farmer WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $farmer = $result->fetch_assoc();
        
        if ($farmer) {
            $farmer_id = $farmer['farmer_id'];

            // Get total crops
            $crops_query = "SELECT COUNT(*) as total_crops FROM crops WHERE farmer_id = ?";
            $stmt = $conn->prepare($crops_query);
            $stmt->bind_param("i", $farmer_id);
            $stmt->execute();
            $crops_result = $stmt->get_result();
            $total_crops = $crops_result->fetch_assoc()['total_crops'] ?? 0;

            // Get total orders and earnings
            $stats_query = "SELECT 
                COUNT(DISTINCT o.order_id) as total_orders,
                COALESCE(SUM(CASE WHEN o.status = 'confirmed' THEN o.total_price ELSE 0 END), 0) as total_earnings,
                COUNT(CASE WHEN o.status = 'pending' THEN 1 END) as pending_orders
                FROM orders o
                JOIN crops c ON o.crop_id = c.crop_id
                WHERE c.farmer_id = ?";
            $stmt = $conn->prepare($stats_query);
            $stmt->bind_param("i", $farmer_id);
            $stmt->execute();
            $stats_result = $stmt->get_result()->fetch_assoc();
            
            $total_orders = $stats_result['total_orders'] ?? 0;
            $earnings = $stats_result['total_earnings'] ?? 0;
            $stats['pending_orders'] = $stats_result['pending_orders'] ?? 0;

            // Get recent orders
            $stmt = $conn->prepare("
                SELECT o.*, c.crop_name, b.company_name as buyer_name
                FROM orders o
                JOIN crops c ON o.crop_id = c.crop_id
                JOIN buyer b ON o.buyer_id = b.buyer_id
                WHERE c.farmer_id = ?
                ORDER BY o.order_date DESC
                LIMIT 5
            ");
            $stmt->bind_param("i", $farmer_id);
            $stmt->execute();
            $recent_orders = $stmt->get_result();
        }
    } else {
        // Get buyer's ID
        $stmt = $conn->prepare("SELECT buyer_id, contact_number, address FROM buyer WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $buyer = $result->fetch_assoc();
        
        // Check if buyer profile is incomplete
        if ($_SESSION['user_type'] === 'buyer' && 
            (!$buyer['contact_number'] || !$buyer['address'])) {
            $_SESSION['profile_incomplete'] = true;
            header("Location: profile.php");
            exit();
        }
        
        if ($buyer) {
            $buyer_id = $buyer['buyer_id'];

            // Get buyer statistics
            $stmt = $conn->prepare("
                SELECT 
                    COUNT(DISTINCT order_id) as total_orders,
                    COALESCE(SUM(CASE WHEN status = 'completed' THEN total_price ELSE 0 END), 0) as total_spent,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_orders
                FROM orders
                WHERE buyer_id = ?
            ");
            $stmt->bind_param("i", $buyer_id);
            $stmt->execute();
            $stats = $stmt->get_result()->fetch_assoc();

            // Get recent orders
            $stmt = $conn->prepare("
                SELECT o.*, c.crop_name, f.name as farmer_name
                FROM orders o
                JOIN crops c ON o.crop_id = c.crop_id
                JOIN farmer f ON c.farmer_id = f.farmer_id
                WHERE o.buyer_id = ?
                ORDER BY o.order_date DESC
                LIMIT 5
            ");
            $stmt->bind_param("i", $buyer_id);
            $stmt->execute();
            $recent_orders = $stmt->get_result();
        }
    }
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
}
?>

<div class="dashboard-header">
    <h1>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></h1>
    <p>Here's your overview</p>
</div>

<div class="dashboard-stats">
    <?php if ($_SESSION['user_type'] === 'farmer'): ?>
        <div class="stat-card">
            <h3>Total Crops</h3>
            <div class="number"><?php echo $total_crops; ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Orders</h3>
            <div class="number"><?php echo $total_orders; ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Earnings</h3>
            <div class="number">₹<?php echo number_format($earnings, 2); ?></div>
        </div>
        <div class="stat-card">
            <h3>Pending Orders</h3>
            <div class="number"><?php echo $stats['pending_orders'] ?? 0; ?></div>
        </div>
    <?php else: ?>
        <div class="stat-card">
            <h3>Total Orders</h3>
            <div class="number"><?php echo $stats['total_orders'] ?? 0; ?></div>
        </div>
        <div class="stat-card">
            <h3>Total Spent</h3>
            <div class="number">₹<?php echo number_format($stats['total_spent'] ?? 0); ?></div>
        </div>
        <div class="stat-card">
            <h3>Pending Orders</h3>
            <div class="number"><?php echo $stats['pending_orders'] ?? 0; ?></div>
        </div>
    <?php endif; ?>
</div>

<div class="dashboard-actions">
    <?php if ($_SESSION['user_type'] === 'farmer'): ?>
        <div class="welcome-message">
            <?php if ($total_crops == 0): ?>
                <p>You haven't listed any crops yet. Start selling by adding your first crop!</p>
            <?php endif; ?>
            <a href="add_crop.php" class="btn btn-primary">Add New Crop</a>
        </div>
    <?php else: ?>
        <div class="welcome-message">
            <?php if ($stats['total_orders'] == 0): ?>
                <p>You haven't made any purchases yet. Start exploring our marketplace!</p>
            <?php endif; ?>
            <a href="marketplace.php" class="btn btn-primary">Browse Marketplace</a>
        </div>
    <?php endif; ?>
</div>

<div class="recent-orders">
    <h2>Recent Orders</h2>
    <div class="table-container">
        <?php if ($recent_orders && $recent_orders->num_rows > 0): ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Crop</th>
                        <th><?php echo $_SESSION['user_type'] === 'farmer' ? 'Buyer' : 'Farmer'; ?></th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($order = $recent_orders->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $order['order_id']; ?></td>
                            <td><?php echo htmlspecialchars($order['crop_name']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($_SESSION['user_type'] === 'farmer' ? 
                                    $order['buyer_name'] : $order['farmer_name']); ?>
                            </td>
                            <td>₹<?php echo number_format($order['total_price']); ?></td>
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
        <?php else: ?>
            <div class="no-data-message">
                <?php if ($_SESSION['user_type'] === 'farmer'): ?>
                    <p>No orders yet. Once buyers purchase your crops, they will appear here.</p>
                <?php else: ?>
                    <p>No orders yet. Start shopping in the marketplace to see your order history here.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.welcome-message {
    text-align: center;
    margin: 20px 0;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 8px;
}

.welcome-message p {
    color: #666;
    margin-bottom: 15px;
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
    margin: 0;
}

.dashboard-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.stat-card h3 {
    color: #666;
    margin-bottom: 10px;
    font-size: 16px;
}

.stat-card .number {
    font-size: 24px;
    font-weight: bold;
    color: #2e7d32;
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
</style>

<?php include '../includes/dashboard_footer.php'; ?> 