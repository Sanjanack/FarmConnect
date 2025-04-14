-- Create the farm_connect database
CREATE DATABASE IF NOT EXISTS farmconnect;
USE farm_connect;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    user_type ENUM('farmer', 'buyer', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Farmers table
CREATE TABLE IF NOT EXISTS farmer (
    farmer_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    name VARCHAR(100),
    contact_number VARCHAR(15),
    address VARCHAR(255),
    farming_experience TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Buyers table
CREATE TABLE IF NOT EXISTS buyer (
    buyer_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    company_name VARCHAR(100),
    business_type VARCHAR(100),
    contact_number VARCHAR(15),
    address TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- Crops table
CREATE TABLE IF NOT EXISTS crops (
    crop_id INT PRIMARY KEY AUTO_INCREMENT,
    farmer_id INT,
    crop_name VARCHAR(100) NOT NULL,
    c_quantity DECIMAL(10,2) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL DEFAULT 'kg',
    quality VARCHAR(50),
    crop_location VARCHAR(255),
    harvest_date DATE,
    listing_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('available', 'sold', 'reserved') DEFAULT 'available',
    FOREIGN KEY (farmer_id) REFERENCES farmer(farmer_id)
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    buyer_id INT,
    crop_id INT,
    quantity DECIMAL(10,2),
    total_price DECIMAL(10,2),
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (buyer_id) REFERENCES buyer(buyer_id),
    FOREIGN KEY (crop_id) REFERENCES crops(crop_id)
);

-- Payment table
CREATE TABLE IF NOT EXISTS payment (
    payment_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    amount DECIMAL(10,2),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_method VARCHAR(50),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
);

-- Supply Chain table
CREATE TABLE IF NOT EXISTS supply_chain (
    tracking_id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    status ENUM('processing', 'shipped', 'in_transit', 'delivered') DEFAULT 'processing',
    location VARCHAR(255),
    update_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id)
); 