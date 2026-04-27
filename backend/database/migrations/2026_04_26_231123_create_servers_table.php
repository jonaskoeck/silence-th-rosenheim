<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            // cascadeOnDelete: a server has no meaning without its owning project.
            $table->foreignId('project_id')
                ->constrained('projects')
                ->cascadeOnDelete();
            $table->string('open_stack_server_id')->index();
            $table->string('name');
            $table->string('label');
            // keep server records when their discovery run is purged.
            $table->foreignId('discovered_by_run_id')
                ->nullable()
                ->constrained('inventory_runs')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
    }
};
