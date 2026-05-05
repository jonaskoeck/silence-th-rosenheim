<?php

declare(strict_types=1);

namespace App\Enums;

enum Weekday: int
{
    case MONDAY = 1;
    case TUESDAY = 2;
    case WEDNESDAY = 4;
    case THURSDAY = 8;
    case FRIDAY = 16;
    case SATURDAY = 32;
    case SUNDAY = 64;

    /**
     * @param  array<int, self>  $cases
     */
    public static function combine(array $cases): int
    {
        $bitmask = 0;

        foreach ($cases as $case) {
            $bitmask |= $case->value;
        }

        return $bitmask;
    }

    /**
     * @return array<int, self>
     */
    public static function unpack(int $bitmask): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $case): bool => ($bitmask & $case->value) === $case->value,
        ));
    }
}
