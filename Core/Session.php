<?php

declare(strict_types=1);

namespace App\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_set_cookie_params([
                'lifetime' => (int) env('SESSION_LIFETIME', 120) * 60,
                'path' => '/',
                'secure' => filter_var(env('SESSION_SECURE', false), FILTER_VALIDATE_BOOL),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function csrf(): string
    {
        return $_SESSION['_csrf'] ?? '';
    }

    public static function verifyCsrf(string $token): bool
    {
        $csrf = self::csrf();
        return $csrf !== '' && $token !== '' && hash_equals($csrf, $token);
    }

    public static function user(): ?array
    {
        return self::get('user');
    }

    public static function isLoggedIn(): bool
    {
        return self::user() !== null;
    }

    public static function isAdmin(): bool
    {
        $user = self::user();
        return $user && in_array($user['role'] ?? '', ['admin', 'staff'], true);
    }
}
