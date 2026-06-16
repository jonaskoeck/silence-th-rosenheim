<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Services\Contracts\PendingActionTrackerInterface;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PendingActionTrackerTest extends TestCase
{
    use DatabaseTransactions;

    public function test_pending_server_ids_extracts_event_ids_and_ignores_other_rows(): void
    {
        $prefix = Cache::store('database')->getStore()->getPrefix();

        DB::table('cache')->insert([
            ['key' => $prefix.'server-expecting:42', 'value' => serialize('SHUTOFF'), 'expiration' => time() + 60],
            ['key' => $prefix.'server-expecting:7', 'value' => serialize('ACTIVE'), 'expiration' => time() + 60],
            // an OpenStack token row sharing the table — must be ignored
            ['key' => $prefix.'openstack-auth:abc', 'value' => serialize('token'), 'expiration' => time() + 60],
            // an expired expectation — must be ignored
            ['key' => $prefix.'server-expecting:99', 'value' => serialize('ACTIVE'), 'expiration' => time() - 10],
        ]);

        $ids = app(PendingActionTrackerInterface::class)->pendingServerIds();
        sort($ids);

        $this->assertSame([7, 42], $ids);
    }
}
