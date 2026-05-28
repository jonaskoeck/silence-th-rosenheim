<?php

namespace Tests;

use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->withoutMiddleware(PreventRequestForgery::class);

        // Shibboleth-Simulation aktivieren, damit alle Tests standardmaessig
        // als authentifizierter Benutzer laufen. Tests die das Auth-Verhalten
        // gezielt pruefen wollen (z.B. ShibbolethAuthTest) koennen das
        // in ihrer eigenen setUp()-Methode ueberschreiben.
        config(['shibboleth.simulate' => true]);
        config(['shibboleth.simulate_eppn' => 'testuser@th-rosenheim.de']);
        config(['shibboleth.simulate_display_name' => 'Test Benutzer']);
    }
}
