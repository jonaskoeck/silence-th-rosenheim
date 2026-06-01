<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface PendingActionTrackerInterface
{
    /**
     * Record that a start/stop action was fired for the given server, expecting
     * the given OpenStack status ('ACTIVE' or 'SHUTOFF') as the settled result.
     */
    public function record(int $serverId, string $expectedStatus): void;

    /**
     * Return the expected status for the given server if an unsettled expectation
     * exists, else null. Clears the entry when the actual status matches the
     * expectation or when the TTL has expired.
     */
    public function expectationFor(int $serverId, ?string $actualStatus): ?string;
}
