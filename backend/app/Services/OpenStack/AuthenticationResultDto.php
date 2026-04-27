<?php

declare(strict_types=1);

namespace App\Services\OpenStack;

final readonly class AuthenticationResultDto
{
    public function __construct(
        public string $token,
        public string $projectId,
    ) {}
}
