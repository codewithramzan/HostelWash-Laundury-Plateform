<?php

namespace app\Services;

final class FileStore
{
    private static function path(string $name): string
    {
        if (!preg_match('/^[a-z0-9_-]+$/', $name)) {
            throw new \LogicException('Invalid storage key.');
        }
        return ROOT . '/storage/private/' . $name . '.json';
    }

    public static function change(string $name, callable $callback): mixed
    {
        $path = self::path($name);
        $lock = fopen($path . '.lock', 'c');
        if (!$lock || !flock($lock, LOCK_EX)) {
            throw new \RuntimeException('Private storage is not writable.');
        }
        $temporary = null;
        try {
            $data = is_file($path) ? json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR) : [];
            $result = $callback($data);
            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
            $temporary = tempnam(dirname($path), 'store-');
            if ($temporary === false || file_put_contents($temporary, $json) !== strlen($json)) {
                throw new \RuntimeException('Cannot write private storage.');
            }
            chmod($temporary, 0600);
            if (!rename($temporary, $path)) {
                throw new \RuntimeException('Cannot replace private storage.');
            }
            return $result;
        } finally {
            if ($temporary && is_file($temporary)) {
                unlink($temporary);
            }
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    public static function read(string $name): array
    {
        $path = self::path($name);
        $lock = fopen($path . '.lock', 'c');
        if (!$lock || !flock($lock, LOCK_SH)) {
            throw new \RuntimeException('Cannot read private storage.');
        }
        try {
            return is_file($path) ? json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR) : [];
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
