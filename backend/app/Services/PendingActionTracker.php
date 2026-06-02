<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\PendingActionTrackerInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PendingActionTracker implements PendingActionTrackerInterface
{
    private const KEY_PREFIX = 'server-expecting:';

    private const TTL_SECONDS = 90;

    public function record(int $serverId, string $expectedStatus): void
    {
        Cache::put(self::keyFor($serverId), $expectedStatus, self::TTL_SECONDS);
    }

    public function expectationFor(int $serverId, ?string $actualStatus): ?string
    {
        $expecting = Cache::get(self::keyFor($serverId));

        if ($expecting === null) {
            return null;
        }

        if ($actualStatus === $expecting) {
            Cache::forget(self::keyFor($serverId));

            return null;
        }

        return $expecting;
    }

    public function expectationsFor(array $actualStatusByServerId): array
    {
        if ($actualStatusByServerId === []) {
            return [];
        }

        $serverIds = array_keys($actualStatusByServerId);
        $keys = array_map(self::keyFor(...), $serverIds);
        $values = Cache::many($keys);

        $result = [];
        $fulfilled = [];

        foreach ($serverIds as $i => $serverId) {
            $expecting = $values[$keys[$i]] ?? null;

            if ($expecting === null) {
                $result[$serverId] = null;
            } elseif ($actualStatusByServerId[$serverId] === $expecting) {
                $fulfilled[] = $keys[$i];
                $result[$serverId] = null;
            } else {
                $result[$serverId] = $expecting;
            }
        }

        foreach ($fulfilled as $key) {
            Cache::forget($key);
        }

        return $result;
    }

    public function pendingServerIds(): array
    {
        return DB::table('cache')
            ->where('key', 'like', '%'.self::KEY_PREFIX.'%')
            ->where('expiration', '>', time())
            ->pluck('key')
            ->map(fn ($key) => (int) substr($key, strrpos($key, self::KEY_PREFIX) + strlen(self::KEY_PREFIX)))
            ->filter()
            ->values()
            ->all();
    }

    private static function keyFor(int $serverId): string
    {
        return self::KEY_PREFIX.$serverId;
    }
}
