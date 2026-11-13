# Verwaltung der Inhalte – tok-art-projekt

Wie die Inhalte des Foto-Tagebuchs gepflegt werden (EasyAdmin unter `/admin`, Daten in MariaDB), warum diese Lösung gewählt wurde und was beim Arbeiten damit zu beachten ist.

Technische Einzelheiten: [TECHNIK.md](TECHNIK.md) · Veröffentlichung: [STRATO.md](STRATO.md)

---

## Inhalt

1. Die Entscheidung: EasyAdmin und MariaDB
2. Erste Schritte (mit Checkliste vor echten Inhalten)
3. Bedienung
4. Fotos und Videos – Datenschutz beim Upload
5. Wo die Daten liegen
6. Lokal oder auf dem Server pflegen?
7. Sicherheit
8. Grenzen und mögliche Erweiterungen

---

## 1. Die Entscheidung: EasyAdmin und MariaDB

### Ausgangsfrage: Welche Datenbank? PostgreSQL?

Bis dahin lagen alle Inhalte als JSON-Dateien in `data/` und wurden von Hand bearbeitet; Kontaktnachrichten waren Dateien in `var/contact/`. Das reicht für wenige Inhalte, die man selbst im Editor pflegt.

Eine Datenbank lohnt sich, sobald eine dieser Erweiterungen kommt:
- **Inhalte im Browser bearbeiten** (Verwaltung statt JSON von Hand)
- **Anmeldung** – für die Administratorin, später für Freunde mit Sichtbarkeitsstufen
- **Kommentare und Feedback** von Besucher:innen
- **Kontaktnachrichten** im Browser lesen

**PostgreSQL kommt für dieses Projekt nicht in Frage:** Das vorhandene Paket **STRATO Hosting Basic** bietet als Datenbank nur **MySQL bzw. MariaDB** (laut STRATO derzeit MySQL 8 oder MariaDB 10.11, gespeichert in Deutschland, automatisch gesichert). PostgreSQL gibt es dort nicht – dafür wäre ein V-Server nötig. Eine lokal mit PostgreSQL entwickelte App ließe sich also nicht auf Hosting Basic veröffentlichen.

### Warum nicht Strapi?

In der Planung des Nuxt-Projekts stand „Verwaltung mit Strapi und PostgreSQL". **Strapi ist eine Node.js-Anwendung**, die dauerhaft als eigener Prozess laufen muss – das erlaubt klassisches Webhosting nicht. Strapi bräuchte einen zusätzlichen V-Server mit eigener Wartung. Die gehostete Variante Strapi Cloud wäre ein externer Dienst und widerspricht dem Grundsatz „Daten bleiben bei mir".

### Die geprüften Alternativen für ein PHP-Projekt

| Lösung | Was es ist | Auf Hosting Basic? | Bewertung |
|---|---|---|---|
| **EasyAdmin** (Symfony-Bundle) | Verwaltung **innerhalb** der Symfony-App, Daten über Doctrine in MariaDB | ✓ | **gewählt** – gleiche App, gleiche Sprache, kein zweites System |
| Sulu | vollwertiges CMS auf Symfony-Basis (Seitenbaum, Medienbibliothek, Inhaltsblöcke) | ✓ | Blockmodell passt, aber großes System – das Projekt würde eher in Sulu umgebaut als Sulu angebunden |
| Kirby | PHP-CMS **ohne Datenbank** (Inhalte als Dateien), eigene API | ✓ | beliebt für Foto- und Reiseseiten; eigenes System neben Symfony; kostenpflichtige Lizenz für öffentliche Seiten |
| Grav | PHP-CMS ohne Datenbank, Open Source | ✓ | wie Kirby: eigenes System neben Symfony |
| WordPress (headless) | Inhalte in WordPress, Symfony liest über die REST-API | ✓ | Block-Editor ähnelt dem Blockmodell, aber sehr groß, viele Plugins und Updates |
| Strapi, Directus, Payload | Node.js-Systeme | ✗ (nur V-Server) | mehr Betriebsaufwand |

### Warum EasyAdmin

- **Teil der App:** Unter `/admin` werden Reisen, Themen, Einträge, Fotos und Schlagwörter bearbeitet. Kein zweites System zu installieren, zu aktualisieren und abzusichern.
- **Läuft auf dem vorhandenen Paket:** PHP und MariaDB auf Hosting Basic – kein V-Server, kein Node.js auf dem Server.
- **Passt zur Architektur:** Die Daten kommen über eine neue `DoctrineContentSource` – genau die Austauschstelle, die von Anfang an vorgesehen war. Seiten, Vue-Galerie und Domain blieben unverändert; es änderte sich eine Zeile in `config/services.yaml`.
- **Kontaktnachrichten** werden dort ebenfalls gelesen (`DoctrineContactInbox` statt Dateien).
- **Anmeldung** über Symfony Security – zugleich die Grundlage für spätere Erweiterungen (Freundeskreis, Kommentare).

### Datenbanken im Vergleich

| | **MariaDB** | PostgreSQL | SQLite |
|---|---|---|---|
| Auf STRATO Hosting Basic | ✓ (mit automatischer Sicherung) | ✗ (nur V-Server) | technisch möglich (eine Datei) |
| Einsatz hier | **gewählt** – lokal in Docker in derselben Version 10.11 | passt zu Strapi auf einem V-Server | schlanke Zwischenlösung, aber Sicherung selbst organisieren |

Doctrine verbirgt die meisten Unterschiede zwischen den Datenbanken; SQL-Kenntnisse aus PostgreSQL helfen, nur einzelne Eigenheiten unterscheiden sich.

### Und das Nuxt-Projekt?

Dort bleibt „Strapi mit PostgreSQL" die passende Planung – Nuxt braucht ohnehin einen Node.js-Server. Für **tok-art-projekt** (Symfony, PHP, Hosting Basic) ist EasyAdmin mit MariaDB die richtige Entscheidung. Beide Wege sind nachvollziehbar dokumentiert.

---

## 2. Erste Schritte

```bash
cd ~/Documents/tok-art-projekt
make setup                         # Container starten, Tabellen anlegen, Beispielinhalte aus data/ übernehmen
make admin EMAIL=deine@adresse.de  # Konto anlegen – das Passwort wird verdeckt abgefragt (mind. 12 Zeichen)
```

Dann http://localhost:8000/admin öffnen und anmelden.

- Passwort vergessen oder ändern: `make admin EMAIL=…` noch einmal – das Passwort wird neu gesetzt.
- Für die Entwicklung gibt es zusätzlich das Testkonto `admin@example.org` (Passwort `entwicklung-2026`) – **nur lokal**, es wird nicht mit veröffentlicht (siehe Abschnitt 5).

### Bevor du mit echten Inhalten anfängst – Checkliste

| Punkt | Worum es geht | Mehr dazu |
|---|---|---|
| **Sichern** | Datenbank und eigene Fotos stehen **nicht in Git**. Die Datenbank liegt nur im Docker-Volume – `docker compose down -v` oder „Purge data" in Docker Desktop löschen sie. Regelmäßig `make db-export` und `public/media/` nach HiDrive kopieren. | Abschnitt 5 |
| **Fotos als JPEG** | iPhone-Fotos sind meist HEIC – das nimmt die Verwaltung nicht an. Als JPEG exportieren oder am iPhone „Maximale Kompatibilität" wählen. Ort und Kameradaten entfernt die App beim Upload selbst. | Abschnitt 4 |
| **Videos ohne Ort** | Videos kann die App nicht bereinigen – vorher ohne Ortsinformationen exportieren, kurz und klein halten. | Abschnitt 4 |
| **Größe** | bis 50 MB pro Datei; auf dem Server gilt die PHP-Einstellung von STRATO. | Abschnitt 4 |
| **„Über mich"** | Der Text steht nicht in der Verwaltung, sondern in `templates/home/index.html.twig`. | Abschnitt 5 |
| **Beispielinhalte** | in der Verwaltung löschen, sobald eigene da sind (erst Einträge, dann Fotos, Reisen, Themen). `data/*.json` **nicht** löschen – das sind die Startdaten der Tests. | Abschnitt 5 |
| **Wo pflegen** | vor dem Online-Gang lokal, dann einmal übertragen (`make db-export`); danach nur noch online. Nicht mischen. | Abschnitt 6 |
| **Committen** | Code-Änderungen wie gewohnt committen – eigene Fotos und die Datenbank gehen dabei nicht mit (`public/media/*` und das Docker-Volume sind nicht in Git). | Abschnitt 5 |

**Vor dem Online-Gang** fehlen außerdem: Impressum und Datenschutzerklärung, ein responsives Layout und auf STRATO die Prüfung von PHP-Erweiterungen und Upload-Grenzen sowie das Anlegen der Datenbank – siehe [STRATO.md](STRATO.md), Abschnitte 1 und 9.

### Was vor dem Start noch behoben wurde

Beim Prüfen vor dem ersten echten Inhalt fielen drei Probleme auf, die bei Handyfotos sofort aufgetreten wären:

| Problem | Folge | Lösung |
|---|---|---|
| PHP im Container nahm nur **2 MB** pro Upload an | Handyfotos (3–10 MB) ließen sich nicht hochladen | `docker/php/uploads.ini`: 50 MB pro Datei, 512 MB Speicher für die Bildverarbeitung |
| Neue Dateien in `public/media/` waren **nicht von Git ausgeschlossen** | eigene Fotos wären mit `git add .` auf GitHub gelandet | `/public/media/*` in `.gitignore`; die 19 Beispielmedien bleiben versioniert |
| Löschen eines Beispielfotos hätte **die Datei mitgelöscht** | `make test` wäre danach fehlgeschlagen | Dateien der Beispielmedien aus `data/media.json` werden nie gelöscht (mit Test) |

---

## 3. Bedienung

### Menü

| Punkt | Inhalt |
|---|---|
| Zur Website | öffnet die öffentliche Seite |
| **Einträge** | Startseite der Verwaltung; Tagebucheinträge mit ihren Blöcken |
| Reisen | Titel und Zeitraum |
| Themen | worum es in Einträgen geht (z. B. „Berge") |
| Fotos und Videos | Dateien hochladen, Ort, Datum, Themen, Schlagwörter |
| Schlagwörter | was auf Fotos zu sehen ist (z. B. „Tier") |
| Nachrichten | Kontaktnachrichten lesen und löschen |
| Abmelden | |

### Felder, die überall gleich funktionieren

- **Kennung (ID):** erscheint in der Adresse, z. B. `/trips/island`. Nur Kleinbuchstaben, Ziffern und Bindestriche. **Beim Anlegen frei wählbar, danach fest** – sonst würden Links ins Leere führen.
- **Datum:** optional und auch ungefähr: `2016`, `2018-05` oder `2019-07-12`. Andere Schreibweisen (z. B. `14.06.2025`) werden mit einem Hinweis abgelehnt.
- **Ort:** als Text, z. B. „Bergen, Norwegen" – nie Koordinaten.

### Einen Eintrag schreiben

1. **Fotos und Videos** zuerst hochladen (Abschnitt 4).
2. **Einträge → Eintrag erstellen:** Titel, Datum, Reise (optional – ohne Reise erscheint der Eintrag nur unter „Themen"), Themen, Kennung.
3. Unter **Blöcke** mit „Neues Element hinzufügen" Blöcke anlegen. Je Block:
   - **Reihenfolge:** 1, 2, 3 … – in dieser Reihenfolge erscheinen die Blöcke.
   - **Art:** Text, Bild, Video oder Galerie. Je nach Art werden nur die passenden Felder angezeigt:

     | Art | Felder |
     |---|---|
     | Text | Text (Zeilenumbrüche bleiben erhalten) |
     | Bild, Video | Foto oder Video, Bildunterschrift (optional) |
     | Galerie | Fotos der Galerie (mehrere) |

4. **Speichern.** Mit „Ansehen" öffnet sich der Eintrag auf der Website in einem neuen Tab.

Fehlt etwas (z. B. ein Bildblock ohne Foto), zeigt das Formular beim Speichern einen Hinweis am Feld.

### Löschen

| Was | Was passiert |
|---|---|
| Eintrag | wird mit seinen Blöcken gelöscht |
| Reise | Einträge bleiben, verlieren nur die Reise (erscheinen dann unter „Themen") |
| Thema, Schlagwort | nur die Zuordnung verschwindet |
| Foto oder Video | **nur möglich, wenn es in keinem Eintrag mehr vorkommt** – sonst nennt eine Meldung die Einträge. Beim Löschen verschwindet auch die Datei – außer bei den Beispielmedien aus `data/media.json`: deren Dateien bleiben, weil die Testdatenbank sie braucht. |
| Nachricht | wird gelöscht – beantwortete Nachrichten bitte löschen (Datenschutz) |

---

## 4. Fotos und Videos – Datenschutz beim Upload

**Fotos** (JPEG, PNG, WebP) werden beim Hochladen automatisch vorbereitet:

1. **richtig gedreht** – Handys speichern die Drehung nur als Angabe in den Metadaten,
2. auf höchstens **2000 Pixel** an der langen Seite **verkleinert**,
3. **neu gespeichert – dabei fallen alle Metadaten weg:** GPS-Koordinaten, Kameramodell, Aufnahmezeit.

Die Metadaten werden dafür nur gelesen (Drehung) und danach verworfen – nie gespeichert oder angezeigt. Geprüft mit einem Testfoto mit eingebetteten Kamera- und GPS-Angaben: Nach dem Upload sind sie nicht mehr in der Datei, und aus 3000 × 2000 Pixeln wurden 2000 × 1333.

**Videos** (MP4) lassen sich in PHP nicht bereinigen. Sie müssen **vorher ohne Ortsangaben exportiert** werden:
- Mac, Fotos-App: Ablage → Exportieren → 1 Video exportieren → „Ortsinformationen" abwählen
- iPhone: Teilen → Optionen → „Ort" ausschalten
- Kurz und klein halten (720p, wenige MB). Die Upload-Grenze liegt bei 50 MB; bei STRATO kann die PHP-Einstellung `upload_max_filesize` niedriger sein.

**SVG** wird nicht angenommen (SVG-Dateien können Skripte enthalten).

**HEIC** (Standardformat des iPhones) wird nicht angenommen. Fotos als **JPEG** exportieren (Fotos-App: Ablage → Exportieren → „JPEG") oder am iPhone unter Einstellungen → Kamera → Formate „Maximale Kompatibilität" wählen.

**Größe:** bis 50 MB pro Datei (lokal in `docker/php/uploads.ini` eingestellt). Auf dem Server gilt die PHP-Einstellung von STRATO (STRATO.md, Abschnitt 1.2).

**Eigene Fotos stehen nicht in Git:** `public/media/*` ist in `.gitignore`. Nur die 19 Beispielmedien (für die Tests) sind versioniert. Eigene Fotos und Videos deshalb regelmäßig sichern, z. B. nach HiDrive.

---

## 5. Wo die Daten liegen

| Was | Wo |
|---|---|
| Reisen, Themen, Schlagwörter, Medien-Angaben, Einträge, Blöcke | MariaDB, Tabellen `trip`, `topic`, `keyword`, `media`, `entry`, `block` (+ Verknüpfungen `entry_topic`, `media_topic`, `media_keyword`) |
| Kontaktnachrichten | Tabelle `message` |
| Konten der Verwaltung | Tabelle `admin_user` (Passwort nur als Hash) |
| Foto- und Videodateien | `public/media/` |
| `data/*.json` | nur noch **Startinhalte**: `make setup` bzw. `bin/console app:import-json` übernimmt sie in eine leere Datenbank; außerdem Grundlage der Testdatenbank |

Die lokale Datenbank liegt im Docker-Volume `db-data` und bleibt bei `make down` erhalten. Zum Ansehen mit einem Datenbankprogramm: `localhost:3307`, Benutzer `tokart`, Passwort `tokart` (nur lokal erreichbar).

**Achtung – nicht in Git, also selbst sichern:**
- Die **Datenbank** steckt nur im Docker-Volume. `docker compose down -v`, „Clean / Purge data" in Docker Desktop oder ein Neuaufsetzen von Docker löschen sie. Sicherung: `make db-export` (Inhalte als SQL in `deploy/sql/`).
- **Eigene Fotos und Videos** in `public/media/`.
- Beides regelmäßig nach HiDrive kopieren, solange lokal gepflegt wird.

**Text „Über mich":** steht nicht in der Datenbank, sondern in `templates/home/index.html.twig` und wird dort bearbeitet.

**Beispielinhalte entfernen:** in der Verwaltung löschen (erst die Einträge, dann Fotos, Reisen, Themen). `data/*.json` und die Beispieldateien in `public/media/` bleiben dabei unberührt – sie sind die Startinhalte der Testdatenbank, `make test` funktioniert also weiter.

---

## 6. Lokal oder auf dem Server pflegen?

**Empfehlung: nach dem Online-Gang nur noch auf dem Server pflegen** (`https://deine-domain.de/admin`).

- **Vor dem Online-Gang:** Inhalte lokal anlegen, dann **einmal** übertragen: `make db-export` (Inhalte als SQL, ohne Konten und Nachrichten) + `public/media/` mit dem Release – siehe STRATO.md, Abschnitt 3.
- **Danach:** Der Server ist maßgeblich. Die lokale Datenbank dient nur noch der Entwicklung. Bei Updates werden `public/media/` und die Datenbank auf dem Server **nicht** überschrieben (STRATO.md, Abschnitt 5).

Beides zu mischen (mal lokal, mal online) führt zu zwei unterschiedlichen Ständen – das vermeiden.

---

## 7. Sicherheit

| Maßnahme | Umsetzung |
|---|---|
| Zugang nur nach Anmeldung | `access_control: ^/admin → ROLE_ADMIN` |
| Passwörter | mindestens 12 Zeichen; gespeichert nur als Hash (`auto` = bcrypt/argon2) |
| Schutz gegen Durchprobieren | höchstens 5 Anmeldeversuche pro Minute (`login_throttling`) |
| Formulare | CSRF-Schutz bei Anmeldung, Speichern und Löschen |
| Cookies | Sitzungs-Cookie nur auf der Kontaktseite und nach dem Anmelden; alle übrigen Seiten (auch `/login`) setzen keines (geprüft) |
| Uploads | nur JPEG, PNG, WebP, MP4; Fotos ohne Metadaten; zufälliger Teil im Dateinamen |
| Konten auf dem Server | nie das Entwicklungskonto übertragen; eigenes Konto per SQL anlegen (STRATO.md) |

---

## 8. Grenzen und mögliche Erweiterungen

| Grenze | Mögliche Lösung |
|---|---|
| Reihenfolge der Blöcke über Nummern, nicht per Ziehen | Sortier-Erweiterung für EasyAdmin oder eigenes Feld |
| Galerie: Nach dem Bearbeiten erscheinen die Fotos in der Reihenfolge der Auswahlliste (nach Kennung), nicht in der Klick-Reihenfolge | eigene Galerie-Komponente mit Sortierung |
| Videos werden nicht automatisch bereinigt | Export ohne Ort (Abschnitt 4); auf einem V-Server wäre ffmpeg möglich |
| Kein Zuschneiden von Fotos | bewusst: keine Bildbearbeitung (Projektgrundsatz) |
| Eine Rolle (Administratorin) | weitere Rollen für Freundeskreis/Kommentare mit Symfony Security |
| Keine Versionsgeschichte | STRATO sichert die Datenbank automatisch; zusätzlich `make db-export` bzw. Export aus phpMyAdmin |
| Keine Vorschau vor dem Speichern | „Speichern und weiter bearbeiten" und „Ansehen" |
