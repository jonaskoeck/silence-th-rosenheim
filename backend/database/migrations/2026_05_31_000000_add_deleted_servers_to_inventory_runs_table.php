<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_runs', function (Blueprint $table) {
            $table->json('deleted_servers')->nullable()->after('found_new_servers');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_runs', function (Blueprint $table) {
            $table->dropColumn('deleted_servers');
        });
    }
};
