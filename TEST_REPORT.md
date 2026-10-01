# HostelWash validation report

Validation used PHP 8.3.6, MariaDB 10.11.14, and Chromium, against a disposable database containing the supplied 18-table schema. Application files were tested directly, without substituting mocked payment/order persistence.

## Passed checks

- **34 backend integration checks**: database/PHP timezone agreement; order booking and coupon math; automatic pickup assignment; ownership and cross-hostel restrictions; unauthorized payment and lifecycle changes; accepted-rate/coupon snapshots after admin edits; real-weight repricing; bag tags; laundry processing; automatic delivery assignment; payment duplication; delivery; history/notifications; invalid and negative quantities; invalid IDs; wrong-hostel rooms; inactive services/hostels; invalid/expired/exhausted coupons; reservation release on cancellation; per-item integer quantities and confirmed counts; duplicate-tag transaction rollback; one-use password-reset tokens and password hashing.
- **53 HTTP checks**: public/auth routes; invalid login; invalid CSRF; authenticated pages; forbidden admin access; support submission and HTML escaping; ownership boundaries; all admin module pages; report export; expense creation; logout/session invalidation; registration; booking and redirect.
- **48 browser viewport/page combinations**: admin dashboard, order list/detail, users, services, support, reports and settings at 320, 375, 425, 768, 1024 and 1440 pixels. No page-level horizontal overflow in these checks. Wide tables scroll inside their cards.
- Theme change and persistence through reload; initialized charts; mobile navigation/escape dismissal; dependent hostel-room selection; dynamic estimate; no JavaScript exceptions during the browser runs.
- **Complete browser business flow**: representative opens assigned order and confirms collection → admin marks at laundry, washing and ready → representative marks out for delivery, confirms cash received, marks delivered → student sees delivered timeline and receipt.
- Additional order-list containment checks at 320/375/425 pixels after the final table readability fix.
- PHP source syntax validation and JavaScript syntax checks.
- Original SQL file checksum matches the uploaded SQL exactly.

## Visual review

Inspected public home, admin overview in light and dark themes, student overview, mobile booking, representative task cards and mobile order tracking. Screenshots in `docs/screenshots` contain clearly named test accounts and test orders; these records and their sessions are not included in the production package.

## Scope and practical limits

These are functional/security-boundary checks, not a formal penetration test or a concurrency/load certification. SMTP delivery, a real payment gateway, hosting-provider configuration and physical laundry operations were not tested. No online gateway is claimed. Run the included setup checks and a real staff/student test on your Laragon installation and again on the final host.

The supplied schema is unchanged. Protected file-backed supplementary data requires a single persistent application server and coordinated database/private-storage backups. See DATABASE_CHANGE_PROPOSAL.md. Legal copy is editable business policy text, not a jurisdiction-specific legal review. The interface follows the supplied green reference; it is responsive HTML, not a native Android/iOS application. Photo crops retain the limited resolution of the supplied composite image.
