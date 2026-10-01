<?php

namespace app\Services;

use app\Models\DB;
use app\Validators\Validate;

final class AdminResources
{
    public static function definitions(): array
    {
        return require ROOT . '/config/resources.php';
    }

    public static function definition(string $resource): array
    {
        return self::definitions()[$resource] ?? throw new \DomainException('Unknown management page.');
    }

    public static function options(string $lookup): array
    {
        return match ($lookup) {
            'hostels' => DB::all('SELECT id, name FROM hostels ORDER BY name'),
            'representative_users' => DB::all('SELECT u.id, u.name FROM users u JOIN roles r ON r.id = u.role_id WHERE r.name = "representative" AND u.status = "active" ORDER BY u.name'),
            default => [],
        };
    }

    public static function save(string $resource, int $id, array $user): void
    {
        $definition = self::definition($resource);
        $fields = [];
        foreach ($definition['fields'] as $column => [$name, $type, $rule]) {
            $value = input($column);
            $fields[$column] = match ($type) {
                'text' => Validate::text($value, $name, $rule),
                'optional' => Validate::text($value, $name, $rule, false) ?: null,
                'select' => Validate::choice($value, $rule),
                'money' => Validate::number($value, $name, 0, $rule),
                'integer' => Validate::number($value, $name, 1, $rule, true),
                'optional_integer' => $value === '' ? null : Validate::number($value, $name, 1, $rule, true),
                'date' => Validate::date($value),
                'optional_date' => $value === '' ? null : Validate::date($value),
                'lookup' => Validate::choice($value, array_map('strval', array_column(self::options($rule), 'id'))),
            };
        }
        if ($resource === 'coupons') {
            $fields['code'] = strtoupper($fields['code']);
            if (($fields['discount_type'] === 'percentage' && $fields['discount_value'] > 100)
                || ($fields['start_date'] && $fields['end_date'] && $fields['end_date'] < $fields['start_date'])) {
                throw new \DomainException('Check the coupon percentage and date range.');
            }
        }
        if ($resource === 'expenses' && !$id) {
            $fields['created_by'] = $user['id'];
        }
        if ($id) {
            if (!DB::one("SELECT id FROM `$resource` WHERE id = ?", [$id])) {
                throw new \DomainException('Record not found.');
            }
            if ($resource === 'services') {
                $previous = DB::one('SELECT pricing_type FROM services WHERE id = ?', [$id]);
                if ($previous['pricing_type'] !== $fields['pricing_type'] && DB::value('SELECT COUNT(*) FROM orders WHERE service_id = ?', [$id])) {
                    throw new \DomainException('Create a new service to change the pricing type after orders exist.');
                }
            }
            if ($resource === 'representatives') {
                $old = DB::one('SELECT * FROM representatives WHERE id = ?', [$id]);
                if ($old['user_id'] != $fields['user_id'] || $old['hostel_id'] != $fields['hostel_id']) {
                    throw new \DomainException('Create a new assignment record instead of moving an existing representative record.');
                }
            }
            if ($resource === 'rooms') {
                $old = DB::one('SELECT * FROM rooms WHERE id = ?', [$id]);
                if ($old['hostel_id'] != $fields['hostel_id'] && DB::value('SELECT COUNT(*) FROM orders WHERE room_id = ?', [$id])) {
                    throw new \DomainException('A room with order history cannot be moved to another hostel.');
                }
            }
            DB::update($resource, $id, $fields);
        } else {
            DB::insert($resource, $fields);
        }
    }
}
