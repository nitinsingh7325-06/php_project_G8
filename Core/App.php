<?php

declare(strict_types=1);

namespace App\Core;

class App
{
    private static ?self $instance = null;
    private Router $router;
    private array $config = [];

    private function __construct()
    {
        $this->loadEnv();
        $this->config = require dirname(__DIR__, 2) . '/config/app.php';
        date_default_timezone_set($this->config['timezone'] ?? 'Asia/Kolkata');
        $this->router = new Router();
    }

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function loadEnv(): void
    {
        $root = dirname(__DIR__, 2);
        if (!file_exists($root . '/.env')) {
            return;
        }
        if (class_exists(\Dotenv\Dotenv::class)) {
            \Dotenv\Dotenv::createImmutable($root)->safeLoad();
            return;
        }
        // Already loaded via config/bootstrap.php when Composer is absent
    }

    public function router(): Router
    {
        return $this->router;
    }

    public function config(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $this->config;
        }
        $parts = explode('.', $key);
        $value = $this->config;
        foreach ($parts as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }

    public function run(): void
    {
        $this->router->dispatch();
    }
}
