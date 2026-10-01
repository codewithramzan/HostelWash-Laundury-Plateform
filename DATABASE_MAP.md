# Database map

The supplied schema has **18 tables**. SQL names, types, indexes, foreign keys and seed lookups are unchanged. All writes use prepared PDO statements through `app/Models/DB.php`; orders additionally use `app/Models/Order.php` for scoped reads.

| Table | Primary / foreign keys | Model / service / controller | Feature |
|---|---|---|---|
| roles | id; unique name | DB, Auth | Database-defined access roles |
| users | id; role_id → roles; unique email/phone | DB, Auth, AuthController, AdminController | Registration, login, profile, account administration |
| hostels | id; unique name | DB, AdminResources | Active hostels, public locations, booking |
| rooms | id; hostel_id → hostels; unique hostel/room number | DB, AdminResources, OrderService | Valid room selection |
| services | id; unique name | DB, AdminResources, OrderService | Per-kg/per-item pricing and turnaround |
| representatives | id; user_id → users, hostel_id → hostels | DB, OrderService, AdminResources | Hostel representative membership |
| order_statuses | id; unique name | DB, OrderService | Canonical status history lookup |
| coupons | id; unique code | DB, OrderService, AdminResources | Server-side discount validation and use reservations |
| orders | id; user_id, hostel_id, room_id, service_id, coupon_id FKs | Order, OrderService, OrderController | Booking, access scoping, workflow, totals |
| order_items | id; order_id → orders, service_id → services | DB, OrderService | Snapshotted unit prices and quantities |
| order_status_history | id; order_id, status_id, updated_by FKs | DB, OrderService | Timeline and actor audit |
| payments | id; order_id → orders; unique transaction_id | DB, OrderService, AccountController | Cash receipts and payment history |
| expenses | id; created_by → users | DB, AdminResources, AdminController | Expenses and cash-profit reporting |
| pickup_assignments | id; order_id, representative_id FKs | DB, OrderService | Pickup assignment and collection timestamp |
| delivery_assignments | id; order_id, representative_id FKs | DB, OrderService | Delivery assignment and completion timestamp |
| bag_tags | id; order_id → orders; unique tag_number | DB, OrderService | One tag per bag |
| complaints | id; order_id nullable, user_id FKs | DB, AccountController | Support ticket and status |
| notifications | id; user_id → users | DB, OrderService, AccountController | Read/unread notifications |

## Core relationships and states
A user has one role and many orders. Hostels have rooms and representative records. Orders reference one service, contain item quantities/rates, and have many assignments, tags, payments and history records. A representative user may belong to multiple hostels; access requires a live assignment to the exact order, not just a matching role.

Order states: pending → collected → at_laundry → washing → ready → out_for_delivery → delivered. Cancellation is available only while pending. Staff cash receipt is a separate event and does not move the lifecycle. Every transition is checked and locked server-side. Payment totals aggregate paid records only.

Coupon usage is reserved at booking and released on pre-collection cancellation. Coupon conditions and service pricing type are snapshotted to protected storage. The SQL item row preserves the accepted unit price. This prevents later service/coupon edits changing an accepted order's pricing rules.

Per-item quantities are stored in order_items.quantity; weight columns remain NULL. For per-kg services, quantity mirrors the current estimated/confirmed weight. Representatives cannot change the accepted rate or discounts.
