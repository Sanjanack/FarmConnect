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
$user = null;
$profile = null;
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        // Update user email
        if (!empty($_POST['email'])) {
            $stmt = $conn->prepare("UPDATE users SET email = ? WHERE user_id = ?");
            $stmt->bind_param("si", $_POST['email'], $_SESSION['user_id']);
            $stmt->execute();
        }

        // Update profile based on user type
        if ($_SESSION['user_type'] === 'farmer') {
            $stmt = $conn->prepare("
                UPDATE farmer SET 
                    name = ?,
                    contact_number = ?,
                    address = ?,
                    farming_experience = ?
                WHERE user_id = ?
            ");
            $stmt->bind_param("sssii", 
                $_POST['name'],
                $_POST['contact_number'],
                $_POST['address'],
                $_POST['farming_experience'],
                $_SESSION['user_id']
            );
        } else {
            // Validate required fields for buyer
            if (empty($_POST['contact_number']) || empty($_POST['address'])) {
                throw new Exception("Contact number and address are required");
            }

            $stmt = $conn->prepare("
                UPDATE buyer SET 
                    contact_number = ?,
                    address = ?
                WHERE user_id = ?
            ");
            $stmt->bind_param("ssi", 
                $_POST['contact_number'],
                $_POST['address'],
                $_SESSION['user_id']
            );
        }
        $stmt->execute();
        
        $conn->commit();
        $success = "Profile updated successfully!";
        
        // Clear profile_incomplete flag if all required fields are filled
        if (isset($_SESSION['profile_incomplete']) && $_SESSION['user_type'] === 'buyer') {
            unset($_SESSION['profile_incomplete']);
            header("Location: index.php");
            exit();
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
        error_log("Profile update error: " . $e->getMessage());
    }
}

// Get user data
try {
    // Get user details
    $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    // Get profile details based on user type
    if ($_SESSION['user_type'] === 'farmer') {
        $stmt = $conn->prepare("SELECT * FROM farmer WHERE user_id = ?");
    } else {
        $stmt = $conn->prepare("SELECT * FROM buyer WHERE user_id = ?");
    }
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $profile = $stmt->get_result()->fetch_assoc();
} catch (Exception $e) {
    error_log("Profile page error: " . $e->getMessage());
    $error = "An error occurred while loading your profile.";
}
?>

<div class="container">
    <h1>My Profile</h1>
    
    <?php if (isset($_SESSION['profile_incomplete']) && $_SESSION['user_type'] === 'buyer'): ?>
        <div class="alert alert-warning">
            <strong>Welcome to FarmConnect!</strong> Please complete your profile to continue.
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="profile-card">
        <form method="POST" action="profile.php">
            <div class="form-section">
                <h2>Account Information</h2>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                    <small>Username cannot be changed</small>
                </div>

                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
            </div>

            <?php if ($_SESSION['user_type'] === 'buyer'): ?>
            <div class="form-section">
                <h2>Contact Information</h2>
                <div class="form-group">
                    <label>Contact Number</label>
                    <input type="tel" name="contact_number" value="<?php echo isset($profile['contact_number']) ? htmlspecialchars($profile['contact_number']) : ''; ?>" required>
                </div>

                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3" required><?php echo isset($profile['address']) ? htmlspecialchars($profile['address']) : ''; ?></textarea>
                </div>
            </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?php echo isset($_SESSION['profile_incomplete']) ? 'Complete Profile' : 'Update Profile'; ?>
                </button>
                <?php if (!isset($_SESSION['profile_incomplete'])): ?>
                    <a href="index.php" class="btn btn-secondary">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<style>
.container {
    max-width: 800px;
    margin: 0 auto;
    padding: 20px;
}

.profile-card {
    background: white;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    padding: 30px;
    margin-top: 20px;
}

.form-section {
    margin-bottom: 30px;
}

.form-section h2 {
    color: #333;
    font-size: 1.5em;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 1px solid #eee;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    color: #555;
    font-weight: 500;
}

.form-group input,
.form-group textarea {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    font-size: 14px;
}

.form-group textarea {
    resize: vertical;
    min-height: 100px;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #666;
    font-size: 12px;
}

.form-actions {
    display: flex;
    gap: 10px;
    margin-top: 30px;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 4px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn-primary {
    background-color: #4CAF50;
    color: white;
}

.btn-primary:hover {
    background-color: #45a049;
}

.btn-secondary {
    background-color: #f0f0f0;
    color: #333;
    text-decoration: none;
}

.btn-secondary:hover {
    background-color: #e4e4e4;
}

.alert {
    padding: 15px;
    border-radius: 4px;
    margin-bottom: 20px;
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

.alert-warning {
    background-color: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}
</style>

<?php include '../includes/dashboard_footer.php'; ?> 