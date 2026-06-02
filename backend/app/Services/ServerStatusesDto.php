<?php

declare(strict_types=1);

namespace App\Services;

final readonly class ServerStatusesDto
{
    /**
     * @param  array<string, string>  $statuses  Map of open_stack_server_id => raw OpenStack status.
     * @param  array<int, int>  $failedProjectIds  Project ids for which the OpenStack fetch threw.
     */
    public function __construct(
        public array $statuses,
        public array $failedProjectIds,
    ) {}

    public function statusFor(string $openStackServerId): ?string
    {
        return $this->statuses[$openStackServerId] ?? null;
    }

    public function hasFailures(): bool
    {
        return $this->failedProjectIds !== [];
    }

    public function toastTriggerPayload(): ?string
    {
        if (! $this->hasFailures()) {
            return null;
        }

        $count = count($this->failedProjectIds);

        return json_encode([
            'toast' => [
                'message' => "Status für {$count} Projekt(e) konnte nicht geladen werden.",
                'type' => 'warning',
            ],
        ]);
    }
}
