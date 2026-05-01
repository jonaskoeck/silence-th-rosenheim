<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ServerLabel;
use App\Models\Project;
use App\Models\Server;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServerFactory extends Factory
{
    protected $model = Server::class;

    public function definition(): array
    {
        return [
            'project_id'           => Project::factory(),
            'open_stack_server_id' => $this->faker->uuid(),
            'name'                 => $this->faker->word(),
            'label'                => ServerLabel::NONE,
            'discovered_by_run_id' => null,
        ];
    }
}
