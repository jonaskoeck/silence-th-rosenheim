<?php

declare(strict_types=1);

namespace App\Services\Contracts;

interface PendingActionTrackerInterface
{
    public function record(int $serverId, string $expectedStatus): void;

    public function expectationFor(int $serverId, ?string $actualStatus): ?string;

    /**
     * @param  array<int, ?string>  $actualStatusByServerId  Map server_id => current raw OpenStack status
     * @return array<int, ?string> Map server_id => expected status (or null if no expectation)
     */
    public function expectationsFor(array $actualStatusByServerId): array;

    /**
     * @return array<int, int> IDs of servers that currently have an active expectation.
     */
    public function pendingServerIds(): array;

    /**
     * @return array<int, string> Map server_id => expected status ('ACTIVE'|'SHUTOFF')
     *                            for servers that currently have an active expectation.
     */
    public function pendingExpectations(): array;
}
