-- Insert test users
INSERT INTO users (username, password, email, user_type) VALUES
('farmer1', '$2y$10$abcdefghijklmnopqrstuv', 'farmer1@test.com', 'farmer'),
('buyer1', '$2y$10$abcdefghijklmnopqrstuv', 'buyer1@test.com', 'buyer');

-- Insert test farmer
INSERT INTO farmer (user_id, name, contact_number, address, farming_experience) VALUES
(1, 'Green Fields Farm', '9876543210', 'Karnataka, India', 'Organic farming since 2010');

-- Insert test buyer
INSERT INTO buyer (user_id, company_name, business_type, contact_number, address) VALUES
(2, 'Fresh Foods Ltd', 'Wholesale', '9876543211', 'Mumbai, India');

-- Insert test crops
INSERT INTO crops (farmer_id, crop_name, c_quantity, price, unit, quality, crop_location, harvest_date, status) VALUES
(1, 'Tomatoes', 100.00, 40.00, 'kg', 'Premium', 'Karnataka', '2024-03-15', 'available'),
(1, 'Potatoes', 200.00, 25.00, 'kg', 'Grade A', 'Karnataka', '2024-03-10', 'available'),
(1, 'Onions', 150.00, 35.00, 'kg', 'Premium', 'Karnataka', '2024-03-12', 'available'); 