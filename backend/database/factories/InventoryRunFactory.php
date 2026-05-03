<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\InventoryRun;
use Illuminate\Database\Eloquent\Factories\Factory;

class InventoryRunFactory extends Factory
{
    protected $model = InventoryRun::class;

    public function definition(): array
    {
        return [
            'start_time'              => now(),
            'end_time'                => now(),
            'triggered_automatically' => false,
            'had_errors'              => false,
            'found_new_servers'       => false,
        ];
    }
}
