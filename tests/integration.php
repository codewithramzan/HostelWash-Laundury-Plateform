<?php

/** Run only after importing the schema into a dedicated *_test database. */
require __DIR__ . '/../config/bootstrap.php';

use app\Models\DB;
use app\Models\Order;
use app\Services\OrderService;
use app\Services\AccountTokens;
use app\Services\FileStore;

if (PHP_SAPI !== 'cli' || env('APP_ENV') !== 'testing' || !str_ends_with(env('DB_DATABASE'), '_test')) {
    exit("Set APP_ENV=testing and use a dedicated database ending in _test.\n");
}

$passed = 0;
function check(bool $condition, string $name): void
{
    global $passed;
    if (!$condition) {
        throw new RuntimeException('FAIL: ' . $name);
    }
    $passed++;
    echo "PASS: $name\n";
}
function rejected(callable $callback, string $name): void
{
    try {
        $callback();
    } catch (DomainException $error) {
        check(true, $name);
        return;
    }
    check(false, $name);
}
function account(string $name, string $role): array
{
    $email = strtolower(str_replace(' ', '-', $name)) . '@example.test';
    $id = DB::insert('users', ['name' => $name, 'email' => $email,
        'role_id' => DB::value('SELECT id FROM roles WHERE name = ?', [$role]),
        'password' => password_hash('TestPass2026!', PASSWORD_DEFAULT)]);
    return DB::one('SELECT u.*, r.name AS role FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=?', [$id]);
}

check(abs(strtotime(DB::value('SELECT NOW()')) - time()) < 5, 'Database and application timestamps use the same timezone');
$admin = account('Test Admin', 'admin');
$student = account('Test Student', 'student');
$other = account('Other Student', 'student');
$rep = account('Test Representative', 'representative');
$foreignRep = account('Other Representative', 'representative');
$hostel = DB::insert('hostels', ['name' => 'University Hostel A']);
$otherHostel = DB::insert('hostels', ['name' => 'University Hostel B']);
$room = DB::insert('rooms', ['hostel_id' => $hostel, 'room_number' => '214', 'capacity' => 3]);
$otherRoom = DB::insert('rooms', ['hostel_id' => $otherHostel, 'room_number' => '102', 'capacity' => 2]);
DB::insert('representatives', ['user_id' => $rep['id'], 'hostel_id' => $hostel]);
DB::insert('representatives', ['user_id' => $foreignRep['id'], 'hostel_id' => $otherHostel]);
$service = DB::value('SELECT id FROM services WHERE pricing_type="per_kg" ORDER BY id LIMIT 1');
$coupon = DB::insert('coupons', ['code' => 'FRESH10', 'discount_type' => 'percentage', 'discount_value' => 10, 'usage_limit' => 2]);
$data = ['hostel_id' => $hostel, 'room_id' => $room, 'service_id' => $service,
    'quantity' => '3', 'pickup_date' => date('Y-m-d', strtotime('+1 day')), 'pickup_slot' => '5–7 PM',
    'special_instructions' => 'Please separate delicate clothes.', 'coupon' => 'FRESH10'];
$id = OrderService::book($student, $data);
$order = Order::get($id, $student);
check((float) $order['total_amount'] === 486.0, 'Estimated total and coupon are computed server-side');
check(count(Order::listing($rep)['orders']) === 1, 'Assigned representative can see pickup');
rejected(fn () => Order::get($id, $other), 'Other student cannot read order');
rejected(fn () => Order::get($id, $foreignRep), 'Other hostel representative cannot read order');
rejected(fn () => Order::get(999999, $student), 'Invalid order ID rejected');
rejected(fn () => OrderService::payment($id, $student), 'Student cannot confirm payment');
rejected(fn () => OrderService::payment($id, $admin), 'Cannot pay before final weighing');
rejected(fn () => OrderService::transition($id, $student, 'collected'), 'Student cannot collect own order');
rejected(fn () => OrderService::transition($id, $admin, 'ready'), 'Cannot skip processing states');
DB::update('services', (int) $service, ['price' => 999]);
DB::update('coupons', $coupon, ['discount_value' => 90]);
OrderService::transition($id, $rep, 'collected', ['quantity' => '4.2', 'bags' => '2', 'tag' => 'HW-QA-001']);
$order = Order::get($id, $student);
check((float) $order['subtotal'] === 756.0 && (float) $order['total_amount'] === 680.4, 'Final weight uses accepted price and coupon snapshots');
check(DB::value('SELECT COUNT(*) FROM bag_tags WHERE order_id=?', [$id]) == 2, 'One unique tag created per bag');
rejected(fn () => OrderService::transition($id, $rep, 'at_laundry'), 'Representative cannot update processing stage');
foreach (['at_laundry', 'washing', 'ready'] as $stage) {
    OrderService::transition($id, $admin, $stage);
}
check(DB::value('SELECT COUNT(*) FROM delivery_assignments WHERE order_id=?', [$id]) == 1, 'Delivery assigned when laundry ready');
OrderService::transition($id, $rep, 'out_for_delivery');
OrderService::payment($id, $rep);
rejected(fn () => OrderService::payment($id, $rep), 'Duplicate cash payment blocked');
OrderService::transition($id, $rep, 'delivered');
check(Order::get($id, $student)['status'] === 'delivered', 'Student sees delivered status');
check(DB::value('SELECT COUNT(*) FROM order_status_history WHERE order_id=?', [$id]) == 8, 'Full lifecycle and payment audit history persisted');
check(DB::value('SELECT COUNT(*) FROM notifications WHERE user_id=?', [$student['id']]) >= 8, 'Lifecycle notifications persisted');
DB::update('services', (int) $service, ['price' => 180]);
DB::update('coupons', $coupon, ['discount_value' => 10]);
foreach (['-1', '0', '1000', 'abc'] as $quantity) {
    rejected(fn () => OrderService::book($student, array_replace($data, ['quantity' => $quantity])), 'Invalid quantity rejected: ' . $quantity);
}
rejected(fn () => OrderService::book($student, array_replace($data, ['room_id' => $otherRoom])), 'Room must belong to selected hostel');
rejected(fn () => OrderService::book($student, array_replace($data, ['coupon' => 'NOPE'])), 'Invalid coupon rejected');
DB::update('coupons', $coupon, ['end_date' => date('Y-m-d', strtotime('-1 day'))]);
rejected(fn () => OrderService::book($student, $data), 'Expired coupon rejected');
DB::update('coupons', $coupon, ['end_date' => null]);
DB::update('services', (int) $service, ['status' => 'inactive']);
rejected(fn () => OrderService::book($student, $data), 'Inactive service rejected');
DB::update('services', (int) $service, ['status' => 'active']);
DB::update('hostels', $hostel, ['status' => 'inactive']);
rejected(fn () => OrderService::book($student, $data), 'Inactive hostel rejected');
DB::update('hostels', $hostel, ['status' => 'active']);
$cancelId = OrderService::book($student, $data);
rejected(fn () => OrderService::book($student, $data), 'Coupon usage limit enforced');
OrderService::transition($cancelId, $student, 'cancelled');
check(DB::value('SELECT used_count FROM coupons WHERE id=?', [$coupon]) == 1, 'Cancellation releases coupon reservation');
$itemData = array_replace($data, ['service_id' => DB::value('SELECT id FROM services WHERE pricing_type="per_item"'), 'quantity' => '5', 'coupon' => '']);
rejected(fn () => OrderService::book($student, array_replace($itemData, ['quantity' => '2.5'])), 'Per-item service requires whole quantities');
$itemId = OrderService::book($student, $itemData);
OrderService::transition($itemId, $rep, 'collected', ['quantity' => '6', 'bags' => '1', 'tag' => 'HW-ITEM-QA']);
check((float) Order::get($itemId, $student)['total_amount'] === 300.0, 'Per-item collection recalculates final total');
$pendingId = OrderService::book($student, array_replace($data, ['coupon' => '']));
try {
    OrderService::transition($pendingId, $rep, 'collected', ['quantity' => '4', 'bags' => '1', 'tag' => 'HW-ITEM-QA']);
    check(false, 'Duplicate tag rejected');
} catch (PDOException $error) {
    check(Order::get($pendingId, $student)['status'] === 'pending', 'Duplicate bag tag rolls back entire collection');
}
FileStore::change('tokens', function (&$tokens) use ($student) {
    $tokens[hash('sha256', 'test-reset-token')] = ['user_id' => $student['id'], 'purpose' => 'reset', 'expires' => time() + 300];
});
AccountTokens::consume('test-reset-token', 'reset', 'TestPass2026!');
rejected(fn () => AccountTokens::consume('test-reset-token', 'reset', 'AnotherPass123'), 'Reset token is single-use');
check(password_verify('TestPass2026!', DB::value('SELECT password FROM users WHERE id=?', [$student['id']])), 'Password reset stores valid password hash');
echo "\n$passed integration checks passed.\n";
