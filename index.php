<?php
session_start();

// If user is already logged in, redirect to appropriate dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard/index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="FarmConnect - Connecting Farmers and Buyers for a Sustainable Agricultural Future">
    <title>FarmConnect - Agricultural Marketplace</title>
    <link rel="stylesheet" href="css/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Hero Section */
        .hero {
            background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('images/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: calc(100vh - 70px);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 2rem;
        }

        .hero-content {
            max-width: 800px;
            margin: 0 auto;
        }

        .hero h1 {
            font-size: 3rem;
            margin-bottom: 1rem;
            animation: fadeInUp 0.8s ease;
        }

        .hero p {
            font-size: 1.2rem;
            margin-bottom: 2rem;
            animation: fadeInUp 1s ease;
        }

        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            animation: fadeInUp 1.2s ease;
        }

        .btn {
            display: inline-block;
            padding: 0.8rem 2rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }

        .btn-primary {
            background-color: #4CAF50;
            color: white;
        }

        .btn-primary:hover {
            background-color: #388E3C;
        }

        .btn-secondary {
            background-color: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-secondary:hover {
            background-color: white;
            color: #4CAF50;
        }

        /* Features Section */
        .features {
            padding: 5rem 0;
            background-color: white;
        }

        .section-title {
            text-align: center;
            margin-bottom: 3rem;
            font-size: 2rem;
            color: #333;
        }

        .feature-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            padding: 0 1rem;
            max-width: 1200px;
            margin: 0 auto;
        }

        .feature-card {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .feature-card:hover {
            transform: translateY(-5px);
        }

        .feature-icon {
            font-size: 2.5rem;
            color: #4CAF50;
            margin-bottom: 1rem;
        }

        /* Footer */
        footer {
            background-color: #212121;
            color: white;
            padding: 3rem 0 1rem;
        }

        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            text-align: center;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            margin-bottom: 1rem;
            font-size: 1.5rem;
        }

        .footer-section p {
            color: #ccc;
            font-size: 1.1rem;
        }

        .copyright {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: #888;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .hero {
                padding: 60px 20px;
            }

            .hero h1 {
                font-size: 2rem;
            }

            .feature-grid {
                grid-template-columns: 1fr;
            }

            .cta-buttons {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                text-align: center;
            }

            .footer-content {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>

    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to FarmConnect</h1>
            <p>Connecting farmers directly with buyers for fresher produce and better prices</p>
            <?php if (!isset($_SESSION['user_id'])): ?>
                <div class="cta-buttons">
                    <a href="register.php" class="btn btn-primary">Get Started</a>
                    <a href="login.php" class="btn btn-secondary">Login</a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <section class="features">
        <h2 class="section-title">Why Choose FarmConnect?</h2>
        <div class="feature-grid">
            <div class="feature-card">
                <i class="fas fa-tractor feature-icon"></i>
                <h3>For Farmers</h3>
                <p>List your crops and connect directly with buyers. Get better prices for your produce.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-store feature-icon"></i>
                <h3>For Buyers</h3>
                <p>Find fresh, quality produce directly from farmers. Transparent pricing and reliable supply.</p>
            </div>
            <div class="feature-card">
                <i class="fas fa-handshake feature-icon"></i>
                <h3>Direct Connection</h3>
                <p>No middlemen means better prices for farmers and buyers alike.</p>
            </div>
        </div>
    </section>

    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h3>Contact Us</h3>
                <p>Email: support@farmconnect.com</p>
            </div>
        </div>
        <div class="copyright">
            <p>&copy; <?php echo date('Y'); ?> FarmConnect. All rights reserved.</p>
        </div>
    </footer>
</body>
</html> 