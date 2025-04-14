# UNIVERSITY OF VISVESVARAYA COLLEGE OF ENGINEERING
# DEPARTMENT OF COMPUTER SCIENCE AND ENGINEERING
# K.R. Circle, Bengaluru – 560001

<br><br><br><br>

# A DBMS Mini-Project Report on
# FARMCONNECT: AN AGRICULTURAL SUPPLY CHAIN MANAGEMENT SYSTEM

<br><br>

## Submitted by
**[Sanjana C K] ([U25UV22T064049])**  
**[Y Lohith] ([U25UV22T064065])**  
**V SEM, B. TECH (ISE)**

<br><br>

## Under the guidance of

**Dr. Kumaraswamy**  
**Associate Professor**  
**Department of Computer Science and Engineering**  
**UVCE**

<br><br><br>

**March-2025**

---

# CERTIFICATE

This is to certify that **[Sanjana C K and Y Lohith]** of V Semester, B. Tech, Computer Science and Engineering, bearing the register numbers **[U25UV22T064049 and U25UV22T064065]** respectively has submitted the DBMS Mini-Project Report on "**FARMCONNECT: AN AGRICULTURAL SUPPLY CHAIN MANAGEMENT SYSTEM**", in partial fulfilment for the DBMS Lab, prescribed by the University of Visvesvaraya College of Engineering for the academic year 2024-25.

<br><br><br>

**Dr. Kumaraswamy**  
Associate Professor  
Dept. of CSE,  
UVCE  

**Dr. Thriveni J**  
Professor & Chairperson  
Dept. of CSE,  
UVCE

<br><br>

Examiners:

1. ………………………… 2. ...……………………….

---

# ACKNOWLEDGEMENT

I take this opportunity to thank our institution University of Visvesvaraya College of Engineering for having given me an opportunity to carry out this project.

I would like to thank Prof. Subhasish Tripathy, Director, UVCE, for providing us all the facilities to work on this project. I am indebted to him for being my pillar of strength and inspiration.

I wish to place my grateful thanks to Dr. Thriveni J, Professor and Chairperson, Department of Computer Science and Engineering, UVCE, who helped me to make my project a great success.

It gives me great pleasure to express my gratitude to Dr. Kumaraswamy, Associate Professor, Department of Computer Science Engineering, UVCE for her valuable guidance and supervision in this course of project.

I express my sincere thanks to all teaching and non-teaching staff, Department of Computer Science and Engineering, UVCE for all the facilities that they have provided me for successfully completing this project.

I also thank my parents and friends for their continuous support and encouragement.

**[Your Names and USNs]**

---

# ABSTRACT

FarmConnect is a web-based Agricultural Supply Chain Management System developed to bridge the gap between farmers and buyers in the agricultural sector. The system streamlines the process of crop listing, order management, and supply chain tracking, ensuring efficient delivery of agricultural products from farm to market.

The project utilizes modern web technologies including PHP for the backend, MySQL for database management, and HTML/CSS/JavaScript for the frontend. It implements a comprehensive supply chain tracking system that monitors order status from pending to delivery, manages farmer earnings, and handles buyer payments securely.

Key features include real-time order status updates, automated earnings calculations, inventory management, and detailed transaction history tracking. The system emphasizes data security, user experience, and efficient supply chain operations, making it a valuable tool for both farmers and buyers in the agricultural sector.

---

# TABLE OF CONTENTS

1. Introduction
   1.1 Introduction
   1.2 Objective
   1.3 Functionality
   1.4 Database Management System
   1.5 MySQL

2. Literature Review
   2.1 Survey of Existing System
   2.2 Developed System
   2.3 Software Requirements
   2.4 Frontend Technologies
   2.5 Backend Technologies
   2.6 Entity Relationship Model
   2.7 Relational Schema Design
   2.8 Normalization
   2.9 API Development
   2.10 Advantages of the Developed System

3. Proposed Work
   3.1 Entity-Relationship (ER) Model
   3.2 Relational Model
   3.3 Normalization

4. Results
   4.1 Screenshots
   4.1.1 Welcome Page
   4.1.2 Login/Signup Page
   4.1.3 Farmer Dashboard
   4.1.4 Buyer Dashboard
   4.1.5 Order Management
   4.1.6 Supply Chain Tracking

5. Conclusion

6. Bibliography

---

# CHAPTER 1
# INTRODUCTION

## 1.1 Introduction
FarmConnect is a comprehensive Agricultural Supply Chain Management System designed to revolutionize the way farmers and buyers interact in the agricultural marketplace. The system addresses key challenges in agricultural commerce by providing a digital platform that connects farmers directly with potential buyers, streamlines the order process, and ensures transparent supply chain tracking.

In the traditional agricultural supply chain, farmers often face difficulties in finding the right buyers for their crops, while buyers struggle to source quality produce directly from farmers. FarmConnect bridges this gap by creating a digital marketplace where farmers can list their crops and buyers can place orders efficiently, with real-time tracking of order status from confirmation to delivery.

## 1.2 Objective
The primary objectives of FarmConnect include:
- Creating a user-friendly platform for farmers to list and manage their agricultural products
- Providing buyers with direct access to farm-fresh produce
- Implementing a transparent supply chain tracking system
- Automating earnings calculations and payment processing
- Ensuring secure and efficient order management
- Maintaining detailed transaction histories
- Generating comprehensive reports for both farmers and buyers

## 1.3 Functionality
FarmConnect offers the following key functionalities:

### Farmer Module
- Crop Management: Add, update, and manage crop listings
- Order Tracking: Monitor order status and update supply chain stages
- Earnings Dashboard: Track earnings and payment history
- Inventory Management: Update crop quantities and availability

### Buyer Module
- Marketplace: Browse and search available crops
- Order Management: Place orders and track deliveries
- Payment Processing: Secure payment handling
- Transaction History: View past orders and payments

### Supply Chain Management
- Status Tracking: Monitor order progress through various stages
- Real-time Updates: Instant status changes reflection
- Automated Notifications: Updates for both farmers and buyers
- History Maintenance: Complete tracking record

## 1.4 Database Management System
The system utilizes MySQL as its primary database management system, ensuring:
- Data Integrity: Through proper relationships and constraints
- Concurrent Access: Multiple users can access simultaneously
- Data Security: Role-based access control
- Efficient Retrieval: Optimized queries for better performance

## 1.5 MySQL Implementation
Key MySQL features utilized in FarmConnect:
- Stored Procedures: For complex business logic
- Triggers: For automated updates
- Views: For simplified data access
- Transactions: For data consistency

# CHAPTER 2
# LITERATURE REVIEW

## 2.1 Survey of Existing System
Traditional agricultural supply chain systems face several challenges:
- Limited direct farmer-buyer connection
- Lack of transparency in pricing
- Inefficient order tracking
- Manual record-keeping
- Delayed payment processing
- No real-time inventory updates

## 2.2 Developed System
FarmConnect addresses these limitations through:
- Digital marketplace platform
- Automated supply chain tracking
- Real-time status updates
- Secure payment processing
- Comprehensive reporting system

## 2.3 Software Requirements
- Frontend: HTML5, CSS3, JavaScript
- Backend: PHP 8.0+
- Database: MySQL 8.0
- Server: Apache
- Additional Tools: XAMPP

## 2.4 Frontend Technologies
The frontend implementation utilizes:
- Responsive design principles
- Modern CSS frameworks
- JavaScript for dynamic content
- AJAX for asynchronous updates

## 2.5 Backend Technologies
Backend architecture includes:
- PHP for server-side logic
- MySQL for data storage
- Apache web server
- Session management
- Security implementations

[Continue with Entity Relationship Model, Schema Design, etc...]

# CHAPTER 3
# PROPOSED WORK

## 3.1 Entity-Relationship (ER) Model
The system's database design includes the following main entities:
- Farmer (farmer_id, name, contact, address)
- Buyer (buyer_id, name, contact, address)
- Crops (crop_id, name, quantity, price, farmer_id)
- Orders (order_id, buyer_id, crop_id, quantity, status)
- Supply_Chain (tracking_id, order_id, status, update_time)

## 3.2 Relational Model
Key relationships include:
- Farmer-Crops (One-to-Many)
- Buyer-Orders (One-to-Many)
- Orders-Supply_Chain (One-to-One)
- Crops-Orders (One-to-Many)

## 3.3 Normalization
The database is normalized to 3NF to ensure:
- Elimination of data redundancy
- Data integrity maintenance
- Efficient updates
- Minimal anomalies

# CHAPTER 4
# RESULTS

## 4.1 Screenshots and Implementation Details

### 4.1.1 Welcome Page
The welcome page provides:
- User role selection (Farmer/Buyer)
- System overview
- Easy navigation options

### 4.1.2 Login/Signup Page
Features include:
- Secure authentication
- Role-based access control
- Password encryption
- Form validation

### 4.1.3 Farmer Dashboard
Displays:
- Active crop listings
- Order notifications
- Earnings summary
- Supply chain updates

### 4.1.4 Buyer Dashboard
Shows:
- Available crops
- Order history
- Payment status
- Delivery tracking

### 4.1.5 Order Management
Includes:
- Order creation
- Status updates
- Payment processing
- Delivery tracking

### 4.1.6 Supply Chain Tracking
Provides:
- Real-time status updates
- Timeline view
- Status change history
- Notification system

# CHAPTER 5
# CONCLUSION

FarmConnect successfully implements a comprehensive agricultural supply chain management system that benefits both farmers and buyers. The system effectively addresses the challenges in traditional agricultural commerce by providing:
- Direct farmer-buyer connection
- Transparent pricing
- Efficient order tracking
- Secure payment processing
- Real-time updates

Future enhancements could include:
- Mobile application development
- Integration with logistics providers
- Advanced analytics
- Weather forecasting integration
- Quality certification system

# CHAPTER 6
# BIBLIOGRAPHY

1. Connolly, T. and Begg, C. (2014). Database Systems: A Practical Approach to Design, Implementation, and Management. 6th ed. Pearson.

2. Nixon, R. (2021). Learning PHP, MySQL & JavaScript. 6th ed. O'Reilly Media.

3. Vaswani, V. (2019). MySQL Database Usage & Administration. McGraw-Hill Education.

4. Duckett, J. (2011). HTML & CSS: Design and Build Websites. John Wiley & Sons.

5. [Add more relevant references...] 