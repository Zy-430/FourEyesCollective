# Four Eyes Collective

An eyewear e-commerce website developed using **PHP, MySQL, HTML, CSS, and JavaScript**. The platform allows customers to browse eyewear products, manage their shopping carts, and maintain their membership profiles, while administrators can manage product listings and inventory.

## Project Overview

Four Eyes Collective is an online eyewear shopping platform featuring a collection of glasses, sunglasses, contact lenses, and children's eyewear. It provides a user-friendly shopping experience with membership management and product administration functionalities.

## Features

### Customer Features
- **Member Registration & Login** – Create an account and log in to the platform.
- **Product Browsing** – Explore eyewear products across different categories.
- **Product Details** – View product descriptions, prices, images, and availability.
- **Shopping Cart** – Add products to the cart and manage item quantities.
- **Profile Management** – Update personal information, profile photos, and passwords.
- **Address Management** – Add, edit, and delete delivery addresses.

### Admin Features
- **Product Management** – Add, modify, and delete product listings.
- **Inventory Management** – Manage product stock and availability.
- **Product Image Management** – Upload and manage product images.

## Technologies Used

| Category | Technologies |
|---|---|
| Backend | PHP |
| Frontend | HTML, CSS, JavaScript |
| Database | MySQL |
| Database Connectivity | PHP PDO |
| Local Development Environment | XAMPP |

## Project Structure

```text
FourEyesCollective/
│
├── database/
│   └── foureyescollective.sql
│
└── web/
    ├── css/
    │   ├── app.css
    │   ├── login.css
    │   └── registration.css
    │
    ├── images/
    │   └── product/
    │
    ├── js/
    │   └── app.js
    │
    ├── lib/
    │   ├── db.php
    │   ├── SimpleImage.php
    │   └── SimplePager.php
    │
    ├── page/
    │   ├── homepage.php
    │   ├── shoppage.php
    │   ├── product_detail.php
    │   ├── cart.php
    │   ├── login.php
    │   ├── member_registration.php
    │   ├── profile_page.php
    │   └── ...
    │
    ├── _base.php
    ├── _head.php
    ├── _foot.php
    └── index.php

```

## Getting Started

### Prerequisites

Make sure you have installed:

- [XAMPP](https://www.apachefriends.org/) (Apache and MySQL)
- PHP
- MySQL
- A web browser

### Installation

1. **Clone the repository**

   ```bash
   git clone https://github.com/YOUR-USERNAME/FourEyesCollective.git
   ```

2. **Move the project folder**

   Move the cloned `FourEyesCollective` folder into the XAMPP `htdocs` directory.

3. **Start Apache and MySQL**

   Open the XAMPP Control Panel and start both Apache and MySQL.

4. **Open phpMyAdmin**

   Navigate to:

   ```text
   http://localhost/phpmyadmin
   ```

5. **Create the database**

   Create a new MySQL database named:

   ```text
   foureyescollective
   ```

6. **Import the database**

   Select the `foureyescollective` database in phpMyAdmin and import the SQL file located at:

   ```text
   database/foureyescollective.sql
   ```

7. **Configure the database connection**

   Open `web/lib/db.php` and ensure the database connection settings match your local MySQL configuration.

8. **Run the application**

   Open your browser and navigate to:

   ```text
   http://localhost/FourEyesCollective/web/
   ```

## Database Configuration

The project uses **MySQL** as its database and **PHP PDO** for database connectivity.

**Database Name:** `foureyescollective`

The database connection is configured in `web/lib/db.php`.

Default configuration:

```php
$_db = new PDO(
    'mysql:host=localhost;dbname=foureyescollective',
    'root',
    ''
);
```

Update the database credentials according to your local MySQL configuration.

## Project Purpose

This project was developed to gain practical experience in full-stack web development, particularly in PHP backend programming, MySQL database integration, frontend development, and implementing e-commerce functionalities.

---

