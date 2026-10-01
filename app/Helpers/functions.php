<?php

function env(string $key, string $default = ''): string
{
    return (string) ($GLOBALS['env'][$key] ?? $default);
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = '/'): string
{
    return rtrim(env('APP_URL', ''), '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): never
{
    header('Location: ' . url($path), true, 303);
    exit;
}

function csrf(): string
{
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
    return '<input type="hidden" name="_token" value="' . e($_SESSION['csrf']) . '">';
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = compact('message', 'type');
}

function money(mixed $value): string
{
    return 'Rs. ' . number_format((float) $value, 2);
}

function label(string $value): string
{
    return ucwords(str_replace('_', ' ', $value));
}

function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    $user = app\Middleware\Auth::user();
    $settings = app\Services\Settings::get();
    $contentTemplate = ROOT . '/app/Views/' . $template . '.php';
    require ROOT . '/app/Views/layouts/main.php';
}

function abort(int $code, string $message): never
{
    http_response_code($code);
    view('public/error', ['title' => (string) $code, 'message' => $message]);
    exit;
}

function input(string $key, string $default = ''): string
{
    $value = $_POST[$key] ?? $default;
    if (!is_scalar($value)) {
        throw new DomainException('Invalid form input.');
    }
    return trim((string) $value);
}

/** Trusted local SVG icons; the argument is an application-defined icon key. */
function icon(string $key): string
{
    $paths = [
        '⌂' => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1Z"/><path d="M9 21v-8h6v8"/>',
        '+' => '<path d="M12 4v16M4 12h16"/>',
        '▤' => '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M9 7h6M9 12h6M9 17h4"/>',
        '♙' => '<circle cx="12" cy="7" r="4"/><path d="M4 21v-3a8 8 0 0 1 16 0v3Z"/>',
        '♧' => '<path d="M19 3C9 3 4 7 4 13a7 7 0 0 0 14 0c0-4 1-7 1-10Z"/><path d="M4 22 14 10"/>',
        '▦' => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M8 7h1m6 0h1M8 11h1m6 0h1M9 21v-6h6v6"/>',
        '▥' => '<path d="M5 21V3h14v18M3 21h18M15 12h.1"/>',
        '◇' => '<path d="m3 10 7-7h10v10l-7 7Z"/><circle cx="16" cy="7" r="1"/>',
        '▣' => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/>',
        '↗' => '<path d="M5 19 19 5M7 5h12v12"/>',
        '⚙' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v3m0 14v3M2 12h3m14 0h3M5 5l2 2m10 10 2 2M5 19l2-2M17 7l2-2"/>',
        '▧' => '<rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18M8 9v11"/>',
        '◉' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="4"/><path d="m5.6 5.6 3.6 3.6m5.6 5.6 3.6 3.6m0-12.8-3.6 3.6m-5.6 5.6-3.6 3.6"/>',
        '◷' => '<circle cx="12" cy="12" r="9"/><path d="M12 6v6l4 2"/>',
        '₨' => '<path d="M6 4h12M6 8h12M9 4c8 0 8 8 0 8H6l10 8"/>',
        '▱' => '<path d="m3 8 9-5 9 5v10l-9 4-9-4ZM3 8l9 5 9-5M12 13v9"/>',
        'bell' => '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4"/>',
    ];
    $path = $paths[$key] ?? $paths['▤'];
    return '<svg class="ui-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $path . '</svg>';
}
