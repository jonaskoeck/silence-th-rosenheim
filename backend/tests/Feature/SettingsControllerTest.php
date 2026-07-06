<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_update_inventory_interval_persists_allowed_value(): void
    {
        $response = $this->putJson(route('settings.inventory-interval'), ['minutes' => 30]);

        $response->assertOk();
        $response->assertJson(['inventory_interval_minutes' => 30]);
        $this->assertSame('30', Setting::get(Setting::KEY_INVENTORY_INTERVAL_MINUTES));
    }

    public function test_update_inventory_interval_rejects_value_outside_allowed_list(): void
    {
        $response = $this->putJson(route('settings.inventory-interval'), ['minutes' => 1]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('minutes');
    }
}
