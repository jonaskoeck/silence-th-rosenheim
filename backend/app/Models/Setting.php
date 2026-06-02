<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    public const KEY_SCHEDULE_POLL_INTERVAL_MINUTES = 'schedule_poll_interval_minutes';

    public const DEFAULT_SCHEDULE_POLL_INTERVAL_MINUTES = 5;

    /**
     * @var array<int, int>
     */
    public const ALLOWED_SCHEDULE_POLL_INTERVAL_MINUTES = [1, 5, 10, 15, 30, 60];

    public const KEY_INVENTORY_INTERVAL_MINUTES = 'inventory_interval_minutes';

    public const DEFAULT_INVENTORY_INTERVAL_MINUTES = 60;

    /**
     * @var array<int, int> 15 min, 30 min, 1 h, 3 h, 6 h, 12 h, 24 h
     */
    public const ALLOWED_INVENTORY_INTERVAL_MINUTES = [15, 30, 60, 180, 360, 720, 1440];

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

    public static function intervalLabel(int $minutes): string
    {
        if ($minutes === 1) {
            return 'Jede Minute';
        }
        if ($minutes < 60) {
            return "Alle {$minutes} Minuten";
        }
        if ($minutes === 60) {
            return 'Jede Stunde';
        }
        if ($minutes === 1440) {
            return 'Täglich';
        }
        if ($minutes % 60 === 0) {
            $hours = intdiv($minutes, 60);

            return "Alle {$hours} Stunden";
        }

        return "Alle {$minutes} Minuten";
    }
}
