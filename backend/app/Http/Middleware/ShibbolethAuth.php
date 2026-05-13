<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware für die Shibboleth-Authentifizierung.
 *
 * Diese Middleware prüft bei jedem Request, ob der Benutzer über den Shibboleth SP
 * authentifiziert ist. Der Ablauf funktioniert so:
 *
 * 1. Apache mit mod_shib fängt den Request ab und prüft, ob eine gültige
 *    Shibboleth-Session vorhanden ist.
 * 2. Falls ja, setzt mod_shib HTTP-Header mit den Benutzerattributen
 *    (z.B. eppn, displayName, mail) und leitet den Request an PHP-FPM weiter.
 * 3. Diese Middleware liest die Header aus und speichert die Benutzerdaten in der
 *    Laravel-Session, damit sie in Views und Controllern verfügbar sind.
 *
 * Falls keine gültige Shibboleth-Session existiert (kein eppn-Header vorhanden),
 * wird der Benutzer zum Shibboleth-Login weitergeleitet.
 */
class ShibbolethAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        // Benutzerdaten aus den Shibboleth-Headern oder der Simulation laden
        $userData = $this->resolveUserData($request);

        // Wenn kein eppn vorhanden ist, ist der Benutzer nicht authentifiziert
        if (empty($userData['eppn'])) {
            return $this->redirectToLogin($request);
        }

        // Optionale Entitlement-Prüfung: Falls ein bestimmtes Entitlement konfiguriert ist,
        // muss der Benutzer dieses besitzen, um Zugang zu erhalten
        if (!$this->hasRequiredEntitlement($userData['entitlement'])) {
            abort(403, 'Zugriff verweigert: Sie besitzen nicht die erforderliche Berechtigung.');
        }

        // Benutzerdaten in der Laravel-Session ablegen, damit sie überall
        // in der Anwendung (z.B. im Layout für die Namensanzeige) verfügbar sind
        $this->storeInSession($request, $userData);

        return $next($request);
    }

    /**
     * Liest die Benutzerdaten entweder aus den echten Shibboleth-Headern
     * oder aus der Simulations-Konfiguration (für lokale Entwicklung).
     */
    private function resolveUserData(Request $request): array
    {
        if (config('shibboleth.simulate')) {
            return $this->getSimulatedData();
        }

        return $this->getHeaderData($request);
    }

    /**
     * Extrahiert die Benutzerdaten aus den HTTP-Headern, die mod_shib gesetzt hat.
     *
     * mod_shib setzt die Attribute als Server-Variablen bzw. HTTP-Header.
     * In PHP kommen diese über $_SERVER oder die Request-Header an.
     * Wir prüfen beide Varianten, da je nach Apache-Konfiguration
     * die Header unterschiedlich weitergeleitet werden können.
     */
    private function getHeaderData(Request $request): array
    {
        return [
            'eppn' => $this->getShibbolethAttribute($request, config('shibboleth.header_eppn')),
            'display_name' => $this->getShibbolethAttribute($request, config('shibboleth.header_display_name')),
            'mail' => $this->getShibbolethAttribute($request, config('shibboleth.header_mail')),
            'affiliation' => $this->getShibbolethAttribute($request, config('shibboleth.header_affiliation')),
            'entitlement' => $this->getShibbolethAttribute($request, config('shibboleth.header_entitlement')),
        ];
    }

    /**
     * Liest ein einzelnes Shibboleth-Attribut aus dem Request.
     *
     * mod_shib kann Attribute auf verschiedene Weisen bereitstellen:
     * - Als Server-Variable (z.B. $_SERVER['eppn'])
     * - Als HTTP-Header (z.B. HTTP_EPPN)
     * Wir prüfen zuerst die Server-Variable, da das die sicherere Variante ist
     * (HTTP-Header könnten theoretisch vom Client gefälscht werden, Server-Variablen nicht).
     */
    private function getShibbolethAttribute(Request $request, string $name): ?string
    {
        // Zuerst Server-Variable prüfen (sicherer, da von mod_shib direkt gesetzt)
        $value = $request->server($name);
        if (!empty($value)) {
            return $value;
        }

        // Fallback auf HTTP-Header (z.B. wenn Apache die Attribute als Header weiterleitet)
        return $request->header($name);
    }

    /**
     * Gibt simulierte Benutzerdaten zurück für die lokale Entwicklung.
     * Die Werte stammen aus der Konfiguration (config/shibboleth.php bzw. .env).
     */
    private function getSimulatedData(): array
    {
        return [
            'eppn' => config('shibboleth.simulate_eppn'),
            'display_name' => config('shibboleth.simulate_display_name'),
            'mail' => config('shibboleth.simulate_mail'),
            'affiliation' => config('shibboleth.simulate_affiliation'),
            'entitlement' => config('shibboleth.simulate_entitlement'),
        ];
    }

    /**
     * Prüft ob der Benutzer über das geforderte Entitlement verfügt.
     *
     * Entitlements sind ein SAML-Attribut, mit dem der IDP signalisiert, welche
     * Berechtigungen ein Benutzer hat. Falls in der Konfiguration ein bestimmtes
     * Entitlement gefordert ist, muss es in der (kommaseparierten) Liste der
     * Benutzer-Entitlements enthalten sein.
     *
     * Ist kein Entitlement konfiguriert, hat jeder authentifizierte Benutzer Zugang.
     */
    private function hasRequiredEntitlement(?string $userEntitlement): bool
    {
        $required = config('shibboleth.required_entitlement');

        // Wenn kein Entitlement gefordert ist, hat jeder Zugang
        if (empty($required)) {
            return true;
        }

        if (empty($userEntitlement)) {
            return false;
        }

        // Entitlements können als semikolon-getrennte Liste kommen
        $entitlements = array_map('trim', explode(';', $userEntitlement));

        return in_array($required, $entitlements);
    }

    /**
     * Speichert die Benutzerdaten in der Laravel-Session.
     * Dadurch sind die Daten in allen nachfolgenden Requests verfügbar,
     * z.B. im Layout für die Anzeige des Benutzernamens.
     */
    private function storeInSession(Request $request, array $userData): void
    {
        $request->session()->put('shibboleth_eppn', $userData['eppn']);
        $request->session()->put('user_displayname', $userData['display_name'] ?? $userData['eppn']);
        $request->session()->put('user_mail', $userData['mail']);
        $request->session()->put('user_affiliation', $userData['affiliation']);
        $request->session()->put('user_entitlement', $userData['entitlement']);
    }

    /**
     * Leitet nicht-authentifizierte Benutzer zum Shibboleth-Login weiter.
     *
     * Im Produktivbetrieb wird direkt der Shibboleth SSO-Handler aufgerufen,
     * der den SAML-Flow mit dem IDP der TH Rosenheim startet.
     *
     * Der 'target'-Parameter teilt dem Shibboleth SP mit, wohin der Benutzer
     * nach erfolgreicher Anmeldung zurückgeleitet werden soll.
     */
    private function redirectToLogin(Request $request): Response
    {
        $targetUrl = $request->fullUrl();
        $loginUrl = config('shibboleth.login_url') . '?target=' . urlencode($targetUrl);

        return redirect($loginUrl);
    }
}
