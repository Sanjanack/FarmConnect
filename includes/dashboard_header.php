<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Get current page name
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FarmConnect Dashboard</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 0.5rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        
        .navbar .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .navbar-brand {
            font-size: 24px;
            font-weight: 600;
            color: #4CAF50;
            text-decoration: none;
            padding: 0.5rem 0;
        }
        
        .nav-links {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .nav-links a {
            color: #333;
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            font-weight: 500;
            transition: all 0.2s;
        }
        
        .nav-links a:hover {
            background-color: #f5f5f5;
        }

        .nav-links a.active {
            color: #4CAF50;
            font-weight: 600;
        }
        
        .nav-links .add-crop {
            background-color: #E8F5E9;
            color: #4CAF50;
            padding: 0.5rem 1rem;
            border-radius: 4px;
        }
        
        .nav-links .add-crop:hover {
            background-color: #C8E6C9;
        }

        .nav-links .logout-btn {
            color: #DC3545;
        }

        .nav-links .logout-btn:hover {
            background-color: #ffebee;
        }

        .dashboard-container {
            margin-top: 80px;
            padding: 20px;
        }
        
        @media (max-width: 768px) {
            .nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                flex-direction: column;
                padding: 1rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
            
            .nav-links.active {
                display: flex;
            }

            .mobile-menu-toggle {
                display: block;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar" role="navigation" aria-label="Main navigation">
        <div class="container">
            <a href="../index.php" class="navbar-brand">FarmConnect</a>
            <button class="mobile-menu-toggle" aria-label="Toggle menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
            <ul class="nav-links">
                <li>
                    <a href="index.php" class="<?php echo $current_page === 'index' ? 'active' : ''; ?>">
                        <?php if ($_SESSION['user_type'] === 'farmer'): ?>
                            Farmer Dashboard
                        <?php else: ?>
                            Buyer Dashboard
                        <?php endif; ?>
                    </a>
                </li>
                <?php if ($_SESSION['user_type'] === 'farmer'): ?>
                    <li><a href="add_crop.php" class="add-crop <?php echo $current_page === 'add_crop' ? 'active' : ''; ?>">Add Crop</a></li>
                    <li><a href="my_crops.php" class="<?php echo $current_page === 'my_crops' ? 'active' : ''; ?>">My Crops</a></li>
                    <li><a href="farmer_orders.php" class="<?php echo $current_page === 'farmer_orders' ? 'active' : ''; ?>">Recent Orders</a></li>
                    <li><a href="orders.php" class="<?php echo $current_page === 'orders' ? 'active' : ''; ?>">Orders</a></li>
                <?php else: ?>
                    <li><a href="marketplace.php" class="<?php echo $current_page === 'marketplace' ? 'active' : ''; ?>">Marketplace</a></li>
                    <li><a href="my_orders.php" class="<?php echo $current_page === 'my_orders' ? 'active' : ''; ?>">My Orders</a></li>
                    <li><a href="cart.php" class="<?php echo $current_page === 'cart' ? 'active' : ''; ?>">Cart</a></li>
                <?php endif; ?>
                <li><a href="profile.php" class="<?php echo $current_page === 'profile' ? 'active' : ''; ?>">Profile</a></li>
                <li><a href="../logout.php" class="logout-btn">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="dashboard-container"> 