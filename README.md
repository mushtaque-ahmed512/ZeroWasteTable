# SmartFood Rescue

A web application that connects food donors (restaurants, bakeries, hotels, grocery stores) with NGOs and charities, using volunteers to move surplus food before it expires.

Built with **Core PHP 8 (no framework)**, **MySQL/MariaDB (PDO)**, **Bootstrap 5**, **Chart.js**, **Leaflet** and plain JavaScript. A small MVC-style structure keeps SQL, logic and HTML apart.

## Features

| Requirement | Where it lives |
|---|---|
| Role-based authentication (admin, donor, NGO, volunteer) | `AuthController`, `middleware/` |
| Food donation management (create, edit, photos, cancel) | `DonationController`, `models/Donation.php` |
| NGO food requests | `RequestController`, `models/FoodRequest.php` |
| Donation approval (one NGO approved, others declined) | `RequestController::approve` |
| Pickup scheduling | `RequestController::schedule`, `models/Delivery.php` |
| Food distribution tracking (people served) | `DeliveryController::deliver` |
| Expiry notifications + auto-expiry | `models/Notification.php::runExpiryChecks`, `cron/expiry_check.php` |
| QR collection verification | QR shown to collector, scanned by donor on `/verify` |
| Analytics dashboard | `models/Analytics.php`, Chart.js on `/dashboard` |
| Maps | Leaflet: location pickers, browse map, pickup route |
| Reviews | donor and NGO rate each other after delivery |
| Profile editing, change password | `ProfileController` (`/profile`) |
| Forgot / reset password (one-time, 60-minute links) | `AuthController`, `models/PasswordReset.php` |
| Remove donation photos | edit screen of a donation |

## Install on XAMPP (Windows)

1. Install XAMPP (PHP 8.0+). Start **Apache** and **MySQL** in the XAMPP control panel.
2. Copy the `smartfood-rescue` folder to `C:\xampp\htdocs\`.
3. Import the database (see below).
4. Open **http://localhost/smartfood-rescue/**

Database credentials are in `config/database.php` (XAMPP default: user `root`, empty password).

### Importing `database/schema.sql` in phpMyAdmin

1. Open **http://localhost/phpmyadmin**.
2. Click the **Import** tab (top menu). You do not need to create the database first.
3. Under *File to import* choose `database/schema.sql`.
4. Leave format as **SQL** and click **Import** at the bottom.
5. You should see a green success message and a new `smartfood_rescue` database with 11 tables.

Command line alternative: `C:\xampp\mysql\bin\mysql -u root < database\schema.sql`

Re-importing wipes and recreates all tables (including seed data).

## Upgrading an existing install

If you already imported an earlier `schema.sql`, don't re-import (it wipes data). Instead open phpMyAdmin, select `smartfood_rescue`, go to the **SQL** tab and run `database/migrations/001_password_resets.sql`.

## Demo accounts

| Role | Email | Password |
|---|---|---|
| Admin | admin@smartfood.test | Admin@123 |
| Donor | donor@smartfood.test | Password@123 |
| NGO | ngo@smartfood.test | Password@123 |
| Volunteer | volunteer@smartfood.test | Password@123 |

The login page lists these while `APP_ENV` is `development`. Remove the seed users before going live.

## Try the full workflow

1. **Donor** posts food (or use the two seeded listings).
2. **NGO** opens *Browse food*, requests a donation.
3. **Donor** opens *Requests*, approves, then picks a pickup time. A QR code is generated.
4. **NGO (or donor/admin)** can assign a specific volunteer on the pickup page, or leave it open so any **volunteer** can claim it under *Pickup tasks*. The NGO can also collect it itself.
5. **NGO or volunteer** opens the delivery and shows the QR code.
6. **Donor** opens *Verify pickup* and scans it (or types the code).
7. **NGO or volunteer** records how many people were served. Both sides can leave a review.

New NGOs must be verified by an admin (*Users & orgs*) before they can request food. Turn this off with `NGO_REQUIRES_VERIFICATION` in `config/config.php`.

## Folder structure

```
smartfood-rescue/
  index.php            front controller
  .htaccess            pretty URLs
  config/              config.php, database.php, routes.php
  includes/            bootstrap, helpers, Router, Database (PDO), base Controller/Model, header/footer, components
  controllers/         request handling
  models/              all SQL lives here
  middleware/          auth, role, guest, csrf
  views/               page templates (no SQL)
  assets/css|js|images
  uploads/             donation photos (PHP execution blocked)
  database/schema.sql  tables + seed data
  cron/expiry_check.php
```

## Notes

- **Pretty URLs** need `mod_rewrite` (on by default in XAMPP). If URLs 404, set `PRETTY_URLS` to `false` in `config/config.php`.
- **Password reset email:** XAMPP can't send mail by default. In `development` mode the reset link is shown on the page instead; in `production` it is sent with PHP's `mail()`, so configure SMTP in `php.ini` first.
- **Expiry alerts** run automatically (at most every 5 minutes) while people use the site. For alerts with nobody online, schedule `php C:\xampp\htdocs\smartfood-rescue\cron\expiry_check.php` in Windows Task Scheduler.
- **QR camera scanning** needs `localhost` or HTTPS plus camera permission. Typing the code always works.
- Maps, charts and icons load from CDNs, so the browser needs internet access.
- Set `MAP_DEFAULT` and `APP_TIMEZONE` in `config/config.php` for your city.
- Security: prepared statements everywhere, `password_hash`, CSRF tokens on every POST, output escaping, image type checks, login throttling. Set `APP_ENV` to `production` when deploying.

## Not included yet

Partial-quantity splitting between several NGOs, changing your login email, and email/push notifications (alerts are in-app).
