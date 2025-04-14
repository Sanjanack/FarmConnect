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

// Handle search and filters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'latest';

// Add this PHP code near the top after session_start()
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

// Add to cart handler
if (isset($_POST['add_to_cart'])) {
    $crop_id = $_POST['crop_id'];
    $quantity = $_POST['quantity'];
    $price_per_unit = $_POST['price_per_unit'];
    $crop_name = $_POST['crop_name'];
    $unit = $_POST['unit'];
    $farmer_name = $_POST['farmer_name'];
    $farmer_location = $_POST['farmer_location'];
    $farmer_contact = $_POST['farmer_contact'];
    
    // Create unconfirmed order
    try {
        // Check if crop is still available
        $stmt = $conn->prepare("
            SELECT quantity FROM crops 
            WHERE crop_id = ? AND status = 'available' 
            AND quantity >= ?
        ");
        $stmt->bind_param("id", $crop_id, $quantity);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $_SESSION['error_message'] = "Sorry, this crop is no longer available in the requested quantity.";
            header("Location: marketplace.php");
            exit();
        }
        
        // Create unconfirmed order
        $stmt = $conn->prepare("
            INSERT INTO orders (
                buyer_id, 
                crop_id, 
                quantity, 
                total_price, 
                order_date, 
                status
            ) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, 'pending')
        ");
        
        $total_price = $quantity * $price_per_unit;
        $stmt->bind_param("iidd", 
            $buyer_id,
            $crop_id,
            $quantity,
            $total_price
        );
        
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Item added to checkout!";
        } else {
            error_log("SQL Error: " . $stmt->error);
            throw new Exception("Failed to create order: " . $stmt->error);
        }
        
    } catch (Exception $e) {
        error_log("Error creating order: " . $e->getMessage());
        $_SESSION['error_message'] = "Failed to add item to checkout. Please try again.";
    }
    
    header("Location: marketplace.php");
    exit();
}

// Get unconfirmed orders count
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE buyer_id = ? AND status = 'pending'
");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$result = $stmt->get_result();
$pending_count = $result->fetch_assoc()['count'];

try {
    // Build query based on filters
    $query = "
        SELECT 
            c.*,
            CONCAT(f.FName, ' ', f.LName) as farmer_name,
            f.address as farmer_location,
            f.contact_number as farmer_contact,
            f.farming_experience
        FROM crops c
        JOIN farmer f ON c.farmer_id = f.farmer_id
        WHERE c.status = 'available'
    ";

    if (!empty($search)) {
        $query .= " AND (c.crop_name LIKE ? OR CONCAT(f.FName, ' ', f.LName) LIKE ?)";
        $search_param = "%$search%";
    }

    // Add sorting
    switch ($sort) {
        case 'price_low':
            $query .= " ORDER BY c.price_per_unit ASC";
            break;
        case 'price_high':
            $query .= " ORDER BY c.price_per_unit DESC";
            break;
        default:
            $query .= " ORDER BY c.listing_date DESC";
    }

    error_log("Marketplace Query: " . $query);
    
    $stmt = $conn->prepare($query);
    
    if (!empty($search)) {
        $stmt->bind_param("ss", $search_param, $search_param);
    }
    
    if (!$stmt->execute()) {
        error_log("Marketplace Query Error: " . $stmt->error);
        throw new Exception("Failed to execute query: " . $stmt->error);
    }
    
    $crops = $stmt->get_result();
    error_log("Number of crops found: " . $crops->num_rows);
    
} catch (Exception $e) {
    error_log("Marketplace error: " . $e->getMessage());
    $error = "An error occurred while loading the marketplace: " . $e->getMessage();
}
?>

<div class="container">
    <div class="page-header">
        <h1>Marketplace</h1>
        <p>Browse available crops from farmers</p>
    </div>

    <div class="marketplace-filters">
        <form method="GET" action="" class="filter-form">
            <div class="search-box">
                <input type="text" name="search" placeholder="Search crops..."
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>

            <div class="sort-box">
                <select name="sort" onchange="this.form.submit()">
                    <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Latest</option>
                    <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                </select>
            </div>
        </form>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php else: ?>
        <div class="crop-grid">
            <?php if ($crops->num_rows > 0): ?>
                <?php while ($crop = $crops->fetch_assoc()): ?>
                    <div class="crop-card">
                        <?php if ($crop['image_url']): ?>
                            <img src="../<?php echo htmlspecialchars($crop['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($crop['crop_name']); ?>"
                                 class="crop-image">
                        <?php else: ?>
                            <div class="crop-image-placeholder">
                                <span>No image available</span>
                            </div>
                        <?php endif; ?>

                        <div class="crop-info">
                            <h3><?php echo htmlspecialchars($crop['crop_name']); ?></h3>
                            <p class="farm-info">
                                <span class="farm-name"><?php echo htmlspecialchars($crop['farmer_name']); ?></span>
                                <span class="location"><?php echo htmlspecialchars($crop['farmer_location']); ?></span>
                            </p>
                            <p class="description"><?php echo htmlspecialchars($crop['description']); ?></p>
                            <div class="crop-meta">
                                <span class="quantity">
                                    <?php echo number_format($crop['quantity']) . ' ' . htmlspecialchars($crop['unit']); ?> available
                                </span>
                                <span class="price">₹<?php echo number_format($crop['price_per_unit']); ?>/<?php echo htmlspecialchars($crop['unit']); ?></span>
                            </div>
                            <button class="btn btn-primary buy-btn" 
                                    onclick="showBuyModal(<?php echo htmlspecialchars(json_encode($crop)); ?>)">
                                Buy Now
                            </button>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="no-results">
                    <p>No crops available at the moment.</p>
                    <?php if (!empty($search)): ?>
                        <p>Try adjusting your search criteria.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Buy Modal -->
<div id="buyModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Purchase Crop</h2>
        <form method="POST" id="buyForm">
            <input type="hidden" name="add_to_cart" value="1">
            <input type="hidden" name="crop_id" id="cropId">
            <input type="hidden" name="price_per_unit" id="pricePerUnit">
            <input type="hidden" name="crop_name" id="cropName">
            <input type="hidden" name="unit" id="unit">
            <input type="hidden" name="farmer_name" id="farmerName">
            <input type="hidden" name="farmer_location" id="farmerLocation">
            <input type="hidden" name="farmer_contact" id="farmerContact">
            
            <div class="form-group">
                <label for="quantity">Quantity:</label>
                <input type="number" class="form-control" id="quantity" name="quantity" min="1" required>
                <small class="text-muted">Available: <span id="availableQuantity"></span></small>
            </div>
            
            <div class="form-group">
                <label>Total Price:</label>
                <h4 id="totalPrice">₹0.00</h4>
            </div>
            
            <button type="submit" class="btn btn-success">Add to Cart</button>
        </form>
    </div>
</div>

<!-- Update the cart icon -->
<div class="cart-icon" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
    <a href="cart.php" class="btn btn-success">
        <i class="fas fa-shopping-cart"></i> 
        Cart (<?php echo $pending_count; ?>)
    </a>
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

.marketplace-filters {
    margin-bottom: 30px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 20px;
}

.filter-form {
    display: flex;
    gap: 20px;
    width: 100%;
}

.search-box {
    flex: 1;
    display: flex;
    gap: 10px;
}

.search-box input {
    flex: 1;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
}

.sort-box select {
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 16px;
    min-width: 200px;
}

.crop-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.crop-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    overflow: hidden;
    transition: transform 0.2s;
}

.crop-card:hover {
    transform: translateY(-2px);
}

.crop-image,
.crop-image-placeholder {
    width: 100%;
    height: 200px;
    object-fit: cover;
    background: #f5f5f5;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #666;
}

.crop-info {
    padding: 20px;
}

.crop-info h3 {
    margin: 0 0 10px 0;
    color: #333;
}

.farm-info {
    color: #666;
    font-size: 14px;
    margin-bottom: 10px;
}

.farm-info span:not(:last-child)::after {
    content: "•";
    margin: 0 5px;
}

.description {
    color: #444;
    margin-bottom: 15px;
    font-size: 14px;
}

.crop-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
    font-size: 14px;
}

.quantity {
    color: #666;
}

.price {
    color: #4CAF50;
    font-weight: 500;
}

.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 1000;
}

.modal-content {
    position: relative;
    background: white;
    margin: 10% auto;
    padding: 30px;
    width: 90%;
    max-width: 500px;
    border-radius: 8px;
}

.close {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.close:hover {
    color: #333;
}

.no-results {
    text-align: center;
    padding: 40px;
    background: white;
    border-radius: 8px;
    grid-column: 1 / -1;
}

.no-results p {
    color: #666;
    margin: 10px 0;
}

@media (max-width: 768px) {
    .marketplace-filters,
    .filter-form {
        flex-direction: column;
    }
    
    .sort-box select {
        width: 100%;
        min-width: unset;
    }
}

/* Add these styles for the cart icon */
.cart-icon {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 1000;
}

.cart-icon .btn {
    padding: 10px 20px;
    font-size: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
}

.cart-icon i {
    font-size: 18px;
}
</style>

<script>
let currentCrop = null;

function showBuyModal(crop) {
    currentCrop = crop;
    document.getElementById('buyModal').style.display = 'block';
    document.getElementById('cropId').value = crop.crop_id;
    document.getElementById('pricePerUnit').value = crop.price_per_unit;
    document.getElementById('cropName').value = crop.crop_name;
    document.getElementById('unit').value = crop.unit;
    document.getElementById('farmerName').value = crop.farmer_name;
    document.getElementById('farmerLocation').value = crop.farmer_location;
    document.getElementById('farmerContact').value = crop.farmer_contact;
    
    document.getElementById('availableQuantity').textContent = crop.quantity + ' ' + crop.unit;
    document.getElementById('quantity').max = crop.quantity;
    document.getElementById('quantity').value = '1';
    
    updateTotalPrice();
}

function closeBuyModal() {
    document.getElementById('buyModal').style.display = 'none';
    currentCrop = null;
}

function updateTotalPrice() {
    const quantity = parseFloat(document.getElementById('quantity').value) || 0;
    const pricePerUnit = parseFloat(currentCrop.price_per_unit) || 0;
    const total = quantity * pricePerUnit;
    document.getElementById('totalPrice').textContent = `₹${total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('buyModal');
    if (event.target === modal) {
        closeBuyModal();
    }
}

// Update total amount when quantity changes
document.getElementById('quantity').addEventListener('input', updateTotalPrice);

// Close modal when clicking the X
document.querySelector('.close').addEventListener('click', closeBuyModal);

// Form validation
document.getElementById('buyForm').addEventListener('submit', function(e) {
    const quantity = parseFloat(document.getElementById('quantity').value);
    const maxQuantity = parseFloat(currentCrop.quantity);
    
    if (quantity <= 0) {
        e.preventDefault();
        alert('Please enter a valid quantity');
    } else if (quantity > maxQuantity) {
        e.preventDefault();
        alert('Quantity cannot exceed available amount');
    }
});
</script>

<?php include '../includes/dashboard_footer.php'; ?> 