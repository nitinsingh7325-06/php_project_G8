<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Cache layer: Redis (Predis) with file-based fallback for local/XAMPP.
 */
class CacheService
{
    private ?object $redis = null;
    private bool $useRedis;

    public function __construct()
    {
        $this->useRedis = (bool) env('USE_REDIS', false);
        if ($this->useRedis && class_exists(\Predis\Client::class)) {
            try {
                $this->redis = new \Predis\Client([
                    'scheme' => 'tcp',
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'port' => (int) env('REDIS_PORT', 6379),
                    'password' => env('REDIS_PASSWORD') ?: null,
                ]);
                $this->redis->ping();
            } catch (\Throwable $e) {
                $this->redis = null;
                $this->useRedis = false;
                log_message('warning', 'Redis unavailable, using file cache', ['error' => $e->getMessage()]);
            }
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $key = $this->prefix($key);
        if ($this->redis) {
            $val = $this->redis->get($key);
            return $val !== null ? $this->unserialize($val) : $default;
        }
        $file = $this->filePath($key);
        if (!file_exists($file)) {
            return $default;
        }
        $data = json_decode((string) file_get_contents($file), true);
        if (!$data || ($data['expires'] ?? 0) < time()) {
            @unlink($file);
            return $default;
        }
        return $data['value'] ?? $default;
    }

    public function set(string $key, mixed $value, int $ttl = 600): void
    {
        $key = $this->prefix($key);
        if ($this->redis) {
            $this->redis->setex($key, $ttl, $this->serialize($value));
            return;
        }
        $dir = storage_path('cache');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        file_put_contents($this->filePath($key), json_encode([
            'expires' => time() + $ttl,
            'value' => $value,
        ]));
    }

    public function forget(string $key): void
    {
        $key = $this->prefix($key);
        if ($this->redis) {
            $this->redis->del([$key]);
            return;
        }
        $file = $this->filePath($key);
        if (file_exists($file)) {
            @unlink($file);
        }
    }

    public function increment(string $key, int $ttl = 600): int
    {
        $val = (int) $this->get($key, 0) + 1;
        $this->set($key, $val, $ttl);
        return $val;
    }

    private function prefix(string $key): string
    {
        return env('REDIS_PREFIX', 'wave:') . $key;
    }

    private function filePath(string $key): string
    {
        return storage_path('cache/' . md5($key) . '.json');
    }

    private function serialize(mixed $value): string
    {
        return is_string($value) ? $value : json_encode($value);
    }

    private function unserialize(string $value): mixed
    {
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }
}
