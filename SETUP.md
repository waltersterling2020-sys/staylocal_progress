# StayLocal · Complete XAMPP & MySQL Setup Guide

This document provides a step-by-step procedure for setting up **XAMPP**, configuring **MySQL**, importing the database schema, and running the **StayLocal 75% core release** on Windows or macOS. The core release focuses on apartment listings, unit availability, reservations, visits, and essential administrator management. Online payment remains a clearly marked demo until a real payment gateway is connected.

---

## 1. Prerequisites & System Requirements

Before you begin, ensure your computer meets the following software requirements:
- **XAMPP (version 8.0 or higher)**: Includes Apache web server, PHP (8.0+ with PDO and MySQL support), and MariaDB/MySQL.
- **Web Browser**: Google Chrome, Mozilla Firefox, Microsoft Edge, or Safari.
- **Git** (optional): Recommended if you wish to clone the project from GitHub.

---

## 2. Installing and Starting XAMPP

### On Windows
1. Download the official XAMPP installer for Windows (PHP 8.1+ recommended) from [Apache Friends](https://www.apachefriends.org/).
2. Run the installer. When prompted for components, ensure **Apache**, **MySQL**, and **phpMyAdmin** are checked.
3. Choose your installation directory (by default, `C:\xampp`).
4. Open the **XAMPP Control Panel** from your Start menu or desktop shortcut.
5. Click **Start** next to **Apache** and **MySQL**. Both modules should display green text indicating they are running on ports `80`/`443` and `3306`.

### On macOS
1. Download the XAMPP OS X installer from [Apache Friends](https://www.apachefriends.org/).
2. Mount the disk image and drag XAMPP into your Applications folder.
3. Open the **XAMPP Control Panel** (Manager for OS X), go to the **Manage Servers** tab, and start **Apache Web Server** and **MySQL Database**.

---

## 3. Placing Project Files in the Web Root

XAMPP serves web applications from a designated root directory (`htdocs`). You must place the `staylocal` project folder inside this directory so Apache can process its PHP files.

1. Locate your project files (extracted from `staylocal-xampp.zip`).
2. Move or copy the entire `staylocal` folder into the XAMPP web root:
   - **Windows**: `C:\xampp\htdocs\staylocal`
   - **macOS**: `/Applications/XAMPP/htdocs/staylocal`

Verify that the folder structure looks like this:
```text
htdocs/
└── staylocal/
    ├── index.php
    ├── apartment.php
    ├── unit.php
    ├── reservation.php
    ├── payment.php
    ├── visit.php
    ├── admin/
    │   ├── login.php
    │   ├── index.php
    │   ├── apartments.php
    │   ├── units.php
    │   ├── reservations.php
    │   ├── payments.php
    │   └── visits.php
    ├── config/
    │   └── database.php
    ├── partials/
    │   ├── header.php
    │   └── footer.php
    ├── assets/
    │   └── style.css
    ├── sql/
    │   └── staylocal.sql
    └── README.md
```

---

## 4. Database Setup & Tutorial

For a comprehensive, step-by-step tutorial on setting up the database, understanding every table (`apartments`, `units`, `reservations`, `payments`, `visits`, `admin_users`), and viewing SQL queries from scratch, please refer to the dedicated [DATABASE_TUTORIAL.md](DATABASE_TUTORIAL.md) file included in the project.

Here is the quick setup via phpMyAdmin:

The StayLocal application connects to a MySQL database named `staylocal`. The SQL seed file is self-contained: it creates the database, resets the demo tables, defines foreign keys and indexes, and loads sample apartment/unit data. It is intended for a clean development import, not for production migrations.

1. Open your web browser and navigate to [http://localhost/phpmyadmin](http://localhost/phpmyadmin).
2. Click on the **Import** tab in the top navigation bar.
3. Under **File to import**, click **Choose File** (or Browse) and select the SQL file located at:
   - **Windows**: `C:\xampp\htdocs\staylocal\sql\staylocal.sql`
   - **macOS**: `/Applications/XAMPP/htdocs/staylocal/sql/staylocal.sql`
4. Leave the character set as `utf8mb4`.
5. Scroll to the bottom of the page and click the **Go** button.
6. Once the import completes successfully, you will see a green success message. On the left sidebar, verify that the `staylocal` database has been created containing the following tables:
   - `apartments`: Stores building information, addresses, and pet policies.
   - `units`: Stores unit numbers, floors, types, sizes, monthly rents, and statuses.
   - `unit_images`: Stores photo galleries for each unit.
   - `reservations`: Stores renter reservation details and due amounts.
   - `payments`: Records online payment transactions and references.
   - `visits`: Manages scheduled 30-minute in-person tour appointments.
   - `admin_users`: Stores administrator accounts with password hashes.

### Default local administrator

The SQL seed includes one development administrator so you can sign in immediately after importing the database:

| Field | Value |
| --- | --- |
| Admin URL | [http://localhost/staylocal/admin/login.php](http://localhost/staylocal/admin/login.php) |
| Email | `admin@staylocal.local` |
| Password | `admin123` |

Change this password before using the dashboard with real data. The login uses PHP password hashing and a session-protected admin area. The dashboard should not be publicly exposed until you replace the seeded development account and configure HTTPS.

---

## 5. Verifying Database Connection Settings

The PHP database helper is located at `config/database.php`. By default, XAMPP uses the root user with no password. Verify that your configuration matches your local environment:

```php
<?php

declare(strict_types=1);

const DB_HOST = '127.0.0.1';
const DB_NAME = 'staylocal';
const DB_USER = 'root';
const DB_PASS = ''; // Leave empty for default XAMPP setup
```

If you configured a root password in MySQL during setup, update `DB_PASS` accordingly. The connection also accepts `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, and `DB_PASS` environment variables; these override the local defaults. See `.env.example`. For production, use a dedicated database user rather than `root`.

---

## 6. Accessing the Website

1. Ensure both **Apache** and **MySQL** are running in your XAMPP Control Panel.
2. Open your web browser and go to:
   [http://localhost/staylocal/](http://localhost/staylocal/)
3. You should see the StayLocal homepage featuring apartment listings in the area, a search bar, and pet-friendly filters.
4. Click any apartment building to view its units grouped by floor, click a unit to open its photo gallery and amenity list, create a reservation inquiry, and book an **In-Person Visit** using the interactive calendar. The online payment screen is included for demonstration only and must not be used to accept real funds.
5. Open the private admin workspace at [http://localhost/staylocal/admin/login.php](http://localhost/staylocal/admin/login.php) and sign in with the development credentials above. The simplified dashboard focuses on apartments, units, reservations, and visits. Payment records remain available in the code but are not part of the core navigation until a gateway is integrated.

---

## 7. Troubleshooting Common Issues

| Symptom | Cause | Solution |
| --- | --- | --- |
| **Error establishing a database connection** | MySQL service is stopped or credentials are incorrect. | Start MySQL in the XAMPP Control Panel and verify `config/database.php` matches your MySQL username and password. |
| **`404 Not Found` when opening the site** | The project folder is not in the correct `htdocs` directory. | Ensure the `staylocal` folder is placed directly inside `C:\xampp\htdocs\` (Windows) or `/Applications/XAMPP/htdocs/` (macOS). |
| **PHP code displayed as raw text on the page** | Apache is not processing PHP files. | Ensure Apache is running in XAMPP and access the site via `http://localhost/staylocal/` rather than opening the file directly from disk (`file://`). |
| **Database import fails with syntax error** | Old database tables or mismatched SQL version. | In phpMyAdmin, drop the `staylocal` database if it partially exists, then re-import `sql/staylocal.sql`. |
