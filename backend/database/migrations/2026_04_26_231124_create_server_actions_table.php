<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')
                ->constrained('servers')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('weekday');
            $table->string('time', 5);
            $table->string('type');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('server_actions');
    }
};
