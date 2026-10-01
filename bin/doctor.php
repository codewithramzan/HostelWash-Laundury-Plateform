<?php

require __DIR__ . '/../config/bootstrap.php';

if (PHP_SAPI !== 'cli') {
    exit('CLI only.');
}

$failures = 0;
$check = function (string $name, bool $ok) use (&$failures): void {
    echo ($ok ? '[OK] ' : '[FAIL] ') . $name . PHP_EOL;
    $failures += $ok ? 0 : 1;
};
$check('PHP 8.2+', version_compare(PHP_VERSION, '8.2', '>='));
foreach (['pdo_mysql', 'mbstring', 'session', 'json'] as $extension) {
    $check($extension, extension_loaded($extension));
}
$check('.env configuration exists', is_file(ROOT . '/.env'));
$check('APP_URL configured', filter_var(env('APP_URL'), FILTER_VALIDATE_URL) !== false);
foreach (['private', 'logs', 'uploads', 'sessions'] as $directory) {
    $check('Writable storage/' . $directory, is_writable(ROOT . '/storage/' . $directory));
}
try {
    app\Models\DB::connection();
    $check('Database connection', true);
    $check('All 18 tables present', (int) app\Models\DB::value('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = ?', [env('DB_DATABASE')]) >= 18);
    $check('Required roles', (int) app\Models\DB::value('SELECT COUNT(*) FROM roles WHERE name IN ("student","admin","representative")') === 3);
} catch (Throwable $error) {
    $check('Database: ' . $error->getMessage(), false);
}
exit($failures ? 1 : 0);
