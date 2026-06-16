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
        // Anchor the LIKE to the full key prefix (cache store prefix + our prefix)
        // so it uses the cache table's primary-key index instead of a full scan.
        // Matters now that cached OpenStack tokens share this table.
        $prefix = Cache::store('database')->getStore()->getPrefix().self::KEY_PREFIX;

        return DB::table('cache')
            ->where('key', 'like', $prefix.'%')
            ->where('expiration', '>', time())
            ->pluck('key')
            ->map(fn ($key) => (int) substr($key, strlen($prefix)))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function pendingExpectations(): array
    {
        $ids = $this->pendingServerIds();

        if ($ids === []) {
            return [];
        }

        // Read from the same database cache store that pendingServerIds() queries.
        $keys = array_map(self::keyFor(...), $ids);
        $values = Cache::store('database')->many($keys);

        $result = [];
        foreach ($ids as $id) {
            $expecting = $values[self::keyFor($id)] ?? null;
            if (is_string($expecting)) {
                $result[$id] = $expecting;
            }
        }

        return $result;
    }

    private static function keyFor(int $serverId): string
    {
        return self::KEY_PREFIX.$serverId;
    }
}
