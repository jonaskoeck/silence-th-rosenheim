<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Project;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class DeleteProjectTest extends TestCase
{
    use DatabaseTransactions;

    public function test_project_can_be_deleted(): void
    {
        $project = Project::factory()->create(['name' => 'To Be Deleted']);

        $response = $this->delete(route('projects.destroy', $project));

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('servers'));
        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_deleting_unknown_project_returns_404(): void
    {
        $response = $this->delete(route('projects.destroy', 999999));

        $response->assertNotFound();
    }
}
