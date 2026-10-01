<?php

require __DIR__ . '/../config/bootstrap.php';

use app\Models\DB;

if (PHP_SAPI !== 'cli') {
    exit('CLI only.');
}
if (!in_array('--confirm', $argv, true)) {
    exit("Optional demo data: run php bin/demo-data.php --confirm on a local test database only.\n");
}
if (env('APP_ENV') !== 'local') {
    exit("Demo data is restricted to APP_ENV=local.\n");
}
DB::transaction(function () {
    foreach (['University Hostel A', 'University Hostel B'] as $name) {
        DB::run('INSERT IGNORE INTO hostels (name,description,address) VALUES (?,?,?)', [$name, 'University student accommodation', 'University campus']);
        $id = DB::value('SELECT id FROM hostels WHERE name = ?', [$name]);
        foreach (['101', '102', '201', '214'] as $number) {
            DB::run('INSERT IGNORE INTO rooms (hostel_id,room_number,capacity) VALUES (?,?,?)', [$id, $number, 3]);
        }
    }
});
echo "Demo hostels and rooms created. No users, passwords, orders or revenue were fabricated.\n";
