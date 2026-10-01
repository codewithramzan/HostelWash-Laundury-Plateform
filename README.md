# HostelWash

A PHP MVC laundry service application for university hostels. Includes the public website, student portal, mobile-friendly representative workflow, and administration CMS. Built from the supplied green UI reference and unchanged MySQL schema.

## Start here — Laragon on Windows

1. Extract this ZIP. Place the **hostelwash** folder at `C:\laragon\www\hostelwash` (or your own Laragon `www` directory).
2. Start **Apache** and **MySQL** in Laragon.
3. Open phpMyAdmin and import `database/hostelwash.sql`. It creates and selects the `hostelwash` database. The SQL is an exact copy of your supplied file.
4. Copy `.env.example` to `.env`. Set your database username/password and `APP_URL=http://hostelwash.test`.
5. Set the Apache virtual host **DocumentRoot to the public folder**, not the project root. See the example below. Enable rewrite and AllowOverride All. Reload Apache.
6. Open Laragon Terminal in the project folder and run:

```bat
php bin/doctor.php
php bin/create-admin.php
```

Enter your name, email and a unique password. There is **no default admin password**. The command only creates an account; it never overwrites an existing account.

7. Visit `http://hostelwash.test/login`. Sign in as admin.
8. Add your real **Hostels**, then **Rooms**. Services and prices are already seeded from your SQL.
9. Under **Students & Users**, create a representative account. Under **Representatives**, connect that account to a hostel.
10. Open the public site in another browser/incognito window, register as a student, and book a pickup.

Optional local-only sample hostels and rooms:

```bat
php bin/demo-data.php --confirm
```

This creates two clearly named demo hostels and eight rooms. It does not create fake orders, dashboard totals or shared default passwords. You may rename the hostels before using them.

### Apache virtual host

Laragon normally stores generated virtual hosts under `C:\laragon\etc\apache2\sites-enabled`. If editing an `auto.*.conf` file, rename it to remove the `auto.` prefix so Laragon does not replace your changes. Adapt drive paths to your installation.

```apache
<VirtualHost *:80>
    ServerName hostelwash.test
    DocumentRoot "C:/laragon/www/hostelwash/public"

    <Directory "C:/laragon/www/hostelwash/public">
        AllowOverride All
        Require all granted
        Options -Indexes +FollowSymLinks
    </Directory>
</VirtualHost>
```

Ensure the Windows hosts file contains `127.0.0.1 hostelwash.test`; Laragon's automatic virtual host feature normally adds it. Reload Apache after edits. If you do not want to edit Apache, use this local development alternative:

```bat
php -S 127.0.0.1:8000 -t public public/router.php
```

For that alternative, set `APP_URL=http://127.0.0.1:8000`. Keep the terminal open. Do not use PHP's development server for public production traffic.

## Requirements

- PHP 8.2 or newer with PDO MySQL, mbstring, sessions and JSON.
- MySQL 8+ or MariaDB 10.6+; verified against MariaDB 10.11.
- Apache 2.4 with mod_rewrite, or the PHP development server locally.
- Writable `storage/private`, `storage/logs`, `storage/sessions`, and `storage/uploads`.
- No Node.js, Composer install, frontend build, or external CDN required to run the app.

Bootstrap CSS and Chart.js are included locally. Application PHP, CSS, JavaScript and views are readable source. Third-party distribution files retain their upstream formatting and license notices.

## Main features

- Public home, services, pricing, process, hostel locations, about, FAQ, contact, terms and privacy.
- Student registration, login/logout, password reset, optional email verification, profile/password changes.
- Database-driven hostels, rooms, services and slots; server-calculated estimates; coupons.
- Accepted service rates remain stable if admin later changes service prices.
- Collection confirms kg or whole-item count, bag count and unique bag tags.
- Validated status transitions, assignment checks, detailed timelines, student notifications.
- Representatives see assigned work only and can collect/deliver/record authorized cash receipts.
- Admin account activation, hostel/room/service management, representative assignments, expenses, coupons, support replies, settings and website copy.
- Cash receipts are recorded by staff; students cannot mark themselves paid. Duplicate full-payment submissions are blocked.
- Database-derived dashboard charts; date-range business reports, CSV export and browser Print / Save PDF.
- Responsive interfaces, menu-only sidebar scrolling, light/dark themes saved under `hostelwash.theme`, system theme detection and reduced-motion support.

## Business flow

Student books → representative collects and confirms quantity/tags → administrator receives at laundry → washing → ready → representative marks out for delivery → collects cash if due → delivered. Students can cancel before collection. There is no post-collection refund or cancellation UI.

When a hostel has an active representative, a pickup is automatically assigned to the representative with the lowest open pickup count. Otherwise it remains pending for admin assignment. Ready orders are assigned back to the active collecting representative where possible. Admin can reassign pickup/delivery tasks at eligible stages.

## Architecture

```text
public/index.php
  routes/web.php
    app/Controllers/
      app/Services/
        app/Models/DB.php + Order.php
          supplied MySQL schema
```

- `app/Views` — shared layout, reusable components, public/auth/portal/admin pages.
- `app/Middleware/Auth.php` — session, roles and CSRF.
- `app/Validators/Validate.php` — bounded central input validation.
- `app/Services/OrderService.php` — transactions, money, assignments, lifecycle and notifications.
- `config/resources.php` — explicit editable field allowlists for administration.
- `storage/private` — protected settings, pricing snapshots, reset/verification state and support replies.
- `database/hostelwash.sql` — original schema, unchanged.
- `PROJECT_ANALYSIS.md`, `DATABASE_MAP.md`, `DATABASE_CHANGE_PROPOSAL.md` — design decisions and data mapping.

## Email and password reset

Default local mode uses an outbox outside the public directory. Request a reset from `/forgot`, then run:

```bat
php bin/read-mail.php
```

Open the reset URL from that local terminal. Tokens expire after one hour and can be used once. The public response does not disclose whether an account exists.

For actual email delivery, configure PHP's mail transport on the host and set `MAIL_ENABLED=true` with a valid `MAIL_FROM`. This project uses PHP `mail()`, not a built-in SMTP client. Verify real delivery before going live. `REQUIRE_EMAIL_VERIFICATION=true` enables verification for student self-registration; admins and admin-created users are marked verified. Enabling verification later for existing students requires those users to request a verification link. Remember-me is intentionally omitted.

## Payments and reports

Cash recording is implemented. Online gateways and wallet balances are **not integrated**. Online/wallet enum values in the original schema are preserved but are not exposed as working payment options. Reports use paid receipt dates for cash revenue and expense dates for expenses; cash profit is received revenue minus expenses, not accrual accounting profit. Cancelled orders may appear in order counts but do not generate payment revenue.

## Storage and backups

The database schema remains unchanged. Missing settings/CMS/token/reply/pricing-snapshot structures use protected JSON files with locking. Deploy this version to one application server with persistent disk. Back up the SQL database **and `storage/private` together**; pricing snapshots are required to complete existing bookings. See `DATABASE_CHANGE_PROPOSAL.md` for proposed future normalized tables; no migration has been applied. Session files can be cleared to sign everyone out. Do not expose storage over HTTP.

## Deployment

Upload the complete application outside the web root and point the site document root to `public/`. On shared hosting where this is impossible, place only the contents of `public/` in `htdocs` and update the bootstrap path in `index.php` to the private application directory. Keep `.env`, `app/`, `config/`, `database/`, `bin/`, and `storage/` outside public access. Configure HTTPS, use an application-specific database user, set `APP_ENV=production`, set the HTTPS `APP_URL`, and configure real mail. Test the real business flow with your actual staff accounts before accepting orders.

The root `.htaccess` denies access by default. The `public/.htaccess` enables only the public application. Do not remove the root protection to work around a wrong DocumentRoot.

## Troubleshooting

| Symptom | Fix |
|---|---|
| Database unavailable | Start MySQL; verify `.env`; import the SQL; run `php bin/doctor.php`. |
| `#1046 No database selected` | Use the complete supplied SQL, which includes CREATE DATABASE and USE. On restricted hosting create/select the assigned DB and remove only those two database-selection statements in a deployment copy. |
| 403 at homepage | Point Apache DocumentRoot to `hostelwash/public`, not `hostelwash`. |
| 404 on `/login` | Enable mod_rewrite, AllowOverride All, and retain `public/.htaccess`. |
| CSS missing or links wrong | Match APP_URL to the domain/port being used; restart Apache and hard-refresh. |
| CSRF or session errors | Enable PHP sessions and make `storage/sessions` writable. |
| No rooms in order form | Admin must add active rooms to the selected active hostel. |
| Representative sees nothing | Create a representative record for the hostel and assign the order; an account role alone is insufficient. |
| No reset email locally | Run `php bin/read-mail.php`; local mode uses the protected outbox. |
| Duplicate phone/email/tag | Use a unique value. The database enforces uniqueness. |
| Private storage error | Grant the PHP worker write access to the storage directories; do not use world-writable production permissions. |

## Screenshots and verification

See `docs/screenshots/` for application screenshots and `TEST_REPORT.md` for actual validation results. The original UI image is included in `public/assets/images/design-reference.png`; the original ER illustration is in `docs/database-reference.png`. The public photo treatments reuse regions of the supplied UI image; these are low-resolution reference assets, not separate high-resolution product photos.

`tests/integration.php` is guarded: it requires APP_ENV=testing and a database name ending `_test`. Import the schema into a dedicated disposable database first. It inserts test fixtures and is not intended to run against your business database. Do not run the test with a production `.env`.
#   H o s t e l W a s h - L a u n d u r y - P l a t e f o r m  
 