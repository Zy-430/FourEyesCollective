# Four Eyes Collective

An eyewear e-commerce website developed using **PHP, MySQL, HTML, CSS, and JavaScript**. The platform allows customers to browse eyewear products, manage their shopping carts, and maintain their membership profiles, while administrators can manage product listings and inventory.

## Project Overview

Four Eyes Collective is an online eyewear shopping platform featuring a collection of glasses, sunglasses, contact lenses, and children's eyewear. It provides a user-friendly shopping experience with membership management and product administration functionalities.

## Features

### Customer Features
- **Member Registration & Login** – Create an account and securely log in to the platform.
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

- **Backend:** PHP
- **Frontend:** HTML, CSS, JavaScript
- **Database:** MySQL
- **Styling:** CSS
- **Client-side Interaction:** JavaScript

## Project Structure

```text
FourEyesCollective/
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
- XAMPP (Apache and MySQL)
- PHP
- MySQL
- A web browser

### Installation

1. Clone the repository:

   ```bash
   git clone https://github.com/YOUR-USERNAME/FourEyesCollective.git
   ```

2. Move the project folder into the XAMPP `htdocs` directory.

3. Start **Apache** and **MySQL** using the XAMPP Control Panel.

4. Create a MySQL database named:

   ```sql
   CREATE DATABASE foureyescollective;
   ```

5. Configure the database connection in `web/lib/db.php` according to your MySQL settings.

6. Set up the required database tables before running the application.

7. Open your browser and navigate to:

   ```text
   http://localhost/FourEyesCollective/web/
   ```

## Database Configuration

The project uses PHP PDO to connect to the MySQL database.

Default database configuration in `web/lib/db.php`:

```php
$_db = new PDO(
    'mysql:host=localhost;dbname=foureyescollective',
    'root',
    ''
);
```

Update the database credentials according to your local environment.

**Note:** The database SQL export is not included in this repository. You will need the corresponding database schema and tables to run the application successfully.

## Project Purpose

This project was developed to gain practical experience in full-stack web development, particularly in PHP backend programming, MySQL database integration, frontend development, and implementing e-commerce functionalities.

---
