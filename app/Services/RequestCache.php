<?php

declare(strict_types=1);

namespace App\Services;

/** Request-scoped cache to avoid duplicate DB reads within one HTTP request. */
final class RequestCache
{
    /** @var array<string, mixed> */
    private static $store = [];

    public static function has(string $key): bool
    {
        return array_key_exists($key, self::$store);
    }

    /**
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        return self::has($key) ? self::$store[$key] : $default;
    }

    /**
     * @param mixed $value
     */
    public static function set(string $key, $value): void
    {
        self::$store[$key] = $value;
    }

    /**
     * @template T
     * @param callable(): T $resolver
     * @return T
     */
    public static function remember(string $key, callable $resolver)
    {
        if (self::has($key)) {
            return self::get($key);
        }
        $value = $resolver();
        self::set($key, $value);

        return $value;
    }

    public static function forget(string $key): void
    {
        unset(self::$store[$key]);
    }
}
