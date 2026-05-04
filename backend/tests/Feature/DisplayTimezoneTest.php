<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\InventoryRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DisplayTimezoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_inventory_run_stores_utc_and_returns_berlin(): void
    {
        // 1 July is in CEST (UTC+2), so 14:30 Berlin must round-trip to 12:30 UTC.
        $berlinTime = Carbon::create(2026, 7, 1, 14, 30, 0, 'Europe/Berlin');

        $run = InventoryRun::create([
            'start_time' => $berlinTime,
            'end_time' => $berlinTime->copy()->addHour(),
        ]);

        $rawStart = DB::table('inventory_runs')->where('id', $run->id)->value('start_time');
        $rawEnd = DB::table('inventory_runs')->where('id', $run->id)->value('end_time');

        $this->assertSame('2026-07-01 12:30:00', $rawStart);
        $this->assertSame('2026-07-01 13:30:00', $rawEnd);

        $fresh = InventoryRun::find($run->id);

        $this->assertSame('Europe/Berlin', $fresh->start_time->timezone->getName());
        $this->assertSame(14, $fresh->start_time->hour);
        $this->assertSame(30, $fresh->start_time->minute);
        $this->assertSame(15, $fresh->end_time->hour);
    }
}
