<?php

declare(strict_types=1);

namespace App\Services\OpenStack\Exceptions;

use RuntimeException;
use Throwable;

class OpenStackServerActionException extends RuntimeException
{
    public static function fromTransportError(string $action, Throwable $previous): self
    {
        return new self("Could not reach OpenStack while attempting to {$action} server.", previous: $previous);
    }

    public static function fromUnexpectedStatus(string $action, int $status): self
    {
        return new self("OpenStack returned status {$status} while attempting to {$action} server.");
    }
}
