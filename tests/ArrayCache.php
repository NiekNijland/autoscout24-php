<?php

declare(strict_types=1);

namespace NiekNijland\AutoScout24\Tests;

use Psr\SimpleCache\CacheInterface;

class ArrayCache implements CacheInterface
{
    /** @var array<string, mixed> */
    private array $store = [];

    /** @var array<string, \DateInterval|int|null> */
    private array $ttls = [];

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store[$key] ?? $default;
    }

    public function set(string $key, mixed $value, \DateInterval|int|null $ttl = null): bool
    {
        $this->store[$key] = $value;
        $this->ttls[$key] = $ttl;

        return true;
    }

    public function delete(string $key): bool
    {
        unset($this->store[$key], $this->ttls[$key]);

        return true;
    }

    /**
     * Get the TTL that was passed when a key was set (for testing purposes).
     */
    public function getTtl(string $key): \DateInterval|int|null
    {
        return $this->ttls[$key] ?? null;
    }

    public function clear(): bool
    {
        $this->store = [];

        return true;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->store);
    }

    /**
     * @param  iterable<string>  $keys
     * @return iterable<string, mixed>
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    /**
     * @param  iterable<string, mixed>  $values
     */
    public function setMultiple(iterable $values, \DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            $this->set($key, $value, $ttl);
        }

        return true;
    }

    /**
     * @param  iterable<string>  $keys
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            $this->delete($key);
        }

        return true;
    }
}
