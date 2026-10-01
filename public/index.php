<?php

declare(strict_types=1);

require __DIR__ . '/../config/bootstrap.php';

try {
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
    $base = rtrim(parse_url(env('APP_URL'), PHP_URL_PATH) ?: '', '/');
    if ($base && str_starts_with($path, $base . '/')) {
        $path = substr($path, strlen($base));
    }
    $path = '/' . trim($path, '/');
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'POST') {
        app\Middleware\Auth::csrf();
    }
    foreach (require ROOT . '/routes/web.php' as [$verb, $pattern, $action]) {
        if ($verb === $method && preg_match($pattern, $path, $matches)) {
            array_shift($matches);
            $action(...$matches);
            exit;
        }
    }
    abort(404, 'The page you requested could not be found.');
} catch (DomainException $error) {
    http_response_code(422);
    view('public/error', ['title' => 'Please check your request', 'message' => $error->getMessage()]);
} catch (PDOException $error) {
    error_log(date(DATE_ATOM) . ' ' . $error->getMessage() . PHP_EOL, 3, ROOT . '/storage/logs/app.log');
    http_response_code(503);
    if ($error->getCode() === '23000') {
        http_response_code(422);
        view('public/error', ['title' => 'Unable to save', 'message' => 'That email, phone, name, code or bag tag may already be in use, or a related record is unavailable. Check your values and try again.']);
    } else {
        echo '<!doctype html><html lang="en"><meta charset="UTF-8"><title>HostelWash setup</title><body><h1>HostelWash is temporarily unavailable</h1><p>Check the database connection and import database/hostelwash.sql. See README.md for setup.</p></body></html>';
    }
} catch (Throwable $error) {
    error_log(date(DATE_ATOM) . ' ' . $error . PHP_EOL, 3, ROOT . '/storage/logs/app.log');
    http_response_code(500);
    echo '<!doctype html><html lang="en"><meta charset="UTF-8"><title>HostelWash</title><body><h1>We could not complete this request</h1><p>Please try again. The administrator can check the application log.</p></body></html>';
}
