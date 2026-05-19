<?php

/*
 * Authentifizierungs-Konfiguration.
 *
 * Da die Authentifizierung vollständig über Shibboleth (Apache + mod_shib) läuft,
 * wird kein Eloquent-basierter User Provider und kein Passwort-Reset benötigt.
 * Die Benutzer-Session wird direkt von der ShibbolethAuth-Middleware verwaltet.
 *
 * Die Standardkonfiguration bleibt erhalten, damit Laravels Auth-Infrastruktur
 * keine Fehler wirft, wird aber nicht aktiv verwendet.
 */

return [

    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),
    ],

    'guards' => [
        'web' => [
            'driver' => 'shibboleth',
        ],
    ],

    'providers' => [],

    'passwords' => [],

    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

];
