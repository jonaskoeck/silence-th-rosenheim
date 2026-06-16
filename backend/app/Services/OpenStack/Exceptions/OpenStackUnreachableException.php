<?php

declare(strict_types=1);

namespace App\Services\OpenStack\Exceptions;

use Throwable;

/**
 * Thrown when the OpenStack identity service cannot be reached or does not
 * look like a Keystone endpoint (DNS failure, connection refused, wrong host).
 *
 * Extends InvalidOpenStackCredentialsException so existing broad catches keep
 * working, while callers that care can distinguish "host unreachable" from
 * "credentials rejected".
 */
class OpenStackUnreachableException extends InvalidOpenStackCredentialsException
{
    public static function fromTransportError(Throwable $previous): self
    {
        return new self('Could not reach the OpenStack identity service.', previous: $previous);
    }
}
