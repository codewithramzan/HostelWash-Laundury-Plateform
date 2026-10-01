# Database change proposal — no migrations applied

The supplied SQL is unchanged. No database changes are required to run this version.

For a future multi-server deployment, add password_reset_tokens (user_id FK, token_hash UNIQUE, expires_at, consumed_at), email_verifications (user_id UNIQUE FK, verified_at), app_settings (key PRIMARY KEY, value JSON), complaint_replies (complaint_id FK, user_id FK, message, created_at), and order_pricing_metadata (order_id UNIQUE FK, coupon_type, coupon_value). These are currently stored as protected JSON files using locks/atomic writes, so financial and account core tables remain untouched. Move existing JSON records transactionally before enabling multiple servers. These additions require approval and an explicit versioned migration.

File-backed coupon snapshots retain the discount conditions accepted at booking, even when admins later edit coupons. Files are part of the required backup. Payments are actual cash receipt records entered by staff, not an online checkout. Gateway refunds and wallet ledgers require separate accounting design before enabling those payment methods.
