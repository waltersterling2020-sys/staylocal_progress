# StayLocal apartment directory

StayLocal is a plain PHP, HTML, CSS, and MySQL apartment directory based on the supplied Information Management project brief. The first page lists apartment buildings in the area. A visitor can search by building, neighborhood, or street; filter by pet policy; and open a building to see its available units grouped by floor with monthly rent. Visitors can now reserve a unit, continue to an online payment confirmation step, and schedule an in-person visit. Final document review and contract signing are completed in person.

## Design decisions

The interface uses a **local editorial directory** direction rather than a generic rental marketplace. The visual system is built around deep blue, coral, muted yellow, and paper neutrals to make the listings feel like a printed neighborhood guide translated into a web page. Georgia is used for large editorial headlines and Trebuchet MS / Segoe UI for compact interface labels, deliberately avoiding a framework-default look. The page structure follows the renter's first action—compare buildings—rather than a generic hero-plus-feature-card template.

## Folder structure

| Path | Purpose |
| --- | --- |
| `index.php` | Searchable apartment directory homepage. |
| `apartment.php` | Building detail page with units grouped by floor. |
| `unit.php` | Dedicated unit detail page with image gallery and feature list. |
| `reservation.php` | Reservation form that routes the renter to online payment. |
| `payment.php` | Demo online payment confirmation that marks the unit reserved. |
| `visit.php` | In-person visit calendar with date and time-slot booking. |
| `admin/login.php` | Session-protected administrator sign-in page. |
| `admin/index.php` | Dashboard overview with listing, reservation, payment, and visit metrics. |
| `admin/apartments.php` | Create, edit, delete, and review apartment buildings. |
| `admin/units.php` | Create, edit, delete, and update unit availability and pricing. |
| `admin/reservations.php` | Review renter reservations and update their status. |
| `admin/payments.php` | Review payment records and mark refunds or failures. |
| `admin/visits.php` | Review scheduled visits and update appointment status. |
| `config/database.php` | PDO connection settings, admin session, and CSRF helpers. |
| `partials/header.php` | Shared page header and navigation. |
| `partials/footer.php` | Shared footer and site policy links. |
| `assets/style.css` | Responsive visual styling; no JavaScript is required. |
| `sql/staylocal.sql` | Database, tables, indexes, foreign key, and sample rows. |
| `DATABASE_TUTORIAL.md` | Comprehensive step-by-step database setup and table tutorial. |


## Adding your own apartments

The quickest method is to use phpMyAdmin's **Insert** tab. Add a row to `apartments`, then add related rows to `units` using the new apartment's `id` as `apartment_id`. Use `pet_friendly = 1` when pets are allowed and `pet_friendly = 0` when they are not. Set `image_url` to a real asset path such as `assets/feat-view.jpg`.

For units, use `status = 'available'`, `status = 'occupied'`, or `status = 'reserved'`. The homepage only counts available units when showing the card summary, while the detail page displays every unit so a renter can see the state of each unit on each floor.

## Common troubleshooting

| Symptom | Check |
| --- | --- |
| The page says it is waiting for a MySQL connection. | Confirm MySQL is running in XAMPP, verify the database name and password in `config/database.php`, and import `sql/staylocal.sql`. |
| The page is blank or shows a PHP error. | Confirm the project is inside `htdocs`, open it through `http://localhost/staylocal/`, and ensure Apache is running. Do not open the PHP file directly from the file system. |
| The apartment list is empty. | Check that the `apartments` table contains rows and that `units.apartment_id` points to those apartment IDs. |
| Unit gallery is not showing images. | Verify the `unit_images` table is populated and the `assets/` folder contains the generated `.jpg` files. |
| Prices or text look wrong. | Make sure the SQL file was imported as UTF-8 / `utf8mb4` and that the browser is loading `assets/style.css`. |

## Admin dashboard note

The admin dashboard is connected to the same database as the public website. Administrators can sign in, manage apartments and units, update availability and pricing, review reservations and payments, and mark in-person visits as scheduled, completed, or cancelled. The seed file creates a local development admin account; replace it or change its password before sharing the site.

## Reservation and payment note

The reservation flow collects renter details, sends the renter to an online payment confirmation page, and marks the unit reserved after a payment record is submitted. The payment page is a **local demo checkout**: it records a payment method and transaction reference but does not connect to a live payment gateway. Before accepting real funds, replace this step with a PCI-compliant provider such as Stripe, PayMongo, or Xendit and add server-side webhook verification. The visit calendar stores scheduled in-person appointments with unique time slots. Final document review and contract signing remain in person.
