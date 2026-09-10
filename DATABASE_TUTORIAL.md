# StayLocal · Complete MySQL Database & XAMPP Setup Tutorial

This guide provides a comprehensive, beginner-friendly walkthrough for setting up the **StayLocal** database in XAMPP. Whether you are using Windows or macOS, this tutorial covers starting your local server, creating the database, understanding every table and column, and verifying your connection.

---

## Part 1: Starting XAMPP and Accessing phpMyAdmin

Before working with databases, you must ensure that Apache (the web server) and MySQL (the database server) are running.

1. **Open the XAMPP Control Panel**:
   - **Windows**: Search for **XAMPP Control Panel** in your Start menu or open it from `C:\xampp\xampp-control.exe`.
   - **macOS**: Open your Applications folder, launch **XAMPP**, and navigate to the **Manage Servers** tab.
2. **Start the Services**:
   - Click the **Start** button next to **Apache**.
   - Click the **Start** button next to **MySQL**.
   - Both modules should display green indicators and running status.
3. **Open phpMyAdmin**:
   - Open your web browser (Chrome, Firefox, Edge, or Safari).
   - Type [http://localhost/phpmyadmin](http://localhost/phpmyadmin) in the address bar and press Enter. This is your visual MySQL database management interface.

---

## Part 2: The Two Ways to Set Up the Database

You have two methods to create the database: the **Quick Import Method** (recommended) and the **Manual Table Creation Method** (if you want to understand how every table is built from scratch).

### Method A: The Quick Import Method (Recommended)
The project includes a complete SQL file (`sql/staylocal.sql`) that creates the database, all tables, foreign keys, indexes, and sample data automatically.

1. In phpMyAdmin, click on the **Import** tab in the top navigation menu.
2. Under **File to import**, click **Choose File** (or Browse).
3. Navigate to your project folder inside XAMPP:
   - **Windows**: `C:\xampp\htdocs\staylocal\sql\staylocal.sql`
   - **macOS**: `/Applications/XAMPP/htdocs/staylocal/sql/staylocal.sql`
4. Leave the character set at `utf8mb4`.
5. Scroll to the bottom and click the **Go** button.
6. A green success message will appear, and you will see the `staylocal` database appear in the left sidebar.

---

### Method B: Manual Database & Table Creation Tutorial
If you prefer to build the database yourself step by step in phpMyAdmin, follow these instructions.

#### Step 1: Create the Database
1. In phpMyAdmin, click the **SQL** tab at the top or click **New** on the left sidebar.
2. Paste the following SQL command and click **Go**:
   ```sql
   CREATE DATABASE IF NOT EXISTS staylocal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   USE staylocal;
   ```

#### Step 2: Create the Tables One by One
Click on the `staylocal` database in the left sidebar, go to the **SQL** tab, and run the following queries to create each table:

1. **`admin_users` table** (Stores administrator login credentials):
   ```sql
   CREATE TABLE admin_users (
       id INT UNSIGNED NOT NULL AUTO_INCREMENT,
       full_name VARCHAR(120) NOT NULL,
       email VARCHAR(180) NOT NULL,
       password_hash VARCHAR(255) NOT NULL,
       last_login_at TIMESTAMP NULL DEFAULT NULL,
       created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
       PRIMARY KEY (id),
       UNIQUE KEY unique_admin_email (email)
   ) ENGINE=InnoDB;
   ```

2. **`apartments` table** (Stores building listings, addresses, and pet policies):
   ```sql
   CREATE TABLE apartments (
       id INT UNSIGNED NOT NULL AUTO_INCREMENT,
       name VARCHAR(120) NOT NULL,
       area VARCHAR(100) NOT NULL,
       address VARCHAR(180) NOT NULL,
       description TEXT NOT NULL,
       pet_friendly TINYINT(1) NOT NULL DEFAULT 0,
       image_url VARCHAR(255) NOT NULL DEFAULT 'assets/feat-view.jpg',
       featured TINYINT(1) NOT NULL DEFAULT 0,
       created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
       PRIMARY KEY (id),
       INDEX idx_apartments_area (area),
       INDEX idx_apartments_pet_friendly (pet_friendly)
   ) ENGINE=InnoDB;
   ```

3. **`units` table** (Stores individual apartments by floor, pricing, and availability status):
   ```sql
   CREATE TABLE units (
       id INT UNSIGNED NOT NULL AUTO_INCREMENT,
       apartment_id INT UNSIGNED NOT NULL,
       floor_number TINYINT UNSIGNED NOT NULL,
       unit_number VARCHAR(20) NOT NULL,
       unit_type VARCHAR(60) NOT NULL,
       size_sqm DECIMAL(6,2) NOT NULL,
       monthly_rent DECIMAL(10,2) NOT NULL,
       image_url VARCHAR(255) DEFAULT NULL,
       features TEXT DEFAULT NULL,
       status ENUM('available', 'occupied', 'reserved') NOT NULL DEFAULT 'available',
       created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
       PRIMARY KEY (id),
       UNIQUE KEY unique_apartment_unit (apartment_id, unit_number),
       INDEX idx_units_status (status),
       CONSTRAINT fk_units_apartment FOREIGN KEY (apartment_id) REFERENCES apartments(id) ON DELETE CASCADE ON UPDATE CASCADE
   ) ENGINE=InnoDB;
   ```

4. **`unit_images` table** (Stores photo galleries for Airbnb-style unit feature views):
   ```sql
   CREATE TABLE unit_images (
       id INT UNSIGNED NOT NULL AUTO_INCREMENT,
       unit_id INT UNSIGNED NOT NULL,
       image_path VARCHAR(255) NOT NULL,
       caption VARCHAR(100) DEFAULT NULL,
       sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
       PRIMARY KEY (id),
       CONSTRAINT fk_images_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE ON UPDATE CASCADE
   ) ENGINE=InnoDB;
   ```

5. **`reservations` table** (Stores renter reservations before online payment):
   ```sql
   CREATE TABLE reservations (
       id INT UNSIGNED NOT NULL AUTO_INCREMENT,
       unit_id INT UNSIGNED NOT NULL,
       full_name VARCHAR(140) NOT NULL,
       email VARCHAR(180) NOT NULL,
       phone VARCHAR(40) NOT NULL,
       move_in_date DATE NOT NULL,
       occupants TINYINT UNSIGNED NOT NULL DEFAULT 1,
       notes TEXT DEFAULT NULL,
       amount_due DECIMAL(10,2) NOT NULL,
       status ENUM('pending_payment', 'paid', 'cancelled') NOT NULL DEFAULT 'pending_payment',
       created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
       PRIMARY KEY (id),
       INDEX idx_reservations_unit (unit_id),
       INDEX idx_reservations_status (status),
       CONSTRAINT fk_reservations_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE ON UPDATE CASCADE
   ) ENGINE=InnoDB;
   ```

6. **`payments` table** (Records online checkout references and payment methods):
   ```sql
   CREATE TABLE payments (
       id INT UNSIGNED NOT NULL AUTO_INCREMENT,
       reservation_id INT UNSIGNED NOT NULL,
       payment_method ENUM('card', 'gcash', 'bank_transfer') NOT NULL,
       transaction_reference VARCHAR(80) NOT NULL,
       amount DECIMAL(10,2) NOT NULL,
       status ENUM('paid', 'failed', 'refunded') NOT NULL DEFAULT 'paid',
       paid_at TIMESTAMP NULL DEFAULT NULL,
       PRIMARY KEY (id),
       UNIQUE KEY unique_transaction_reference (transaction_reference),
       INDEX idx_payments_reservation (reservation_id),
       CONSTRAINT fk_payments_reservation FOREIGN KEY (reservation_id) REFERENCES reservations(id) ON DELETE CASCADE ON UPDATE CASCADE
   ) ENGINE=InnoDB;
   ```

7. **`visits` table** (Manages in-person tour appointments and calendar slots):
   ```sql
   CREATE TABLE visits (
       id INT UNSIGNED NOT NULL AUTO_INCREMENT,
       unit_id INT UNSIGNED NOT NULL,
       full_name VARCHAR(140) NOT NULL,
       email VARCHAR(180) NOT NULL,
       phone VARCHAR(40) NOT NULL,
       visit_date DATE NOT NULL,
       visit_time TIME NOT NULL,
       notes TEXT DEFAULT NULL,
       status ENUM('scheduled', 'completed', 'cancelled') NOT NULL DEFAULT 'scheduled',
       created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
       PRIMARY KEY (id),
       UNIQUE KEY unique_visit_slot (visit_date, visit_time),
       INDEX idx_visits_unit (unit_id),
       CONSTRAINT fk_visits_unit FOREIGN KEY (unit_id) REFERENCES units(id) ON DELETE CASCADE ON UPDATE CASCADE
   ) ENGINE=InnoDB;
   ```

---

## Part 3: Understanding the Database Schema & Relations

The StayLocal database is relational and connects renters, buildings, units, payments, and visits:

| Table Name | Primary Purpose | Key Relationships |
| --- | --- | --- |
| `admin_users` | Secure administrator authentication. | Standalone table with hashed passwords. |
| `apartments` | Stores building info (Casa Pilar, Northline, etc.). | Parent table for `units`. |
| `units` | Stores floors, unit numbers, rents, and availability. | Belongs to an `apartment_id`; parent to `unit_images`, `reservations`, and `visits`. |
| `unit_images` | Stores photo galleries (kitchen, bathroom, view). | Belongs to a `unit_id`. |
| `reservations` | Stores renter intent and move-in details. | Belongs to a `unit_id`; parent to `payments`. |
| `payments` | Records card, GCash, or bank transfer receipts. | Belongs to a `reservation_id`. |
| `visits` | Stores scheduled tour appointments. | Belongs to a `unit_id`. |

---

## Part 4: Connecting PHP to MySQL

The website connects to MySQL using PHP's PDO (PHP Data Objects) extension. Open `config/database.php` to verify your connection credentials:

```php
<?php

declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_NAME = 'staylocal';
const DB_USER = 'root';
const DB_PASS = ''; // Default XAMPP has no password for root
```

---

## Part 5: Default Admin Credentials

Once your database is imported and XAMPP is running, you can access the admin dashboard:
- **Admin Login URL**: [http://localhost/staylocal/admin/login.php](http://localhost/staylocal/admin/login.php)
- **Email**: `admin@staylocal.local`
- **Password**: `admin123`

*(Note: Change this password in production before managing live properties.)*
