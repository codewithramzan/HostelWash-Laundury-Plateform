<?php

namespace app\Services;

use app\Models\DB;
use app\Models\Order;
use app\Validators\Validate;
use DomainException;

final class OrderService
{
    public static function notify(int $userId, string $title, string $message): void
    {
        DB::insert('notifications', ['user_id' => $userId, 'title' => $title, 'message' => $message]);
    }

    private static function history(array $order, string $status, int $actor, string $notes = ''): void
    {
        $statusId = DB::value('SELECT id FROM order_statuses WHERE name = ?', [$status]);
        if (!$statusId) {
            throw new DomainException('Required status lookup is missing. Import the supplied SQL lookup data.');
        }
        DB::insert('order_status_history', [
            'order_id' => $order['id'], 'status_id' => $statusId,
            'updated_by' => $actor, 'notes' => $notes,
        ]);
        self::notify((int) $order['user_id'], 'Order ' . label($status), $order['order_number'] . ': ' . label($status) . ($notes ? '. ' . $notes : ''));
    }

    public static function discount(float $subtotal, ?array $coupon): float
    {
        if (!$coupon || $subtotal < (float) $coupon['min_order_amount']) {
            return 0;
        }
        $discount = $coupon['discount_type'] === 'percentage'
            ? $subtotal * (float) $coupon['discount_value'] / 100
            : (float) $coupon['discount_value'];
        return round(min($subtotal, max(0, $discount)), 2);
    }

    public static function book(array $user, array $data): int
    {
        if ($user['role'] !== 'student') {
            throw new DomainException('Only student accounts can book laundry.');
        }
        $date = Validate::date($data['pickup_date']);
        if ($date < date('Y-m-d') || $date > date('Y-m-d', strtotime('+60 days'))) {
            throw new DomainException('Choose a pickup within the next 60 days.');
        }
        $slot = Validate::choice($data['pickup_slot'], Settings::slots());
        $notes = Validate::text($data['special_instructions'], 'Instructions', 2000, false);

        return DB::transaction(function () use ($user, $data, $date, $slot, $notes) {
            $room = DB::one('SELECT r.* FROM rooms r JOIN hostels h ON h.id = r.hostel_id WHERE r.id = ? AND h.id = ? AND r.status = "active" AND h.status = "active" FOR UPDATE', [$data['room_id'], $data['hostel_id']]);
            $service = DB::one('SELECT * FROM services WHERE id = ? AND status = "active" FOR UPDATE', [$data['service_id']]);
            if (!$room || !$service) {
                throw new DomainException('Choose an active hostel, a room in that hostel, and an active service.');
            }
            $quantity = Validate::number($data['quantity'], 'Quantity', 0.1, 999, $service['pricing_type'] === 'per_item');
            $subtotal = round($quantity * (float) $service['price'], 2);
            $coupon = null;
            if ($data['coupon'] !== '') {
                $coupon = DB::one('SELECT * FROM coupons WHERE code = ? FOR UPDATE', [strtoupper($data['coupon'])]);
                if (!$coupon || $coupon['status'] !== 'active'
                    || ($coupon['start_date'] && $coupon['start_date'] > date('Y-m-d'))
                    || ($coupon['end_date'] && $coupon['end_date'] < date('Y-m-d'))
                    || ($coupon['usage_limit'] !== null && $coupon['used_count'] >= $coupon['usage_limit'])
                    || $subtotal < (float) $coupon['min_order_amount']) {
                    throw new DomainException('This coupon is invalid, expired, exhausted, or below its minimum order value.');
                }
                DB::run('UPDATE coupons SET used_count = used_count + 1 WHERE id = ?', [$coupon['id']]);
            }
            $discount = self::discount($subtotal, $coupon);
            $id = DB::insert('orders', [
                'order_number' => 'HW-' . strtoupper(bin2hex(random_bytes(7))),
                'user_id' => $user['id'], 'hostel_id' => $room['hostel_id'], 'room_id' => $room['id'],
                'service_id' => $service['id'], 'coupon_id' => $coupon['id'] ?? null,
                'estimated_weight' => $service['pricing_type'] === 'per_kg' ? $quantity : null,
                'pickup_date' => $date, 'pickup_slot' => $slot, 'special_instructions' => $notes,
                'subtotal' => $subtotal, 'discount_amount' => $discount, 'total_amount' => $subtotal - $discount,
            ]);
            DB::insert('order_items', [
                'order_id' => $id, 'service_id' => $service['id'], 'quantity' => $quantity,
                'unit_price' => $service['price'], 'total_price' => $subtotal,
            ]);
            FileStore::change('pricing', function (&$pricing) use ($id, $coupon, $service) {
                $pricing[$id] = ['coupon' => $coupon, 'pricing_type' => $service['pricing_type']];
            });
            $representative = DB::one('SELECT rep.id FROM representatives rep JOIN users u ON u.id = rep.user_id
                WHERE rep.hostel_id = ? AND rep.is_active = 1 AND u.status = "active"
                ORDER BY (SELECT COUNT(*) FROM pickup_assignments pa WHERE pa.representative_id = rep.id AND pa.status = "assigned"), rep.id LIMIT 1', [$room['hostel_id']]);
            if ($representative) {
                DB::insert('pickup_assignments', ['order_id' => $id, 'representative_id' => $representative['id'], 'pickup_time' => $date . ' 00:00:00']);
            }
            $order = DB::one('SELECT * FROM orders WHERE id = ?', [$id]);
            self::history($order, 'pending', (int) $user['id'], 'Pickup scheduled.');
            return $id;
        });
    }

    private static function assigned(array $user, array $order, string $kind): void
    {
        if ($user['role'] === 'admin') {
            return;
        }
        $table = $kind === 'pickup' ? 'pickup_assignments' : 'delivery_assignments';
        $assignment = DB::one("SELECT a.id FROM $table a JOIN representatives r ON r.id = a.representative_id
            WHERE a.order_id = ? AND r.user_id = ? AND r.hostel_id = ? AND r.is_active = 1 AND a.status IN ('assigned','out_for_delivery')",
            [$order['id'], $user['id'], $order['hostel_id']]);
        if ($user['role'] !== 'representative' || !$assignment) {
            throw new DomainException('This task is not assigned to you.');
        }
    }

    public static function transition(int $id, array $user, string $next, array $data = []): void
    {
        Order::get($id, $user);
        DB::transaction(function () use ($id, $user, $next, $data) {
            $order = DB::one('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$id]);
            $allowed = [
                'pending' => ['collected', 'cancelled'], 'collected' => ['at_laundry'],
                'at_laundry' => ['washing'], 'washing' => ['ready'], 'ready' => ['out_for_delivery'],
                'out_for_delivery' => ['delivered'], 'delivered' => [], 'cancelled' => [],
            ];
            if (!in_array($next, $allowed[$order['status']], true)) {
                throw new DomainException('That status change is not allowed, or the order was already updated.');
            }
            if ($user['role'] === 'student' && $next !== 'cancelled') {
                throw new DomainException('Students may only cancel an uncollected order.');
            }
            if ($user['role'] === 'representative' && !in_array($next, ['collected', 'out_for_delivery', 'delivered'], true)) {
                throw new DomainException('Only the administrator can update laundry processing.');
            }
            $fields = ['status' => $next];
            $notes = '';
            if ($next === 'collected') {
                self::assigned($user, $order, 'pickup');
                $item = DB::one('SELECT * FROM order_items WHERE order_id = ? ORDER BY id LIMIT 1', [$id]);
                $snapshot = FileStore::read('pricing')[$id] ?? null;
                if (!$snapshot || !$item) {
                    throw new DomainException('Pricing snapshot is missing. Restore private storage before collection.');
                }
                $perItem = $snapshot['pricing_type'] === 'per_item';
                $quantity = Validate::number((string) ($data['quantity'] ?? ''), $perItem ? 'Item count' : 'Actual weight', 0.1, 999, $perItem);
                $bagCount = (int) Validate::number((string) ($data['bags'] ?? ''), 'Bags', 1, 25, true);
                $tag = Validate::text((string) ($data['tag'] ?? ''), 'Bag tag', 40);
                if (!preg_match('/^[A-Za-z0-9-]+$/', $tag)) {
                    throw new DomainException('Bag tags may contain only letters, numbers and hyphens.');
                }
                $subtotal = round($quantity * (float) $item['unit_price'], 2);
                $discount = self::discount($subtotal, $snapshot['coupon']);
                $fields += ['actual_weight' => $perItem ? null : $quantity, 'subtotal' => $subtotal,
                    'discount_amount' => $discount, 'total_amount' => $subtotal - $discount];
                DB::update('order_items', (int) $item['id'], ['quantity' => $quantity, 'total_price' => $subtotal]);
                for ($bag = 1; $bag <= $bagCount; $bag++) {
                    DB::insert('bag_tags', ['order_id' => $id, 'tag_number' => $bagCount > 1 ? $tag . '-' . $bag : $tag]);
                }
                DB::run('UPDATE pickup_assignments SET status = "collected", actual_pickup_time = NOW() WHERE order_id = ? AND status = "assigned"', [$id]);
                $notes = "Confirmed $quantity " . ($perItem ? 'items' : 'kg') . ", $bagCount bag(s).";
            }
            if ($next === 'ready') {
                $pickup = DB::one('SELECT pa.representative_id FROM pickup_assignments pa JOIN representatives rep ON rep.id = pa.representative_id JOIN users u ON u.id = rep.user_id WHERE pa.order_id = ? AND pa.status = "collected" AND rep.is_active = 1 AND u.status = "active" ORDER BY pa.id DESC LIMIT 1', [$id]);
                if ($pickup) {
                    DB::insert('delivery_assignments', ['order_id' => $id, 'representative_id' => $pickup['representative_id']]);
                }
            }
            if (in_array($next, ['out_for_delivery', 'delivered'], true)) {
                self::assigned($user, $order, 'delivery');
                DB::run('UPDATE delivery_assignments SET status = ?, delivery_time = ' . ($next === 'delivered' ? 'NOW()' : 'NULL') . ' WHERE order_id = ? AND status IN ("assigned","out_for_delivery")', [$next, $id]);
            }
            if ($next === 'cancelled') {
                if ($order['coupon_id']) {
                    DB::run('UPDATE coupons SET used_count = GREATEST(0, used_count - 1) WHERE id = ?', [$order['coupon_id']]);
                }
                DB::run('UPDATE pickup_assignments SET status = "cancelled" WHERE order_id = ? AND status = "assigned"', [$id]);
            }
            DB::update('orders', $id, $fields);
            self::history($order, $next, (int) $user['id'], $notes);
        });
    }

    public static function assign(int $id, int $representativeId, string $kind, array $user): void
    {
        if ($user['role'] !== 'admin') {
            throw new DomainException('Administrator access required.');
        }
        DB::transaction(function () use ($id, $representativeId, $kind) {
            $order = DB::one('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$id]);
            $rep = DB::one('SELECT r.* FROM representatives r JOIN users u ON u.id = r.user_id WHERE r.id = ? AND r.is_active = 1 AND u.status = "active"', [$representativeId]);
            if (!$order || !$rep || $rep['hostel_id'] != $order['hostel_id']) {
                throw new DomainException('Select an active representative for this hostel.');
            }
            Validate::choice($kind, ['pickup', 'delivery']);
            if (($kind === 'pickup' && $order['status'] !== 'pending')
                || ($kind === 'delivery' && !in_array($order['status'], ['ready', 'out_for_delivery'], true))) {
                throw new DomainException('This task cannot be assigned at the current order stage.');
            }
            $table = $kind === 'pickup' ? 'pickup_assignments' : 'delivery_assignments';
            DB::run("UPDATE $table SET status = 'cancelled' WHERE order_id = ? AND status IN ('assigned','out_for_delivery')", [$id]);
            DB::insert($table, ['order_id' => $id, 'representative_id' => $representativeId,
                'status' => $order['status'] === 'out_for_delivery' ? 'out_for_delivery' : 'assigned']);
            self::notify((int) $rep['user_id'], label($kind) . ' assigned', $order['order_number'] . ' has been assigned to you.');
        });
    }

    public static function payment(int $id, array $user): void
    {
        $order = Order::get($id, $user);
        if ($user['role'] === 'student') {
            throw new DomainException('Only authorized staff can confirm cash received.');
        }
        DB::transaction(function () use ($id, $user) {
            $order = DB::one('SELECT * FROM orders WHERE id = ? FOR UPDATE', [$id]);
            if (in_array($order['status'], ['pending', 'cancelled'], true)) {
                throw new DomainException('Confirm collection and the final price before receiving payment.');
            }
            if ($user['role'] !== 'admin') {
                $valid = DB::value('SELECT COUNT(*) FROM delivery_assignments a JOIN representatives r ON r.id = a.representative_id WHERE a.order_id = ? AND r.user_id = ? AND r.is_active = 1 AND r.hostel_id = ? AND a.status <> "cancelled"', [$id, $user['id'], $order['hostel_id']]);
                if (!$valid) {
                    throw new DomainException('Only the assigned delivery representative can record this payment.');
                }
            }
            $paid = (float) DB::value('SELECT COALESCE(SUM(amount),0) FROM payments WHERE order_id = ? AND status = "paid"', [$id]);
            $balance = round((float) $order['total_amount'] - $paid, 2);
            if ($balance <= 0) {
                throw new DomainException('This order is already fully paid.');
            }
            DB::insert('payments', ['order_id' => $id, 'amount' => $balance, 'method' => 'cash',
                'transaction_id' => 'CASH-' . bin2hex(random_bytes(12)), 'status' => 'paid', 'paid_at' => date('Y-m-d H:i:s')]);
            self::notify((int) $order['user_id'], 'Payment confirmed', money($balance) . ' cash received for ' . $order['order_number'] . '.');
            self::history($order, $order['status'], (int) $user['id'], 'Cash receipt: ' . money($balance));
        });
    }
}
