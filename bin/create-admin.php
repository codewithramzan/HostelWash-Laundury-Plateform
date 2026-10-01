<?php

require __DIR__ . '/../config/bootstrap.php';

use app\Models\DB;
use app\Services\FileStore;
use app\Validators\Validate;

if (PHP_SAPI !== 'cli') {
    exit('CLI only.');
}

try {
    echo "HostelWash administrator setup\n";
    $name = Validate::text(trim(readline('Full name: ')), 'Name', 100);
    $email = Validate::email(trim(readline('Email address: ')));
    echo "Use a unique password. Input is visible in this local terminal.\n";
    $password = Validate::password(trim(readline('Password (10–72 characters): ')));
    $role = DB::value('SELECT id FROM roles WHERE name = "admin"');
    if (!$role) {
        throw new RuntimeException('Import database/hostelwash.sql first.');
    }
    if (DB::value('SELECT id FROM users WHERE email = ?', [$email])) {
        throw new RuntimeException('This email already exists. Use the password reset flow instead.');
    }
    $id = DB::insert('users', ['role_id' => $role, 'name' => $name, 'email' => $email,
        'password' => password_hash($password, PASSWORD_DEFAULT)]);
    FileStore::change('verified', function (&$verified) use ($id) {
        $verified[$id] = true;
    });
    echo "Administrator created. Sign in at " . url('/login') . "\n";
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
}
