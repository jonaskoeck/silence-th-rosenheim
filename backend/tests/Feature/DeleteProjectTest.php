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
        $project = Project::create([
            'name' => 'To Be Deleted',
            'open_stack_project_id' => 'a4d3f1c2b5e64d7a8c9b0e1f2a3b4c5d',
            'app_credential_id' => 'cred-id-123',
            'app_credential_secret' => 'cred-secret-xyz',
        ]);

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
