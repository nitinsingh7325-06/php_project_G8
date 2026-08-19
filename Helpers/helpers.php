<?php

declare(strict_types=1);

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);
        if ($value === false || $value === null || $value === '') {
            return $default;
        }
        return match (strtolower((string) $value)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'null', '(null)' => null,
            default => $value,
        };
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return \App\Core\App::getInstance()->config($key, $default);
    }
}

if (!function_exists('app_base_path')) {
    function app_base_path(): string
    {
        $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
        return rtrim($script, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        $basePath = app_base_path();

        $configured = rtrim((string) env('APP_URL', ''), '/');
        if ($configured !== '') {
            // If app runs from /public but APP_URL omits it, fix automatically
            if (str_ends_with($basePath, '/public') && !str_ends_with($configured, '/public')) {
                $configured .= '/public';
            }
            return $path === '' ? $configured : $configured . '/' . $path;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $relative = $basePath . ($path !== '' ? '/' . $path : '');
        return $scheme . '://' . $host . $relative;
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        // Always serve from the public folder (works on XAMPP /wave/public)
        $basePath = app_base_path();
        $assetPath = ($basePath === '' ? '' : $basePath) . '/assets/' . ltrim($path, '/');

        $configured = rtrim((string) env('APP_URL', ''), '/');
        if ($configured !== '') {
            if (str_ends_with($basePath, '/public') && !str_ends_with($configured, '/public')) {
                $configured .= '/public';
            }
            return $configured . '/assets/' . ltrim($path, '/');
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        return $scheme . '://' . $host . $assetPath;
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(\App\Core\Session::csrf()) . '">';
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \App\Core\Session::csrf();
    }
}

if (!function_exists('old')) {
    function old(string $key, string $default = ''): string
    {
        return e((string) (\App\Core\Session::flash('old_' . $key) ?? $default));
    }
}

if (!function_exists('money')) {
    function money(float|int|string $amount): string
    {
        return '₹' . number_format((float) $amount, 2);
    }
}

if (!function_exists('format_phone')) {
    function format_phone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';
        if (strlen($digits) === 10) {
            return '+91' . $digits;
        }
        if (!str_starts_with($phone, '+')) {
            return '+' . $digits;
        }
        return $phone;
    }
}

if (!function_exists('booking_id')) {
    function booking_id(): string
    {
        return 'TW-' . strtoupper(bin2hex(random_bytes(4))) . '-' . date('ymd');
    }
}

if (!function_exists('loyalty_tier')) {
    function loyalty_tier(int $points): string
    {
        if ($points >= (int) env('LOYALTY_DIAMOND_THRESHOLD', 5000)) {
            return 'Diamond';
        }
        if ($points >= (int) env('LOYALTY_PLATINUM_THRESHOLD', 2000)) {
            return 'Platinum';
        }
        if ($points >= (int) env('LOYALTY_GOLD_THRESHOLD', 500)) {
            return 'Gold';
        }
        return 'Standard';
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return dirname(__DIR__, 2) . '/storage/' . ltrim($path, '/');
    }
}

if (!function_exists('upload_path')) {
    function upload_path(string $path = ''): string
    {
        return dirname(__DIR__, 2) . '/public/uploads/' . ltrim($path, '/');
    }
}

if (!function_exists('log_message')) {
    function log_message(string $level, string $message, array $context = []): void
    {
        $dir = storage_path('logs');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        $line = sprintf(
            "[%s] %s: %s %s\n",
            date('Y-m-d H:i:s'),
            strtoupper($level),
            $message,
            $context ? json_encode($context) : ''
        );
        file_put_contents($dir . '/app.log', $line, FILE_APPEND);
    }
}
