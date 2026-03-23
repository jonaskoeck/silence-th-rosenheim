# silence!

## Projektübersicht

Dieses Projekt entsteht im Rahmen der Lehrveranstaltung *Software Engineering Praxis* (SoSe 2026) an der Technischen Hochschule Rosenheim in Zusammenarbeit mit dem Rechenzentrum der TH.

### Hintergrund

Die virtuellen Maschinen des Rechenzentrums laufen auf einer externen OpenStack-Infrastruktur bei cnds.io mit minutengenauer Kostenabrechnung. Da Server bisher ausschließlich manuell über die OpenStack-Oberfläche gesteuert wurden, entstehen unnötige Kosten durch Laufzeiten außerhalb der Nutzungszeiten.

### Ziel

Entwicklung einer Webanwendung zur automatisierten Zeitplansteuerung und Inventarisierung von OpenStack-VMs über die Nova API, um Betriebskosten zu reduzieren.

### Kernfunktionen

- Zentrale Übersicht aller Server, gruppiert nach Kategorie (Produktion, Test, Entwicklung)
- Konfiguration individueller Zeitpläne pro Server (Starten/Stoppen nach Wochentag und Uhrzeit)
- Automatische Inventarisierung sowie manuelle Aktualisierung
- Sicherheitsabfrage bei Änderungen an Produktivservern
- Authentifizierung und Autorisierung über Shibboleth SSO (nur RZ-Mitarbeiter)

### Technologie-Stack

| Komponente | Technologie |
|---|---|
| Backend | PHP mit Laravel Framework |
| Frontend | Bootstrap v5 |
| Datenbank | MariaDB |
| Infrastruktur | Docker & Docker Compose |
| Authentifizierung | Shibboleth SSO |