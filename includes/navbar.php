<?php
if (!isset($_SESSION)) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>

<nav class="navbar">
    <div class="container">
        <a href="<?php echo isset($_SESSION['user_id']) ? 'dashboard/index.php' : 'index.php'; ?>" class="navbar-brand">
            FarmConnect
        </a>
        
        <button class="mobile-menu-toggle" aria-label="Toggle menu" aria-expanded="false">
            <span></span>
            <span></span>
            <span></span>
        </button>

        <ul class="nav-links">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <li>
                    <a href="index.php" class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">
                        Home
                    </a>
                </li>
                <li>
                    <a href="login.php" class="<?php echo $current_page === 'login' ? 'active' : ''; ?>">
                        Login
                    </a>
                </li>
                <li>
                    <a href="register.php" class="<?php echo $current_page === 'register' ? 'active' : ''; ?>">
                        Register
                    </a>
                </li>
            <?php else: ?>
                <li>
                    <a href="dashboard/index.php" class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">
                        Dashboard
                    </a>
                </li>
                <?php if ($_SESSION['user_type'] === 'farmer'): ?>
                    <li>
                        <a href="dashboard/add_crop.php" class="<?php echo $current_page === 'add_crop' ? 'active' : ''; ?>">
                            Add Crop
                        </a>
                    </li>
                    <li>
                        <a href="dashboard/my_crops.php" class="<?php echo $current_page === 'my_crops' ? 'active' : ''; ?>">
                            My Crops
                        </a>
                    </li>
                <?php else: ?>
                    <li>
                        <a href="dashboard/marketplace.php" class="<?php echo $current_page === 'marketplace' ? 'active' : ''; ?>">
                            Marketplace
                        </a>
                    </li>
                <?php endif; ?>
                <li>
                    <a href="dashboard/orders.php" class="<?php echo $current_page === 'orders' ? 'active' : ''; ?>">
                        Orders
                    </a>
                </li>
                <li>
                    <a href="dashboard/profile.php" class="<?php echo $current_page === 'profile' ? 'active' : ''; ?>">
                        Profile
                    </a>
                </li>
                <li>
                    <a href="logout.php">Logout</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>

<script>
// Mobile menu toggle
const menuToggle = document.querySelector('.mobile-menu-toggle');
const navLinks = document.querySelector('.nav-links');

if (menuToggle) {
    menuToggle.addEventListener('click', () => {
        navLinks.classList.toggle('active');
        menuToggle.setAttribute('aria-expanded', 
            menuToggle.getAttribute('aria-expanded') === 'false' ? 'true' : 'false'
        );
    });

    // Close mobile menu when clicking outside
    document.addEventListener('click', (e) => {
        if (navLinks.classList.contains('active') && 
            !e.target.closest('.nav-links') && 
            !e.target.closest('.mobile-menu-toggle')) {
            navLinks.classList.remove('active');
            menuToggle.setAttribute('aria-expanded', 'false');
        }
    });
}
</script> 