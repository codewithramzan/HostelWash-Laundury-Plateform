<?php

namespace app\Controllers;

use app\Middleware\Auth;
use app\Models\DB;
use app\Models\Order;

final class DashboardController
{
    public function index(): void
    {
        $user = Auth::require();
        [$scope, $parameters] = Order::scope($user);
        $stats = DB::one("SELECT COUNT(*) AS orders, COALESCE(SUM(o.status NOT IN ('delivered','cancelled')),0) AS active,
            COALESCE(SUM(CASE WHEN o.status <> 'cancelled' THEN o.actual_weight ELSE 0 END),0) AS weight
            FROM orders o WHERE $scope", $parameters);
        $stats['revenue'] = DB::value("SELECT COALESCE(SUM(p.amount),0) FROM payments p JOIN orders o ON o.id = p.order_id WHERE p.status = 'paid' AND $scope", $parameters);
        $stats['students'] = DB::value('SELECT COUNT(*) FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = "student" AND u.status = "active"');
        $recent = Order::listing($user)['orders'];
        $current = DB::one(Order::SELECT . " WHERE $scope AND o.status NOT IN ('delivered','cancelled') ORDER BY o.id DESC LIMIT 1", $parameters);
        $statusChart = DB::all("SELECT o.status AS label, COUNT(*) AS value FROM orders o WHERE $scope GROUP BY o.status", $parameters);
        $revenueChart = DB::all("SELECT DATE_FORMAT(p.paid_at,'%Y-%m') AS label, SUM(p.amount) AS value FROM payments p JOIN orders o ON o.id = p.order_id
            WHERE p.status = 'paid' AND p.paid_at >= DATE_SUB(CURDATE(), INTERVAL 11 MONTH) AND $scope GROUP BY label ORDER BY label", $parameters);
        view('portal/dashboard', ['title' => 'Dashboard', 'stats' => $stats, 'recent' => $recent, 'current' => $current,
            'statusChart' => $statusChart, 'revenueChart' => $revenueChart]);
    }
}
