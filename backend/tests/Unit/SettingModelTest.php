<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Setting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SettingModelTest extends TestCase
{
    use DatabaseTransactions;

    public function test_get_returns_default_when_key_missing(): void
    {
        $this->assertSame('fallback', Setting::get('does_not_exist', 'fallback'));
    }

    public function test_set_creates_row_and_get_returns_value(): void
    {
        Setting::set('some_key', 'some_value');

        $this->assertSame('some_value', Setting::get('some_key'));
        $this->assertDatabaseHas('settings', ['key' => 'some_key', 'value' => 'some_value']);
    }

    public function test_set_overwrites_existing_value_without_creating_duplicate_row(): void
    {
        Setting::set('some_key', 'first');
        Setting::set('some_key', 'second');

        $this->assertSame('second', Setting::get('some_key'));
        $this->assertSame(1, Setting::query()->where('key', 'some_key')->count());
    }

    public function test_inventory_interval_returns_stored_value(): void
    {
        Setting::set(Setting::KEY_INVENTORY_INTERVAL_MINUTES, '15');

        $this->assertSame(15, Setting::inventoryIntervalMinutes());
    }

    public function test_inventory_interval_returns_default_when_unset(): void
    {
        $this->assertSame(
            Setting::DEFAULT_INVENTORY_INTERVAL_MINUTES,
            Setting::inventoryIntervalMinutes(),
        );
    }
}
