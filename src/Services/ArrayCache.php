<?php

declare(strict_types=1);

namespace WhatsApp\Adapter\Services;

/**
 * Simple in-memory cache implementation
 * For production, use Redis or another persistent cache
 */
class ArrayCache implements CacheInterface
{
    private array $cache = [];
    private array $expiry = [];

    public function get(string $key): mixed
    {
        // Check if key exists and hasn't expired
        if (isset($this->cache[$key])) {
            if (!isset($this->expiry[$key]) || $this->expiry[$key] > time()) {
                return $this->cache[$key];
            }
            // Expired, remove it
            unset($this->cache[$key], $this->expiry[$key]);
        }
        
        return null;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): void
    {
        $this->cache[$key] = $value;
        if ($ttl !== null) {
            $this->expiry[$key] = time() + $ttl;
        }
    }

    public function delete(string $key): void
    {
        unset($this->cache[$key], $this->expiry[$key]);
    }

    public function clear(): void
    {
        $this->cache = [];
        $this->expiry = [];
    }

    public function has(string $key): bool
    {
        if (isset($this->cache[$key])) {
            if (!isset($this->expiry[$key]) || $this->expiry[$key] > time()) {
                return true;
            }
            // Expired, remove it
            unset($this->cache[$key], $this->expiry[$key]);
        }
        
        return false;
    }
}
