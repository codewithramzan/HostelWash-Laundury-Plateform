<?php

namespace app\Middleware;

use app\Models\DB;

final class Auth
{
    public static function user(): ?array
    {
        if (empty($_SESSION['user_id'])) {
            return null;
        }
        $user = DB::one('SELECT u.*, r.name AS role FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = ? AND u.status = ?', [$_SESSION['user_id'], 'active']);
        if (!$user || !hash_equals((string) ($_SESSION['auth_version'] ?? ''), hash('sha256', $user['password']))) {
            unset($_SESSION['user_id'], $_SESSION['auth_version']);
            return null;
        }
        return $user;
    }

    public static function require(array $roles = []): array
    {
        $user = self::user();
        if (!$user) {
            redirect('/login');
        }
        if ($roles && !in_array($user['role'], $roles, true)) {
            abort(403, 'You do not have permission to open this page.');
        }
        return $user;
    }

    public static function csrf(): void
    {
        if (empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], input('_token'))) {
            abort(419, 'Your form session expired. Reload the page and try again.');
        }
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['auth_version'] = hash('sha256', $user['password']);
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
}
