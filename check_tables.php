<?php
require_once 'db_connect.php';

// Function to check if a table exists
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result->num_rows > 0;
}

// Function to check if a column exists in a table
function columnExists($conn, $tableName, $columnName) {
    $result = $conn->query("SHOW COLUMNS FROM `$tableName` LIKE '$columnName'");
    return $result->num_rows > 0;
}

try {
    // Check users table
    if (!tableExists($conn, 'users')) {
        $conn->query("CREATE TABLE users (
            user_id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            user_type ENUM('farmer', 'buyer', 'admin') NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        echo "Created users table<br>";
    }

    // Check farmer table
    if (!tableExists($conn, 'farmer')) {
        $conn->query("CREATE TABLE farmer (
            farmer_id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            farm_name VARCHAR(100),
            location VARCHAR(255),
            contact_number VARCHAR(15),
            description TEXT,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        )");
        echo "Created farmer table<br>";
    }

    // Check buyer table
    if (!tableExists($conn, 'buyer')) {
        $conn->query("CREATE TABLE buyer (
            buyer_id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT,
            company_name VARCHAR(100),
            business_type VARCHAR(100),
            contact_number VARCHAR(15),
            address TEXT,
            FOREIGN KEY (user_id) REFERENCES users(user_id)
        )");
        echo "Created buyer table<br>";
    }

    // Check if tables exist and have correct structure
    $tables = ['users', 'farmer', 'buyer'];
    foreach ($tables as $table) {
        if (tableExists($conn, $table)) {
            echo "$table table exists<br>";
            
            // Display table structure
            $result = $conn->query("DESCRIBE $table");
            while ($row = $result->fetch_assoc()) {
                echo "Column: " . $row['Field'] . " - Type: " . $row['Type'] . "<br>";
            }
        } else {
            echo "ERROR: $table table is missing!<br>";
        }
    }

    echo "<br>Database check completed successfully!";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 