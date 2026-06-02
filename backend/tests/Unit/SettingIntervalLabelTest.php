<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Setting;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SettingIntervalLabelTest extends TestCase
{
    /**
     * @return array<string, array{int, string}>
     */
    public static function intervals(): array
    {
        return [
            'one minute' => [1, 'Jede Minute'],
            'sub hour' => [15, 'Alle 15 Minuten'],
            'half hour' => [30, 'Alle 30 Minuten'],
            'one hour' => [60, 'Jede Stunde'],
            'three hours' => [180, 'Alle 3 Stunden'],
            'six hours' => [360, 'Alle 6 Stunden'],
            'twelve hours' => [720, 'Alle 12 Stunden'],
            'daily' => [1440, 'Täglich'],
        ];
    }

    #[DataProvider('intervals')]
    public function test_interval_label(int $minutes, string $expected): void
    {
        $this->assertSame($expected, Setting::intervalLabel($minutes));
    }
}
