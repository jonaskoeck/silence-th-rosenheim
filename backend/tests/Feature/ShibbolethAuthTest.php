<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Tests fuer die Shibboleth-Authentifizierungs-Middleware.
 *
 * Prueft, dass geschuetzte Routen nur mit gueltiger Shibboleth-Session
 * erreichbar sind und dass die Benutzerdaten korrekt in die Session
 * uebernommen werden.
 */
class ShibbolethAuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Simulation deaktivieren, damit die Middleware echte Header erwartet
        config(['shibboleth.simulate' => false]);
    }

    /**
     * Ohne Shibboleth-Header muss der Zugriff auf geschuetzte Routen
     * mit einem Redirect zum Shibboleth-Login beantwortet werden.
     */
    public function test_unauthenticated_request_redirects_to_shibboleth_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect();
        $this->assertStringContains('/Shibboleth.sso/Login', $response->headers->get('Location'));
    }

    /**
     * Der Redirect zum Shibboleth-Login muss einen target-Parameter enthalten,
     * damit der Benutzer nach der Anmeldung zur urspruenglich angefragten
     * Seite zurueckgeleitet wird.
     */
    public function test_redirect_contains_target_parameter(): void
    {
        $response = $this->get('/servers');

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContains('target=', $location);
        $this->assertStringContains(urlencode('servers'), $location);
    }

    /**
     * Mit gueltigem eppn-Header (simuliert ueber Server-Variable) muss der
     * Zugriff auf geschuetzte Routen erlaubt sein.
     */
    public function test_authenticated_request_passes_middleware(): void
    {
        config(['shibboleth.simulate' => true]);
        config(['shibboleth.simulate_eppn' => 'testuser@th-rosenheim.de']);
        config(['shibboleth.simulate_display_name' => 'Test User']);

        $response = $this->get('/dashboard');

        // Kein Redirect zum Login — entweder 200 oder ein DB-Fehler (500),
        // aber auf keinen Fall ein 302 zum Shibboleth-Login
        $this->assertNotEquals(302, $response->getStatusCode(),
            'Authentifizierter Request sollte nicht zum Login redirected werden');
    }

    /**
     * Die Benutzerdaten aus den Shibboleth-Headern muessen in der
     * Laravel-Session gespeichert werden.
     */
    public function test_user_data_is_stored_in_session(): void
    {
        config(['shibboleth.simulate' => true]);
        config(['shibboleth.simulate_eppn' => 'muster01@th-rosenheim.de']);
        config(['shibboleth.simulate_display_name' => 'Max Muster']);
        config(['shibboleth.simulate_mail' => 'max.muster@th-rosenheim.de']);

        $this->get('/dashboard');

        // Session-Werte pruefen: Die Middleware sollte die Benutzerdaten abgelegt haben
        $this->assertEquals('muster01@th-rosenheim.de', session('shibboleth_eppn'));
        $this->assertEquals('Max Muster', session('user_displayname'));
        $this->assertEquals('max.muster@th-rosenheim.de', session('user_mail'));
    }

    /**
     * Wenn ein Entitlement konfiguriert ist, muss der Zugriff verweigert werden
     * fuer Benutzer, die dieses Entitlement nicht besitzen.
     */
    public function test_missing_entitlement_returns_403(): void
    {
        config(['shibboleth.simulate' => true]);
        config(['shibboleth.simulate_eppn' => 'student01@th-rosenheim.de']);
        config(['shibboleth.simulate_entitlement' => '']);
        config(['shibboleth.required_entitlement' => 'urn:mace:th-rosenheim.de:rz-access']);

        $response = $this->get('/dashboard');

        $response->assertStatus(403);
    }

    /**
     * Mit dem richtigen Entitlement muss der Zugriff erlaubt sein.
     */
    public function test_matching_entitlement_allows_access(): void
    {
        config(['shibboleth.simulate' => true]);
        config(['shibboleth.simulate_eppn' => 'mitarbeiter01@th-rosenheim.de']);
        config(['shibboleth.simulate_display_name' => 'RZ Mitarbeiter']);
        config(['shibboleth.simulate_entitlement' => 'urn:mace:th-rosenheim.de:rz-access']);
        config(['shibboleth.required_entitlement' => 'urn:mace:th-rosenheim.de:rz-access']);

        $response = $this->get('/dashboard');

        // Zugriff erlaubt — kein 302 und kein 403
        $this->assertNotEquals(302, $response->getStatusCode());
        $this->assertNotEquals(403, $response->getStatusCode());
    }

    /**
     * Die Logout-Route muss die Session zerstoeren und zum
     * Shibboleth Logout-Handler weiterleiten.
     */
    public function test_logout_clears_session_and_redirects_to_shibboleth(): void
    {
        // Erst einloggen (simuliert)
        config(['shibboleth.simulate' => true]);
        config(['shibboleth.simulate_eppn' => 'testuser@th-rosenheim.de']);
        $this->get('/dashboard');

        // Jetzt ausloggen
        $response = $this->get('/logout');

        $response->assertRedirect();
        $this->assertStringContains('/Shibboleth.sso/Logout', $response->headers->get('Location'));
    }

    /**
     * Die Login-Route muss direkt zum Shibboleth SSO-Handler weiterleiten
     * (keine eigene Login-Seite).
     */
    public function test_login_route_redirects_to_shibboleth_sso(): void
    {
        $response = $this->get('/login');

        $response->assertRedirect();
        $this->assertStringContains('/Shibboleth.sso/Login', $response->headers->get('Location'));
    }

    /**
     * Hilfsmethode: Prueft ob ein String einen bestimmten Teilstring enthaelt.
     */
    private function assertStringContains(string $needle, ?string $haystack): void
    {
        $this->assertNotNull($haystack, "String darf nicht null sein");
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Erwartet dass '$haystack' den Teilstring '$needle' enthaelt"
        );
    }
}
