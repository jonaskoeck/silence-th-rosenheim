<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'name'                   => $this->faker->company(),
            'open_stack_project_id'  => $this->faker->uuid(),
            'app_credential_id'      => $this->faker->uuid(),
            'app_credential_secret'  => $this->faker->password(32),
            'last_inventory_run_id'  => null,
        ];
    }
}
