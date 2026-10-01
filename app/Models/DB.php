<?php

namespace app\Models;

use PDO;
use Throwable;

final class DB
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection === null) {
            self::$connection = new PDO(
                'mysql:host=' . env('DB_HOST', '127.0.0.1') . ';port=' . env('DB_PORT', '3306')
                . ';dbname=' . env('DB_DATABASE', 'hostelwash') . ';charset=utf8mb4',
                env('DB_USERNAME', 'root'),
                env('DB_PASSWORD'),
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                 PDO::ATTR_EMULATE_PREPARES => false]
            );
            // Keep SQL timestamps aligned with the configured application timezone.
            $timezone = self::$connection->prepare('SET time_zone = ?');
            $timezone->execute([date('P')]);
        }
        return self::$connection;
    }

    public static function run(string $sql, array $parameters = []): \PDOStatement
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($parameters);
        return $statement;
    }

    public static function all(string $sql, array $parameters = []): array
    {
        return self::run($sql, $parameters)->fetchAll();
    }

    public static function one(string $sql, array $parameters = []): ?array
    {
        return self::run($sql, $parameters)->fetch() ?: null;
    }

    public static function value(string $sql, array $parameters = []): mixed
    {
        return self::run($sql, $parameters)->fetchColumn();
    }

    public static function insert(string $table, array $fields): int
    {
        // Identifiers are supplied by application code, never directly by HTTP input.
        $columns = implode(',', array_map(fn ($key) => '`' . $key . '`', array_keys($fields)));
        $placeholders = implode(',', array_fill(0, count($fields), '?'));
        self::run("INSERT INTO `$table` ($columns) VALUES ($placeholders)", array_values($fields));
        return (int) self::connection()->lastInsertId();
    }

    public static function update(string $table, int $id, array $fields): void
    {
        $set = implode(',', array_map(fn ($key) => '`' . $key . '` = ?', array_keys($fields)));
        self::run("UPDATE `$table` SET $set WHERE id = ?", [...array_values($fields), $id]);
    }

    public static function transaction(callable $callback): mixed
    {
        self::connection()->beginTransaction();
        try {
            $result = $callback();
            self::connection()->commit();
            return $result;
        } catch (Throwable $error) {
            self::connection()->rollBack();
            throw $error;
        }
    }
}
