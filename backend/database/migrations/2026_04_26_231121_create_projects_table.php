<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            // A project always belongs to exactly one region; deleting a region with
            // projects is blocked rather than cascading.
            $table->foreignId('region_id')
                ->constrained('regions')
                ->restrictOnDelete();
            $table->string('name')->nullable();
            $table->string('open_stack_project_id')->unique();
            // Encrypted via Laravel's `encrypted` cast — ciphertext exceeds varchar bounds, so TEXT.
            $table->text('app_credential_id');
            $table->text('app_credential_secret');
            // deleting an inventory run must not cascade-delete projects;
            $table->foreignId('last_inventory_run_id')
                ->nullable()
                ->constrained('inventory_runs')
                ->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
