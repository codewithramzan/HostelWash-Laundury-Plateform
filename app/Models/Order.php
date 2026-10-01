<?php

namespace app\Models;

final class Order
{
    public const STATUSES = ['pending', 'collected', 'at_laundry', 'washing', 'ready', 'out_for_delivery', 'delivered', 'cancelled'];
    public const SELECT = 'SELECT o.*, u.name AS student_name, u.email, u.phone, h.name AS hostel_name,
        r.room_number, s.name AS service_name, s.pricing_type, s.estimated_time_hours,
        (SELECT COALESCE(SUM(p.amount), 0) FROM payments p WHERE p.order_id = o.id AND p.status = "paid") AS paid
        FROM orders o JOIN users u ON u.id = o.user_id JOIN hostels h ON h.id = o.hostel_id
        JOIN rooms r ON r.id = o.room_id JOIN services s ON s.id = o.service_id';

    public static function scope(array $user): array
    {
        if ($user['role'] === 'admin') {
            return ['1=1', []];
        }
        if ($user['role'] === 'student') {
            return ['o.user_id = ?', [$user['id']]];
        }
        return ['(EXISTS (SELECT 1 FROM pickup_assignments pa JOIN representatives rep ON rep.id = pa.representative_id
            WHERE pa.order_id = o.id AND rep.user_id = ? AND rep.is_active = 1 AND rep.hostel_id = o.hostel_id AND pa.status <> "cancelled")
            OR EXISTS (SELECT 1 FROM delivery_assignments da JOIN representatives rep ON rep.id = da.representative_id
            WHERE da.order_id = o.id AND rep.user_id = ? AND rep.is_active = 1 AND rep.hostel_id = o.hostel_id AND da.status <> "cancelled"))', [$user['id'], $user['id']]];
    }

    public static function get(int $id, array $user): array
    {
        [$scope, $parameters] = self::scope($user);
        $order = DB::one(self::SELECT . " WHERE o.id = ? AND $scope", [$id, ...$parameters]);
        if (!$order) {
            throw new \DomainException('Order not found or unavailable to your account.');
        }
        return $order;
    }

    public static function listing(array $user, string $search = '', string $status = '', int $page = 1): array
    {
        [$scope, $parameters] = self::scope($user);
        if ($search !== '') {
            $scope .= ' AND (o.order_number LIKE ? OR u.name LIKE ? OR r.room_number LIKE ?)';
            array_push($parameters, "%$search%", "%$search%", "%$search%");
        }
        if (in_array($status, self::STATUSES, true)) {
            $scope .= ' AND o.status = ?';
            $parameters[] = $status;
        }
        $total = (int) DB::value('SELECT COUNT(*) FROM (' . self::SELECT . " WHERE $scope) matching", $parameters);
        $offset = (max(1, $page) - 1) * 20;
        return ['orders' => DB::all(self::SELECT . " WHERE $scope ORDER BY o.id DESC LIMIT 20 OFFSET $offset", $parameters), 'total' => $total];
    }
}
