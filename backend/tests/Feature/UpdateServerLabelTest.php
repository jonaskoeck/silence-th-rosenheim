<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ServerLabel;
use App\Models\Server;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UpdateServerLabelTest extends TestCase
{
    use DatabaseTransactions;

    public function test_label_can_be_set_to_production(): void
    {
        $server = Server::factory()->create(['label' => ServerLabel::NONE]);

        $response = $this->patch(route('servers.label', $server), ['label' => 'PRODUCTION']);

        $response->assertRedirect();
        $this->assertDatabaseHas('servers', ['id' => $server->id, 'label' => 'PRODUCTION']);
    }

    public function test_label_can_be_set_to_test(): void
    {
        $server = Server::factory()->create(['label' => ServerLabel::NONE]);

        $response = $this->patch(route('servers.label', $server), ['label' => 'TEST']);

        $response->assertRedirect();
        $this->assertDatabaseHas('servers', ['id' => $server->id, 'label' => 'TEST']);
    }

    public function test_label_can_be_set_to_development(): void
    {
        $server = Server::factory()->create(['label' => ServerLabel::NONE]);

        $response = $this->patch(route('servers.label', $server), ['label' => 'DEVELOPMENT']);

        $response->assertRedirect();
        $this->assertDatabaseHas('servers', ['id' => $server->id, 'label' => 'DEVELOPMENT']);
    }

    public function test_label_can_be_reset_to_none(): void
    {
        $server = Server::factory()->create(['label' => ServerLabel::PRODUCTION]);

        $response = $this->patch(route('servers.label', $server), ['label' => 'NONE']);

        $response->assertRedirect();
        $this->assertDatabaseHas('servers', ['id' => $server->id, 'label' => 'NONE']);
    }

    public function test_invalid_label_is_rejected(): void
    {
        $server = Server::factory()->create(['label' => ServerLabel::NONE]);

        $response = $this->patch(route('servers.label', $server), ['label' => 'INVALID']);

        $response->assertSessionHasErrors('label');
        $this->assertDatabaseHas('servers', ['id' => $server->id, 'label' => 'NONE']);
    }

    public function test_missing_label_is_rejected(): void
    {
        $server = Server::factory()->create(['label' => ServerLabel::NONE]);

        $response = $this->patch(route('servers.label', $server), []);

        $response->assertSessionHasErrors('label');
        $this->assertDatabaseHas('servers', ['id' => $server->id, 'label' => 'NONE']);
    }
}
