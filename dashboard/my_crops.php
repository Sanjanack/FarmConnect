<?php
session_start();
require_once '../db_connect.php';

// Check if user is logged in and is a farmer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'farmer') {
    header("Location: ../login.php");
    exit();
}

include '../includes/dashboard_header.php';

// Initialize variables
$crops = null;
$error = '';

try {
    // Get farmer's ID
    $stmt = $conn->prepare("SELECT farmer_id FROM farmer WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $farmer = $result->fetch_assoc();
    
    if ($farmer) {
        // Get all crops for the farmer
        $stmt = $conn->prepare("
            SELECT 
                c.*,
                COALESCE(SUM(o.quantity), 0) as total_sold,
                COUNT(DISTINCT o.order_id) as order_count
            FROM crops c
            LEFT JOIN orders o ON c.crop_id = o.crop_id
            WHERE c.farmer_id = ?
            GROUP BY c.crop_id
            ORDER BY c.listing_date DESC
        ");
        $stmt->bind_param("i", $farmer['farmer_id']);
        $stmt->execute();
        $crops = $stmt->get_result();
    }
} catch (Exception $e) {
    error_log("My Crops page error: " . $e->getMessage());
    $error = "An error occurred while loading your crops.";
}
?>

<div class="container">
    <div class="page-header">
        <h1>My Crops</h1>
        <a href="add_crop.php" class="btn btn-primary">Add New Crop</a>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <?php if ($crops && $crops->num_rows > 0): ?>
        <div class="crops-grid">
            <?php while ($crop = $crops->fetch_assoc()): ?>
                <div class="crop-card">
                    <div class="crop-status status-<?php echo $crop['status']; ?>">
                        <?php echo ucfirst($crop['status']); ?>
                    </div>
                    <div class="crop-details">
                        <h3><?php echo htmlspecialchars($crop['crop_name']); ?></h3>
                        <p class="description"><?php echo htmlspecialchars($crop['description']); ?></p>
                        <div class="crop-info">
                            <div class="info-item">
                                <span class="label">Quantity:</span>
                                <span class="value"><?php echo $crop['quantity'] . ' ' . $crop['unit']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Price:</span>
                                <span class="value">₹<?php echo number_format($crop['price_per_unit'], 2); ?>/<?php echo $crop['unit']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Quality:</span>
                                <span class="value">Standard</span>
                            </div>
                            <div class="info-item">
                                <span class="label">Total Sold:</span>
                                <span class="value"><?php echo $crop['total_sold'] . ' ' . $crop['unit']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Orders:</span>
                                <span class="value"><?php echo $crop['order_count']; ?></span>
                            </div>
                            <div class="info-item">
                                <span class="label">Listed On:</span>
                                <span class="value"><?php echo date('M j, Y', strtotime($crop['listing_date'])); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="no-data-message">
            <p>You haven't listed any crops yet. Start selling by adding your first crop!</p>
            <a href="add_crop.php" class="btn btn-primary">Add New Crop</a>
        </div>
    <?php endif; ?>
</div>

<style>
.container {
    padding: 20px;
}

.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.page-header h1 {
    margin: 0;
    color: #333;
}

.crops-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    margin-top: 20px;
}

.crop-card {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    position: relative;
}

.crop-status {
    position: absolute;
    top: 10px;
    right: 10px;
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
}

.status-available {
    background: #e8f5e9;
    color: #2e7d32;
}

.status-sold {
    background: #ffebee;
    color: #c62828;
}

.status-reserved {
    background: #fff3e0;
    color: #e65100;
}

.crop-details {
    padding: 20px;
}

.crop-details h3 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 18px;
}

.description {
    color: #666;
    margin-bottom: 15px;
    font-size: 14px;
}

.crop-info {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
}

.info-item {
    font-size: 14px;
}

.info-item .label {
    color: #666;
    margin-right: 5px;
}

.info-item .value {
    color: #333;
    font-weight: 500;
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
</style>

<?php include '../includes/dashboard_footer.php'; ?> 