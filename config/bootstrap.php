<?php

declare(strict_types=1);

/**
 * Lightweight bootstrap (works without Composer).
 */

spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file = dirname(__DIR__) . '/app/' . $relative . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

require_once dirname(__DIR__) . '/app/Helpers/helpers.php';

(function (): void {
    $envFile = dirname(__DIR__) . '/.env';
    if (!file_exists($envFile)) {
        $example = dirname(__DIR__) . '/.env.example';
        if (file_exists($example)) {
            copy($example, $envFile);
        } else {
            return;
        }
    }
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$name, $value] = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
        putenv("{$name}={$value}");
    }
})();
