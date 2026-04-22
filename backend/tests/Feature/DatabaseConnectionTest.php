<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    public function test_database_connection_is_reachable(): void
    {
        $result = DB::select('SELECT 1 as value');

        $this->assertSame(1, $result[0]->value);
    }
}
