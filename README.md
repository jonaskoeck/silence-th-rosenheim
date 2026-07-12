# silence!

> **Portfolio-Kopie.** Dies ist eine mit ausdrücklicher Zustimmung des betreuenden Professors auf meinen privaten GitHub-Account gespiegelte Kopie eines Teamprojekts an der Technischen Hochschule Rosenheim. Das Original-Repository liegt im TH-internen GitLab und ist nicht öffentlich zugänglich.
>
> Meine persönlichen Beiträge sind in der Sektion [**Meine Beiträge**](#meine-beiträge-jonas-köck) am Ende dieser README gelistet. Für einen chronologischen Überblick: `git log --author="Jonas Köck"` bzw. der [Contributors](https://github.com/jonaskoeck/silence-th-rosenheim/graphs/contributors)-Tab auf GitHub.

## Projektübersicht

Dieses Projekt entsteht im Rahmen der Lehrveranstaltung _Software Engineering Praxis_ (SoSe 2026) an der Technischen Hochschule Rosenheim in Zusammenarbeit mit dem Rechenzentrum der TH.

### Hintergrund

Die virtuellen Maschinen des Rechenzentrums laufen auf einer externen OpenStack-Infrastruktur bei cnds.io mit minutengenauer Kostenabrechnung. Da Server bisher ausschließlich manuell über die OpenStack-Oberfläche gesteuert wurden, entstehen unnötige Kosten durch Laufzeiten außerhalb der Nutzungszeiten.

### Ziel

Entwicklung einer Webanwendung zur automatisierten Zeitplansteuerung und Inventarisierung von OpenStack-VMs über die Nova API, um Betriebskosten zu reduzieren.

### Kernfunktionen

- Zentrale Übersicht aller Server ausgewählter OpenStack Projekte, gruppiert nach Kategorie (Produktion, Test, Entwicklung)
- Konfiguration individueller Zeitpläne pro Server (Starten/Stoppen nach Wochentag und Uhrzeit)
- Automatische sowie manuelle Inventarisierung der Server
- Sicherheitsabfrage bei Änderungen an Produktivservern
- Authentifizierung und Autorisierung über Shibboleth SSO (nur RZ-Mitarbeiter)

### Technologie-Stack

| Komponente          | Technologie                  |
| ------------------- | ---------------------------- |
| Backend             | PHP mit Laravel Framework    |
| User-Interface/ GUI | Laravel-Blade & Bootstrap v5 |
| Datenbank           | MariaDB                      |
| Infrastruktur       | Docker & Docker Compose      |
| Authentifizierung   | Shibboleth SSO               |

### Wiki

Ein ausführliches Wiki (Anforderungen, Architektur, Entwicklungs-Doku, Meeting-Protokolle) wurde begleitend im TH-internen GitLab gepflegt und ist von außen nicht erreichbar. Auf Nachfrage kann ich einzelne Kapitel als PDF-Export bereitstellen.

<!-- wiki-end -->

---

## Was ist umgesetzt?

Alle Pflichtfunktionen (MMF – Must-Have Features) sind vollständig implementiert:

- **Projektverwaltung:** OpenStack-Projekte mit Zugangsdaten anlegen, bearbeiten und löschen.
- **Server-Inventarisierung:** stündlich automatisch sowie jederzeit manuell (alle oder einzelne Projekte); gelöschte Server werden erkannt und markiert.
- **Server-Kategorisierung:** _Entwicklung_ / _Test_ / _Produktion_ – die Kategorie bestimmt die Sicherheitsregeln beim Anlegen von Zeitplänen.
- **Zeitpläne:** wochenbasierte Start-/Stopp-Zeitpläne anlegen, bearbeiten, aktivieren/deaktivieren und löschen; vollautomatische Auslösung, Sicherheitsabfrage bei Produktionsservern.
- **Login & Zugriffsschutz:** ausschließlich über das Hochschul-SSO (Shibboleth/SAML); nur RZ-Mitarbeiter mit passendem Entitlement erhalten Zugang.
- **Dashboard & Oberfläche:** Serverübersicht mit Echtzeit-Status (Polling), Anzeige eingesparter Ressourcen, Toast-Benachrichtigungen, HTMX-Layout, Dark Mode, Farbblindenmodus, Einstellungen-Tab.
- **Regionenverwaltung:** bildet OpenStack-Installationen mit unterschiedlichen Host-URLs ab; jedes Projekt wird einer Region zugeordnet.
- **Deployment:** vollständig containerisiert über Docker Compose.

## Installation & Konfiguration

Ausführliche Schritt-für-Schritt-Anleitung im Wiki: **[Installationsskizze](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/Struktur/Installationsskizze)**. Kurzfassung:

### Voraussetzungen

- Docker + Docker Compose
- Für die lokale Entwicklung zusätzlich: PHP 8.4 (Erweiterungen `pdo_mysql`, `zip`, `mbstring`, `opcache`), Composer 2, Node.js 22

### Konfiguration (zwei `.env`-Dateien)

Beide Dateien aus den Vorlagen erstellen:

```bash
cp backend/.env.example backend/.env   # App-Konfiguration
cp .env.example .env                   # Datenbank-Init + Deployment-Overrides
```

Die Container laden beide Dateien in dieser Reihenfolge – bei doppelten Keys **gewinnt die spätere**: zuerst `backend/.env` (App-Defaults), dann die Root-`.env` (Deployment-Overrides). Zusätzlich überschreibt `environment:` in `compose.yml` (`DB_HOST=database`) beide. Die Datenbank-Zugangsdaten müssen zwischen den Dateien übereinstimmen:

| Root-`.env` | | `backend/.env` |
|---|---|---|
| `DB_DATABASE` | = | `DB_DATABASE` |
| `DB_LARAVEL_USER` | = | `DB_USERNAME` |
| `DB_LARAVEL_PASSWORD` | = | `DB_PASSWORD` |

### Lokale Entwicklung

```bash
docker compose up -d database
cd backend && composer install && php artisan key:generate && npm install && php artisan migrate && cd ..
./startDevEnvironmentUnix.sh          # macOS/Linux   (Windows: ./startDevEnvironmentWindows.ps1)
```

Die App ist danach unter <http://localhost:8000> erreichbar. Lokal ist `SHIB_SIMULATE=true` gesetzt (simulierter RZ-Benutzer, kein echter Shibboleth-SP nötig).

### Produktion

```bash
docker network create web             # einmalig
# .env-Dateien mit echten Secrets füllen (Root-.env zusätzlich: APP_ENV=production,
#   APP_DEBUG=false, APP_URL, SHIB_SIMULATE=false, DB_USERNAME/DB_PASSWORD)
docker compose up --build -d
```

Kein manuelles `npm install` / `composer install` nötig – der mehrstufige `backend/Dockerfile` baut die Vite-Assets und installiert die PHP-Abhängigkeiten; der Entrypoint spiegelt die Assets und führt `php artisan migrate --force` aus.

### Wichtige Hinweise

- **Shibboleth:** Der `server`-Container (Apache + `mod_shib`) ist der SAML-Service-Provider; er benötigt SP-Zertifikate und `shibboleth2.xml` am Host (Details im Wiki).
- **OpenStack:** Zugänge werden **nicht** über `.env` konfiguriert, sondern zur Laufzeit in der Weboberfläche angelegt (Region + Projekt mit Application Credentials) und verschlüsselt in der Datenbank gespeichert.
- **`APP_KEY`:** Die verschlüsselten OpenStack-Credentials sind an den `APP_KEY` gebunden. Bei einer Rotation den alten Schlüssel **vorher** als `APP_PREVIOUS_KEYS` hinterlegen, sonst sind bestehende Credentials nicht mehr entschlüsselbar.
- **`CACHE_STORE=database`** ist erforderlich – der `PendingActionTracker` liest laufende Server-Aktionen gezielt aus dem Datenbank-Cache.
- **Scheduler:** Der Container `silence-scheduler` muss dauerhaft laufen, damit Zeitpläne ausgelöst und Server regelmäßig inventarisiert werden.

## Entwicklerteam

| Name | Rolle | Kontakt |
|------|-------|---------|
| Paul Hannemann | Projektleiter | paul.hannemann@stud.th-rosenheim.de |
| Jan Falarowski | Technischer Architekt | jan.falarowski@stud.th-rosenheim.de |
| Alexander Dingiria | Product Owner | alexander.dingiria@stud.th-rosenheim.de |
| Jonas Köck | Qualitätsbeauftragter | jonas.koeck@stud.th-rosenheim.de |
| David Costa | Usability Engineer | david.costa@stud.th-rosenheim.de |

## Meine Beiträge (Jonas Köck)

Neben der Rolle als Qualitätsbeauftragter (Test-Reviews, Coverage-Kontrolle) habe ich innerhalb des Teams folgende Features eigenständig konzipiert und implementiert:

### Authentifizierung & Zugriffsschutz

- Vollständige Shibboleth-SSO-Integration nach den User Stories US-E6-1 (SSO-Login) und US-E6-2 (Zugriffskontrolle)
- Eigene Laravel-Middleware, die die vom SP gesetzten SAML-Header ausliest (inkl. `REDIRECT_`-Prefix-Handling auf Empfehlung des Rechenzentrums), die geforderten Entitlements gegen die Föderationskonfiguration prüft und die Benutzerdaten in der Session ablegt
- Simulationsmodus (`SHIB_SIMULATE=true`) für lokale Entwicklung ohne Shibboleth-SP, damit alle Teammitglieder ohne SAML-Setup entwickeln konnten
- 8 Feature-Tests, die Redirect-Verhalten, Entitlement-Enforcement, Session-Persistenz und Logout abdecken

Kernstellen: [`ShibbolethAuth.php`](backend/app/Http/Middleware/ShibbolethAuth.php), [`config/shibboleth.php`](backend/config/shibboleth.php), [`ShibbolethAuthTest.php`](backend/tests/Feature/ShibbolethAuthTest.php)

### Barrierefreiheit & UI

- **Dark Mode** mit persistenter `localStorage`-Speicherung, Kontrast-optimierten Farbwerten und Anpassungen für Formulare, Modals, Toasts, Tabellen und HTMX-Partials
- **Farbblindenmodus** (Rot-Grün-Schwäche / Deuteranopie): Farbblind-sichere Alternativpalette (Blau/Orange statt Rot/Grün) über Badges, Buttons, Status-Dots, Toggle-Switches und Zeitplan-Kalender – auch für per JavaScript erzeugte, dynamische DOM-Knoten
- **Konsistente Server-Label-Farbgebung** über Produktiv/Test/Entwicklung/Unkategorisiert – in beiden Modi klar unterscheidbar
- **Tooltip-Ausstattung** aller relevanten Icon-Buttons inklusive automatischer Neuinitialisierung nach HTMX-Content-Swaps

Kernstellen: [`custom.css`](backend/public/assets/css/custom.css), [`layouts/app.blade.php`](backend/resources/views/layouts/app.blade.php), [`app.js`](backend/resources/js/app.js)