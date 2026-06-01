<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\PendingActionTrackerInterface;
use Illuminate\Support\Facades\Session;

class PendingActionTracker implements PendingActionTrackerInterface
{
    private const SESSION_KEY = 'server_action_expectations';

    private const TTL_SECONDS = 60;

    public function record(int $serverId, string $expectedStatus): void
    {
        $store = Session::get(self::SESSION_KEY, []);
        $store[$serverId] = [
            'expecting' => $expectedStatus,
            'until' => time() + self::TTL_SECONDS,
        ];
        Session::put(self::SESSION_KEY, $store);
    }

    public function expectationFor(int $serverId, ?string $actualStatus): ?string
    {
        $store = Session::get(self::SESSION_KEY, []);
        $entry = $store[$serverId] ?? null;

        if ($entry === null) {
            return null;
        }

        if (time() > $entry['until'] || $actualStatus === $entry['expecting']) {
            unset($store[$serverId]);
            Session::put(self::SESSION_KEY, $store);

            return null;
        }

        return $entry['expecting'];
    }
}
