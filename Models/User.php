<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class User extends Model
{
    protected static string $table = 'users';

    public static function findByPhone(string $phone): ?array
    {
        return self::firstWhere('phone', format_phone($phone));
    }

    public static function findByEmail(string $email): ?array
    {
        return self::firstWhere('email', $email);
    }

    public static function createCustomer(array $data): int
    {
        return self::create([
            'uuid' => self::uuid(),
            'role' => 'customer',
            'name' => $data['name'],
            'phone' => format_phone($data['phone']),
            'email' => $data['email'] ?? null,
            'password' => !empty($data['password']) ? password_hash($data['password'], PASSWORD_BCRYPT) : null,
            'phone_verified_at' => $data['phone_verified_at'] ?? null,
            'is_active' => 1,
        ]);
    }

    public static function staffList(): array
    {
        return Database::fetchAll("SELECT id, name, email, phone, avatar FROM users WHERE role='staff' AND is_active=1 ORDER BY name");
    }

    public static function customers(int $page = 1): array
    {
        return self::paginate($page, 20, "role='customer'", [], 'id DESC');
    }

    public static function safe(array $user): array
    {
        unset($user['password']);
        return $user;
    }

    public static function uuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
