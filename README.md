# silence!

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

### Wiki Übersicht

- [Home](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home)
- [Entwicklerteam](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/Entwicklerteam)
- [Projekt](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/Projekt)
- [Anforderungen](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/Anforderungen)
- [Architektur](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/Architektur)
- [Entwicklung](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/Entwicklung)
- [Meetings und Protokolle](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/Meetings-und-Protokolle)

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