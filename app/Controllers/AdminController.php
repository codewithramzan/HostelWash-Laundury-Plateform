<?php

namespace app\Controllers;

use app\Middleware\Auth;
use app\Models\DB;
use app\Services\AdminResources;
use app\Services\FileStore;
use app\Validators\Validate;

final class AdminController
{
    public function resource(string $resource): void
    {
        Auth::require(['admin']);
        $definition = AdminResources::definition($resource);
        $page = max(1, min(100000, (int) ($_GET['page'] ?? 1)));
        $search = substr((string) ($_GET['q'] ?? ''), 0, 100);
        $where = '1=1';
        $parameters = [];
        $searchColumn = match ($resource) {
            'rooms' => 'room_number', 'coupons' => 'code', 'expenses' => 'title', 'representatives' => 'id', default => 'name',
        };
        if ($search !== '') {
            $where .= " AND `$searchColumn` LIKE ?";
            $parameters[] = "%$search%";
        }
        if ($resource === 'expenses') {
            foreach (['from' => '>=', 'to' => '<='] as $key => $operator) {
                if (!empty($_GET[$key])) {
                    $where .= " AND expense_date $operator ?";
                    $parameters[] = Validate::date((string) $_GET[$key]);
                }
            }
            if (!empty($_GET['category'])) {
                $where .= ' AND category = ?';
                $parameters[] = (string) $_GET['category'];
            }
        }
        $offset = ($page - 1) * 20;
        $options = [];
        foreach ($definition['fields'] as $column => [$name, $type, $rule]) {
            if ($type === 'lookup') {
                $options[$column] = AdminResources::options($rule);
            }
        }
        $edit = isset($_GET['edit']) ? DB::one("SELECT * FROM `$resource` WHERE id = ?", [(int) $_GET['edit']]) : null;
        $workload = $resource === 'representatives' ? DB::all('SELECT r.id,
            (SELECT COUNT(*) FROM pickup_assignments p WHERE p.representative_id = r.id AND p.status = "assigned") AS pickups,
            (SELECT COUNT(*) FROM delivery_assignments d WHERE d.representative_id = r.id AND d.status IN ("assigned","out_for_delivery")) AS deliveries FROM representatives r') : [];
        view('admin/resource', ['title' => $definition['title'], 'definition' => $definition, 'resource' => $resource,
            'records' => DB::all("SELECT * FROM `$resource` WHERE $where ORDER BY id DESC LIMIT 20 OFFSET $offset", $parameters),
            'total' => DB::value("SELECT COUNT(*) FROM `$resource` WHERE $where", $parameters), 'edit' => $edit,
            'options' => $options, 'page' => $page, 'search' => $search, 'workload' => array_column($workload, null, 'id')]);
    }

    public function saveResource(string $resource): void
    {
        AdminResources::save($resource, (int) input('id'), Auth::require(['admin']));
        flash('Record saved.');
        redirect('/admin/' . $resource);
    }

    public function users(): void
    {
        Auth::require(['admin']);
        $q = substr((string) ($_GET['q'] ?? ''), 0, 100);
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $offset = ($page - 1) * 20;
        $params = ["%$q%", "%$q%"];
        $users = DB::all('SELECT u.id,u.name,u.email,u.phone,u.status,r.name AS role,
            (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS orders
            FROM users u JOIN roles r ON r.id = u.role_id WHERE (u.name LIKE ? OR u.email LIKE ?) ORDER BY u.id DESC LIMIT 20 OFFSET ' . $offset, $params);
        $detail = !empty($_GET['view']) ? DB::one('SELECT id,name,email,phone,status FROM users WHERE id = ?', [(int) $_GET['view']]) : null;
        view('admin/users', ['title' => 'Students & Users', 'users' => $users, 'search' => $q, 'page' => $page,
            'total' => DB::value('SELECT COUNT(*) FROM users WHERE name LIKE ? OR email LIKE ?', $params),
            'detail' => $detail,
            'orders' => $detail ? DB::all('SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC LIMIT 50', [$detail['id']]) : [],
            'payments' => $detail ? DB::all('SELECT p.*,o.order_number FROM payments p JOIN orders o ON o.id=p.order_id WHERE o.user_id=? ORDER BY p.id DESC LIMIT 50', [$detail['id']]) : [],
            'complaints' => $detail ? DB::all('SELECT * FROM complaints WHERE user_id=? ORDER BY id DESC LIMIT 50', [$detail['id']]) : []]);
    }

    public function saveUser(): void
    {
        $actor = Auth::require(['admin']);
        $id = (int) input('id');
        if ($id) {
            if ($id === (int) $actor['id']) {
                throw new \DomainException('You cannot deactivate your own account.');
            }
            DB::update('users', $id, ['status' => Validate::choice(input('status'), ['active', 'inactive'])]);
        } else {
            $role = Validate::choice(input('role'), ['student', 'representative', 'admin']);
            $id = DB::insert('users', ['name' => Validate::text(input('name'), 'Name', 100),
                'email' => Validate::email(input('email')), 'phone' => Validate::text(input('phone'), 'Phone', 20, false) ?: null,
                'password' => password_hash(Validate::password(input('password')), PASSWORD_DEFAULT),
                'role_id' => DB::value('SELECT id FROM roles WHERE name = ?', [$role])]);
            FileStore::change('verified', function (&$verified) use ($id) {
                $verified[$id] = true;
            });
        }
        flash('Account saved.');
        redirect('/admin/users');
    }

    public function settings(string $section = 'settings'): void
    {
        Auth::require(['admin']);
        view('admin/settings', ['title' => $section === 'content' ? 'Website Content' : 'Settings', 'section' => $section]);
    }

    public function saveSettings(): void
    {
        Auth::require(['admin']);
        $section = Validate::choice(input('section'), ['settings', 'content']);
        $keys = $section === 'content' ? ['headline', 'intro', 'about', 'terms', 'privacy'] : ['contact_email', 'contact_phone', 'pickup_slots'];
        $values = [];
        foreach ($keys as $key) {
            $values[$key] = Validate::text(input($key), label($key), in_array($key, ['about', 'terms', 'privacy'], true) ? 10000 : 500, $key !== 'contact_email' && $key !== 'contact_phone');
        }
        if (!empty($values['contact_email'])) {
            $values['contact_email'] = Validate::email($values['contact_email']);
        }
        if (isset($values['pickup_slots'])) {
            $slots = array_filter(array_map('trim', explode(',', $values['pickup_slots'])));
            if (!$slots || count($slots) > 12 || max(array_map('mb_strlen', $slots)) > 50) {
                throw new \DomainException('Enter 1–12 pickup slots, each at most 50 characters.');
            }
        }
        FileStore::change('settings', function (&$settings) use ($values) {
            $settings = array_replace($settings, $values);
        });
        flash('Changes saved.');
        redirect('/admin/' . $section);
    }

    public function reports(): void
    {
        Auth::require(['admin']);
        $from = Validate::date((string) ($_GET['from'] ?? date('Y-m-01')));
        $to = Validate::date((string) ($_GET['to'] ?? date('Y-m-d')));
        if ($to < $from) {
            throw new \DomainException('End date must follow start date.');
        }
        $rows = DB::all('SELECT o.order_number,u.name AS student,h.name AS hostel,o.status,o.actual_weight,o.total_amount,o.created_at
            FROM orders o JOIN users u ON u.id=o.user_id JOIN hostels h ON h.id=o.hostel_id
            WHERE o.created_at >= ? AND o.created_at < DATE_ADD(?, INTERVAL 1 DAY) ORDER BY o.id', [$from, $to]);
        $revenue = DB::value('SELECT COALESCE(SUM(amount),0) FROM payments WHERE status="paid" AND paid_at >= ? AND paid_at < DATE_ADD(?,INTERVAL 1 DAY)', [$from,$to]);
        $expenses = DB::value('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE expense_date BETWEEN ? AND ?', [$from,$to]);
        $byHostel = DB::all('SELECT h.name AS label,COUNT(*) AS value FROM orders o JOIN hostels h ON h.id=o.hostel_id WHERE o.created_at >= ? AND o.created_at < DATE_ADD(?,INTERVAL 1 DAY) GROUP BY h.id,h.name', [$from,$to]);
        if (($_GET['export'] ?? '') === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="hostelwash-report.csv"');
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['Period', $from, $to]);
            fputcsv($stream, ['Cash revenue', $revenue, 'Expenses', $expenses, 'Cash profit', $revenue - $expenses]);
            fputcsv($stream, ['Order', 'Student', 'Hostel', 'Status', 'Actual kg', 'Total Rs.', 'Created']);
            foreach ($rows as $row) {
                fputcsv($stream, array_map(fn ($cell) => preg_match('/^[=+@\-\t\r]/', (string) $cell) ? "'" . $cell : $cell, $row));
            }
            fclose($stream);
            return;
        }
        view('admin/reports', ['title' => 'Business Reports', 'from' => $from, 'to' => $to, 'rows' => $rows,
            'revenue' => $revenue, 'expenses' => $expenses, 'byHostel' => $byHostel]);
    }
}
