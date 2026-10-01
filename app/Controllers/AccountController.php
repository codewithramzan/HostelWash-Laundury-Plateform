<?php

namespace app\Controllers;

use app\Middleware\Auth;
use app\Models\DB;
use app\Models\Order;
use app\Services\FileStore;
use app\Services\OrderService;
use app\Validators\Validate;

final class AccountController
{
    public function profile(): void
    {
        Auth::require();
        view('portal/profile', ['title' => 'My Profile']);
    }

    public function saveProfile(): void
    {
        $user = Auth::require();
        $fields = ['name' => Validate::text(input('name'), 'Name', 100),
            'phone' => Validate::text(input('phone'), 'Phone', 20, false) ?: null];
        if (input('password') !== '') {
            if (!password_verify(input('current_password'), $user['password'])) {
                throw new \DomainException('Current password is incorrect.');
            }
            $password = Validate::password(input('password'));
            if ($password !== input('password_confirmation')) {
                throw new \DomainException('New passwords do not match.');
            }
            $fields['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        DB::update('users', (int) $user['id'], $fields);
        if (isset($fields['password'])) {
            Auth::login(DB::one('SELECT * FROM users WHERE id = ?', [$user['id']]));
        }
        flash('Profile updated.');
        redirect('/profile');
    }

    public function payments(): void
    {
        $user = Auth::require();
        [$scope, $params] = Order::scope($user);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * 30;
        view('portal/payments', ['title' => 'Payments', 'page' => $page,
            'total' => DB::value("SELECT COUNT(*) FROM payments p JOIN orders o ON o.id = p.order_id WHERE $scope", $params),
            'payments' => DB::all("SELECT p.*, o.order_number FROM payments p JOIN orders o ON o.id = p.order_id WHERE $scope ORDER BY p.id DESC LIMIT 30 OFFSET $offset", $params)]);
    }

    public function notifications(): void
    {
        $user = Auth::require();
        view('portal/notifications', ['title' => 'Notifications', 'notifications' => DB::all('SELECT * FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 100', [$user['id']])]);
    }

    public function readNotifications(): void
    {
        $user = Auth::require();
        DB::run('UPDATE notifications SET is_read = 1 WHERE user_id = ?', [$user['id']]);
        flash('Notifications marked as read.');
        redirect('/notifications');
    }

    public function support(): void
    {
        $user = Auth::require();
        $status = (string) ($_GET['status'] ?? '');
        $where = $user['role'] === 'admin' ? '1=1' : 'c.user_id = ?';
        $params = $user['role'] === 'admin' ? [] : [$user['id']];
        if (in_array($status, ['open', 'in_progress', 'resolved'], true)) {
            $where .= ' AND c.status = ?';
            $params[] = $status;
        }
        view('portal/support', ['title' => 'Support', 'status' => $status,
            'complaints' => DB::all("SELECT c.*, u.name FROM complaints c JOIN users u ON u.id = c.user_id WHERE $where ORDER BY c.id DESC LIMIT 100", $params),
            'orders' => Order::listing($user)['orders'], 'replies' => FileStore::read('replies')]);
    }

    public function sendSupport(): void
    {
        $user = Auth::require();
        $orderId = (int) input('order_id');
        if ($orderId) {
            Order::get($orderId, $user);
        }
        DB::insert('complaints', ['user_id' => $user['id'], 'order_id' => $orderId ?: null,
            'subject' => Validate::text(input('subject'), 'Subject', 100),
            'message' => Validate::text(input('message'), 'Message', 5000)]);
        flash('Your request has been sent to the team.');
        redirect('/support');
    }

    public function replySupport(int $id): void
    {
        $user = Auth::require(['admin']);
        $complaint = DB::one('SELECT * FROM complaints WHERE id = ?', [$id]);
        if (!$complaint) {
            throw new \DomainException('Support request not found.');
        }
        $status = Validate::choice(input('status'), ['open', 'in_progress', 'resolved']);
        $reply = Validate::text(input('reply'), 'Reply', 5000, false);
        DB::update('complaints', $id, ['status' => $status]);
        if ($reply !== '') {
            FileStore::change('replies', function (&$data) use ($id, $reply, $user) {
                $data[$id][] = ['message' => $reply, 'name' => $user['name'], 'created_at' => date('Y-m-d H:i:s')];
            });
        }
        OrderService::notify((int) $complaint['user_id'], 'Support request updated', $complaint['subject'] . ': ' . label($status));
        flash('Support request updated.');
        redirect('/support');
    }
}
