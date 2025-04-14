-- Drop database if exists and create new one
DROP DATABASE IF EXISTS farmconnect;
CREATE DATABASE farmconnect;
USE farmconnect;

-- Users table
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('farmer', 'buyer') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Farmers table
CREATE TABLE farmer (
    farmer_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    username VARCHAR(50) NOT NULL,
    FName VARCHAR(50) NOT NULL,
    LName VARCHAR(50),
    farm_name VARCHAR(100) NULL,
    location VARCHAR(255) NULL,
    contact_number VARCHAR(15) NULL,
    F_email VARCHAR(100) NULL,
    address TEXT NULL,
    farming_experience INT NULL,
    description TEXT NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Buyers table
CREATE TABLE buyer (
    buyer_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT UNIQUE,
    company_name VARCHAR(100),
    business_type VARCHAR(100),
    contact_number VARCHAR(15),
    address TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Crops table
CREATE TABLE crops (
    crop_id INT PRIMARY KEY AUTO_INCREMENT,
    farmer_id INT,
    crop_name VARCHAR(100) NOT NULL,
    quantity DECIMAL(10,2) NOT NULL,
    price_per_unit DECIMAL(10,2) NOT NULL,
    unit VARCHAR(20) NOT NULL,
    quality_grade VARCHAR(50),
    harvest_date DATE,
    listing_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('available', 'sold', 'reserved') DEFAULT 'available',
    FOREIGN KEY (farmer_id) REFERENCES farmer(farmer_id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    order_id INT PRIMARY KEY AUTO_INCREMENT,
    buyer_id INT,
    crop_id INT,
    quantity DECIMAL(10,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    FOREIGN KEY (buyer_id) REFERENCES buyer(buyer_id) ON DELETE SET NULL,
    FOREIGN KEY (crop_id) REFERENCES crops(crop_id) ON DELETE SET NULL
); 