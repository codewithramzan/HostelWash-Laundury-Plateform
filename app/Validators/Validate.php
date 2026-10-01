<?php

namespace app\Validators;

use DomainException;

final class Validate
{
    public static function text(string $value, string $name, int $max, bool $required = true): string
    {
        if (($required && $value === '') || mb_strlen($value) > $max) {
            throw new DomainException("$name is required and must be at most $max characters.");
        }
        return $value;
    }

    public static function email(string $value): string
    {
        if (strlen($value) > 100 || !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Enter a valid email address.');
        }
        return strtolower($value);
    }

    public static function number(string $value, string $name, float $min, float $max, bool $integer = false): float
    {
        if (!is_numeric($value) || !is_finite((float) $value) || (float) $value < $min || (float) $value > $max
            || ($integer && floor((float) $value) !== (float) $value)) {
            throw new DomainException("$name must be between $min and $max" . ($integer ? ' and a whole number.' : '.'));
        }
        return round((float) $value, 2);
    }

    public static function choice(string $value, array $choices): string
    {
        if (!in_array($value, $choices, true)) {
            throw new DomainException('Choose a valid option.');
        }
        return $value;
    }

    public static function date(string $value): string
    {
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new DomainException('Enter a valid date.');
        }
        return $value;
    }

    public static function password(string $value): string
    {
        if (strlen($value) < 10 || strlen($value) > 72) {
            throw new DomainException('Use a password between 10 and 72 characters.');
        }
        return $value;
    }
}
