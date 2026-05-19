<?php

declare(strict_types=1);

namespace App\Auth;

use Illuminate\Auth\GuardHelpers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;

/**
 * Stub Guard for an app that authenticates via the Shibboleth middleware.
 *
 * The app does not manage users through Laravel's auth system; user data lives
 * in the session, populated by ShibbolethAuth middleware from mod_shib headers.
 * This guard exists so Auth::id() / Auth::user() resolve cleanly to null
 * instead of crashing the SessionGuard constructor for missing UserProvider.
 */
class ShibbolethGuard implements Guard
{
    use GuardHelpers;

    public function user(): ?Authenticatable
    {
        return null;
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validate(array $credentials = []): bool
    {
        return false;
    }
}
