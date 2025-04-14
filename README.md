# FarmConnect - Agricultural Database Management System

FarmConnect is a web-based platform that connects farmers directly with buyers, streamlining the agricultural supply chain process. The system facilitates crop listing, ordering, and management of agricultural products.

## Features

- User Authentication (Farmers, Buyers, Admin)
- Crop Management
- Marketplace for Agricultural Products
- Order Processing
- Payment Management
- Supply Chain Tracking
- User Dashboard for both Farmers and Buyers

## Technology Stack

- Frontend: HTML, CSS, JavaScript
- Backend: PHP
- Database: MySQL
- Server: Apache

## Installation

1. Clone the repository
2. Import the database schema from `database/schema.sql`
3. Configure database connection in `db_connect.php`
4. Start your Apache server
5. Access the application through your web browser

## Project Structure

```
FarmConnect/
├── assets/
│   └── images/
├── backend/
│   └── database/
├── frontend/
│   ├── css/
│   ├── js/
│   └── pages/
├── db_connect.php
├── index.php
└── README.md
```

## Usage

1. Register as either a farmer or buyer
2. Login to access your dashboard
3. Farmers can:
   - List their crops
   - Manage inventory
   - Track orders
4. Buyers can:
   - Browse available crops
   - Place orders
   - Track deliveries

## Security

- Password hashing
- SQL injection prevention
- Session management
- Input validation

## Contributing

Feel free to fork this repository and submit pull requests.

## License

This project is licensed under the MIT License.
