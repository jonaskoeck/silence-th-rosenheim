# silence!

## Projektübersicht

Dieses Projekt entsteht im Rahmen der Lehrveranstaltung *Software Engineering Praxis* (SoSe 2026) an der Technischen Hochschule Rosenheim in Zusammenarbeit mit dem Rechenzentrum der TH.

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

| Komponente | Technologie |
|---|---|
| Backend | PHP mit Laravel Framework |
| User-Interface/ GUI | Laravel-Blade & Bootstrap v5 |
| Datenbank | MariaDB |
| Infrastruktur | Docker & Docker Compose |
| Authentifizierung | Shibboleth SSO |

### Wiki Übersicht
- [Home](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home)
    - [Teamorganisation](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/3.-Teamorganisation)
    - [Stakeholder](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/Stakeholder)
    - [Meilensteine](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/1.-Meilenstein)
    - [Termine](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/2.-Termin)
    - [Beistellungen](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/Beistellungen)
    - [Meetingprotokolle & Ergebnisse](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/Meeting-Protokolle-&-Ergebnisse)
        - [Projektexterne Meetings](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/Meeting-Protokolle-&-Ergebnisse/Projektexterne-Meetings)
        - [Projektinterne Meetings](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/Meeting-Protokolle-&-Ergebnisse/Projektinterne-Meetings)
    - [Systemarchitektur](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/Systemarchitektur)
        - [Diagramme](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/Systemarchitektur/Diagramme)
        - [Verzeichnisstruktur](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/Systemarchitektur/Verzeichnisstruktur)
        - [UI & UX Design](https://git-ce.th-rosenheim.de/sep-wif-26/silence/-/wikis/Home/7.-Systemarchitektur/UI---UX-Design)