<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;

final class IdempotencyService
{
    private const TTL_SECONDS = 86400;

    public function acquire(string $key): bool
    {
        return Cache::add($this->cacheKey($key), true, self::TTL_SECONDS);
    }

    public function isKnown(string $key): bool
    {
        return Cache::has($this->cacheKey($key));
    }

    public function mark(string $key): void
    {
        Cache::put($this->cacheKey($key), true, self::TTL_SECONDS);
    }

    private function cacheKey(string $key): string
    {
        return "idempotency:{$key}";
    }
}
