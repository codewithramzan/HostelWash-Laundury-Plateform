<?php

namespace app\Controllers;

use app\Middleware\Auth;
use app\Models\DB;
use app\Models\Order;
use app\Services\OrderService;
use app\Services\Settings;

final class OrderController
{
    public function index(): void
    {
        $user = Auth::require();
        $page = max(1, min(100000, (int) ($_GET['page'] ?? 1)));
        $search = substr((string) ($_GET['q'] ?? ''), 0, 100);
        $status = (string) ($_GET['status'] ?? '');
        view('portal/orders', Order::listing($user, $search, $status, $page) + [
            'title' => $user['role'] === 'representative' ? 'Pickups & Deliveries' : 'Orders',
            'page' => $page, 'search' => $search, 'status' => $status,
        ]);
    }

    public function create(): void
    {
        Auth::require(['student']);
        view('portal/new-order', ['title' => 'Schedule Laundry Pickup',
            'services' => DB::all('SELECT * FROM services WHERE status = "active" ORDER BY id'),
            'hostels' => DB::all('SELECT * FROM hostels WHERE status = "active" ORDER BY name'),
            'rooms' => DB::all('SELECT r.* FROM rooms r JOIN hostels h ON h.id = r.hostel_id WHERE r.status = "active" AND h.status = "active" ORDER BY r.room_number'),
            'slots' => Settings::slots()]);
    }

    public function store(): void
    {
        $user = Auth::require(['student']);
        $keys = ['hostel_id', 'room_id', 'service_id', 'quantity', 'pickup_date', 'pickup_slot', 'special_instructions', 'coupon'];
        $data = [];
        foreach ($keys as $key) {
            $data[$key] = input($key);
        }
        $id = OrderService::book($user, $data);
        flash('Your pickup is scheduled. Track it here.');
        redirect('/orders/' . $id);
    }

    public function show(int $id): void
    {
        $user = Auth::require();
        $order = Order::get($id, $user);
        view('portal/order', ['title' => 'Order ' . $order['order_number'], 'order' => $order,
            'history' => DB::all('SELECT h.*, s.name AS status FROM order_status_history h JOIN order_statuses s ON s.id = h.status_id WHERE h.order_id = ? ORDER BY h.id', [$id]),
            'tags' => DB::all('SELECT * FROM bag_tags WHERE order_id = ?', [$id]),
            'items' => DB::all('SELECT oi.*, s.name FROM order_items oi JOIN services s ON s.id = oi.service_id WHERE oi.order_id = ?', [$id]),
            'payments' => DB::all('SELECT * FROM payments WHERE order_id = ? ORDER BY id DESC', [$id]),
            'representatives' => $user['role'] === 'admin' ? DB::all('SELECT r.id, u.name FROM representatives r JOIN users u ON u.id = r.user_id WHERE r.hostel_id = ? AND r.is_active = 1 AND u.status = "active"', [$order['hostel_id']]) : [],
            'assignments' => DB::all('SELECT "Pickup" AS task, u.name, p.status FROM pickup_assignments p JOIN representatives r ON r.id = p.representative_id JOIN users u ON u.id = r.user_id WHERE p.order_id = ? UNION ALL SELECT "Delivery", u.name, d.status FROM delivery_assignments d JOIN representatives r ON r.id = d.representative_id JOIN users u ON u.id = r.user_id WHERE d.order_id = ?', [$id, $id])]);
    }

    public function update(int $id): void
    {
        $user = Auth::require();
        OrderService::transition($id, $user, input('status'), ['quantity' => input('quantity'), 'bags' => input('bags'), 'tag' => input('tag')]);
        flash('Order updated successfully.');
        redirect('/orders/' . $id);
    }

    public function assign(int $id): void
    {
        $user = Auth::require(['admin']);
        OrderService::assign($id, (int) input('representative_id'), input('kind'), $user);
        flash('Representative assigned.');
        redirect('/orders/' . $id);
    }

    public function payment(int $id): void
    {
        OrderService::payment($id, Auth::require(['admin', 'representative']));
        flash('Cash receipt recorded.');
        redirect('/orders/' . $id);
    }
}
