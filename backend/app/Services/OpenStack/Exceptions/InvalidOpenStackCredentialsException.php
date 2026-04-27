<?php

declare(strict_types=1);

namespace App\Services\OpenStack\Exceptions;

use RuntimeException;
use Throwable;

class InvalidOpenStackCredentialsException extends RuntimeException
{
    public static function fromInvalidCredentials(): self
    {
        return new self('OpenStack rejected the supplied application credentials.');
    }

    public static function fromTransportError(Throwable $previous): self
    {
        return new self('Could not reach the OpenStack identity service.', previous: $previous);
    }
}
