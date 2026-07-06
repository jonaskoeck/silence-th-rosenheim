<?php

declare(strict_types=1);

namespace App\Services\OpenStack\Exceptions;

use RuntimeException;

class InvalidOpenStackCredentialsException extends RuntimeException
{
    public static function fromInvalidCredentials(): self
    {
        return new self('OpenStack rejected the supplied application credentials.');
    }
}
