<?php

/*
 * Konfiguration für die Shibboleth-Authentifizierung.
 *
 * Der Shibboleth Service Provider (Apache + mod_shib) übernimmt die eigentliche
 * SAML-Authentifizierung mit dem Identity Provider der TH Rosenheim. Nach erfolgreicher
 * Anmeldung setzt mod_shib HTTP-Header mit den Benutzerattributen, die hier gemappt werden.
 *
 * Im lokalen Entwicklungsmodus kann die Authentifizierung simuliert werden,
 * damit man ohne laufenden Shibboleth SP entwickeln und testen kann.
 */

return [

    /*
     * HTTP-Header, die vom Shibboleth SP gesetzt werden.
     * Diese Header enthalten die Benutzerinformationen nach erfolgreicher Anmeldung.
     * Die Standardwerte entsprechen der üblichen DFN-AAI / Shibboleth-Konfiguration.
     */
    'header_eppn' => env('SHIB_HEADER_EPPN', 'eppn'),
    'header_display_name' => env('SHIB_HEADER_DISPLAY_NAME', 'displayName'),
    'header_mail' => env('SHIB_HEADER_MAIL', 'mail'),
    'header_affiliation' => env('SHIB_HEADER_AFFILIATION', 'affiliation'),
    'header_entitlement' => env('SHIB_HEADER_ENTITLEMENT', 'entitlement'),

    /*
     * Optionaler Entitlement-Wert, der vorhanden sein muss, damit der Zugriff erlaubt wird.
     * Wenn leer, wird kein Entitlement geprüft — jeder authentifizierte TH-Benutzer hat Zugang.
     * Kann später gesetzt werden, falls nur bestimmte Gruppen (z.B. RZ-Mitarbeiter) Zugang haben sollen.
     */
    'required_entitlement' => env('SHIB_REQUIRED_ENTITLEMENT', ''),

    /*
     * URL zum Shibboleth Login-Handler.
     * Der Shibboleth SP stellt unter diesem Pfad den SSO-Login bereit.
     * Bei einem Request auf diese URL leitet mod_shib automatisch zum IDP weiter.
     */
    'login_url' => env('SHIB_LOGIN_URL', '/Shibboleth.sso/Login'),

    /*
     * URL zum Shibboleth Logout-Handler.
     * Über diesen Pfad wird ein Single Logout (SLO) beim IDP ausgelöst,
     * sodass der Benutzer auch dort abgemeldet wird.
     */
    'logout_url' => env('SHIB_LOGOUT_URL', '/Shibboleth.sso/Logout'),

    /*
     * Lokaler Entwicklungsmodus: Wenn aktiviert, wird die Shibboleth-Authentifizierung
     * simuliert. Statt echte HTTP-Header vom SP zu erwarten, werden die unten
     * konfigurierten Testdaten verwendet. NIEMALS in Produktion aktivieren!
     */
    'simulate' => env('SHIB_SIMULATE', false),

    /*
     * Simulierte Benutzerdaten für die lokale Entwicklung.
     * Diese Werte werden nur verwendet, wenn 'simulate' auf true steht.
     */
    'simulate_eppn' => env('SHIB_SIMULATE_EPPN', 'testuser@th-rosenheim.de'),
    'simulate_display_name' => env('SHIB_SIMULATE_DISPLAY_NAME', 'Test Benutzer (RZ)'),
    'simulate_mail' => env('SHIB_SIMULATE_MAIL', 'testuser@th-rosenheim.de'),
    'simulate_affiliation' => env('SHIB_SIMULATE_AFFILIATION', 'employee@th-rosenheim.de'),
    'simulate_entitlement' => env('SHIB_SIMULATE_ENTITLEMENT', ''),

];
