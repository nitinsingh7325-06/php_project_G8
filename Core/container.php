<?php
/**
 * Dependency Injection Container
 */

namespace App\Core;

class Container
{
    private array $bindings = [];
    private array $instances = [];

    public function set(string $key, $value): void
    {
        $this->bindings[$key] = $value;
    }

    public function get(string $key)
    {
        if (isset($this->instances[$key])) {
            return $this->instances[$key];
        }

        if (isset($this->bindings[$key])) {
            $binding = $this->bindings[$key];
            
            if (is_callable($binding)) {
                return $binding($this);
            }
            
            if (is_object($binding)) {
                $this->instances[$key] = $binding;
                return $binding;
            }
            
            if (class_exists($binding)) {
                $object = new $binding();
                $this->instances[$key] = $object;
                return $object;
            }
        }

        throw new \Exception("Binding not found: {$key}");
    }

    public function has(string $key): bool
    {
        return isset($this->bindings[$key]) || isset($this->instances[$key]);
    }

    public function singleton(string $key, $value): void
    {
        $this->set($key, $value);
    }
}