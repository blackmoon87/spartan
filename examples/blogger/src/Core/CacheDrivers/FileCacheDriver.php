<?php

declare(strict_types=1);

namespace App\Core\CacheDrivers;

use App\Core\CacheDriverInterface;

/**
 * File-based cache driver — zero dependencies, works out of the box.
 * Stores serialized values in the filesystem under storage/cache/.
 *
 * Configure in .env:
 *   CACHE_DRIVER=file
 *   CACHE_PATH=storage/cache
 */
class FileCacheDriver implements CacheDriverInterface
{
    private string $cachePath;

    public function __construct(array $config)
    {
        $path = $config['path'] ?? dirname(__DIR__, 3) . '/storage/cache';

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        $this->cachePath = rtrim($path, '/');
    }

    public function put(string $key, mixed $value, int $ttl): void
    {
        $expires = $ttl > 0 ? time() + $ttl : 0;
        $payload = serialize(['expires' => $expires, 'value' => $value]);
        file_put_contents($this->filePath($key), $payload, LOCK_EX);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->filePath($key);

        if (!file_exists($file)) {
            return $default;
        }

        $payload = unserialize((string) file_get_contents($file));

        if ($payload['expires'] !== 0 && time() > $payload['expires']) {
            unlink($file);
            return $default;
        }

        return $payload['value'];
    }

    public function has(string $key): bool
    {
        $file = $this->filePath($key);
        if (!file_exists($file)) {
            return false;
        }
        $payload = unserialize((string) file_get_contents($file));
        if ($payload['expires'] !== 0 && time() > $payload['expires']) {
            unlink($file);
            return false;
        }
        return true;
    }

    public function forget(string $key): void
    {
        $file = $this->filePath($key);
        if (file_exists($file)) {
            unlink($file);
        }
    }

    public function flush(): void
    {
        foreach (glob($this->cachePath . '/*.cache') ?: [] as $file) {
            unlink($file);
        }
    }

    private function filePath(string $key): string
    {
        return $this->cachePath . '/' . md5($key) . '.cache';
    }
}
