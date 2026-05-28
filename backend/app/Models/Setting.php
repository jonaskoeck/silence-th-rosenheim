<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public const KEY_SCHEDULE_POLL_INTERVAL_MINUTES = 'schedule_poll_interval_minutes';

    public const DEFAULT_SCHEDULE_POLL_INTERVAL_MINUTES = 5;

    /**
     * Allowed values for the server-action scheduler interval.
     * Restricted to divisors of 60 so the resulting cron expression
     * `*\/{N} * * * *` produces a clean per-hour cadence.
     *
     * @var array<int, int>
     */
    public const ALLOWED_SCHEDULE_POLL_INTERVAL_MINUTES = [1, 5, 10, 15, 30, 60];

    public const KEY_INVENTORY_INTERVAL_MINUTES = 'inventory_interval_minutes';

    public const DEFAULT_INVENTORY_INTERVAL_MINUTES = 60;

    /**
     * Allowed values for the inventory job interval. Same divisor-of-60
     * constraint as the schedule poll interval.
     *
     * @var array<int, int>
     */
    public const ALLOWED_INVENTORY_INTERVAL_MINUTES = [5, 10, 15, 30, 60];

    protected $fillable = ['key', 'value'];

    public static function get(string $key, ?string $default = null): ?string
    {
        return self::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function set(string $key, ?string $value): self
    {
        return self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    public static function schedulePollIntervalMinutes(): int
    {
        return max(1, (int) self::get(
            self::KEY_SCHEDULE_POLL_INTERVAL_MINUTES,
            (string) self::DEFAULT_SCHEDULE_POLL_INTERVAL_MINUTES,
        ));
    }

    public static function inventoryIntervalMinutes(): int
    {
        return max(1, (int) self::get(
            self::KEY_INVENTORY_INTERVAL_MINUTES,
            (string) self::DEFAULT_INVENTORY_INTERVAL_MINUTES,
        ));
    }
}
