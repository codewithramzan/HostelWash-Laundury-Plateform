<?php

declare(strict_types=1);

const ROOT = __DIR__ . '/..';

spl_autoload_register(function (string $class): void {
    $path = ROOT . '/' . str_replace('\\', '/', $class) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

$envFile = ROOT . '/.env';
$GLOBALS['env'] = is_file($envFile)
    ? (parse_ini_file($envFile, false, INI_SCANNER_RAW) ?: [])
    : [];
require ROOT . '/app/Helpers/functions.php';
date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Karachi'));

if (PHP_SAPI !== 'cli') {
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', ROOT . '/storage/logs/app.log');
    session_save_path(ROOT . '/storage/sessions');
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name('hostelwash_session');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => str_starts_with(env('APP_URL', ''), 'https://'),
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'");
    header('Cache-Control: no-store');
}
