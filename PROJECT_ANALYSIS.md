# HostelWash project analysis

The input is a 18-table MySQL/MariaDB schema, an ER illustration, a composite UI reference, and requirements. There is no existing executable application to preserve.

## Architecture
PHP 8.2 custom MVC: public/index.php → routes/web.php → controllers → services → PDO models. Views contain presentation only. A shared green design system provides public navigation, portal sidebars, cards, tables, timelines, and mobile navigation.

## UI inventory
Public home, services, how it works, pricing, hostels, about, FAQ, contact, terms, privacy; login/register/reset; student overview/new order/orders/tracking/payments/profile/support; representative pickup and delivery cards; administrator overview/orders/users/hostels/rooms/representatives/services/payments/expenses/coupons/support/reports/settings/content.

## Data and implementation
The SQL file is authoritative, copied byte-for-byte. SQL differs from the illustration by providing coupon_id, subtotal, discount_amount, and the at_laundry/out_for_delivery stages: the application follows SQL. Items provide the snapshotted service rate and item quantity. Bag count is represented by one bag_tags row per bag, not an invented column.

## Gaps and decisions
No reset-token, verification, CMS, settings, or support-reply tables exist. Protected JSON files outside public hold tokens, content/settings, and support replies. These require a single application server/shared persistent filesystem. Password reset uses PHP mail when configured; local mail goes to a protected outbox. Optional email verification uses the same protected token store. Remember-me is intentionally omitted. Payments record cash receipts; no payment gateway or wallet balance is claimed. Legal copy is an editable starting policy, to be reviewed by the business.

## Security
Prepared statements; session rotation; database-backed role validation on each request; CSRF on every mutation; strict ownership/assignment checks; transaction and row locking for order transitions, coupon reservations, and payments; output escaping; bounded inputs; file locks for security storage; no public web installer or default administrator password.

## Phases
1. Preserve and map inputs. 2. Build foundation and shared UI. 3. Implement booking/pricing/assignment/workflow/payments. 4. Build administration and supporting modules. 5. Exercise lifecycle and negative authorization/validation cases. 6. Package documented Laragon setup.
