<?php

namespace app\Services;

final class RateLimit
{
    public static function check(string $action, int $limit = 10): void
    {
        $key = hash('sha256', $action . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'cli'));
        $allowed = FileStore::change('rate_limits', function (&$data) use ($key, $limit) {
            $now = time();
            $data = array_filter($data, fn ($entry) => $entry['until'] > $now);
            $data[$key] ??= ['until' => $now + 900, 'count' => 0];
            return ++$data[$key]['count'] <= $limit;
        });
        if (!$allowed) {
            throw new \DomainException('Too many attempts. Please try again in 15 minutes.');
        }
    }
}
