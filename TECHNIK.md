# Technische Dokumentation – tok-art-projekt

Wie das Projekt technisch aufgebaut ist: Umgebung, Abhängigkeiten, Ablauf einer Anfrage, Domain, Datenbank, Verwaltung, Vorlagen, Vue-Inseln, Build, Sicherheit, Tests und Betrieb.

Weitere Dokumente: [README.md](README.md) (Einstieg) · [VERWALTUNG.md](VERWALTUNG.md) (Bedienung der Verwaltung und warum EasyAdmin/MariaDB) · [STRATO.md](STRATO.md) (Veröffentlichung)

---

## Inhalt

1. Überblick
2. Entwicklungsumgebung
3. Abhängigkeiten
4. Konfiguration
5. Ablauf einer Anfrage
6. Domain-Schicht
7. Inhaltsquellen: JSON und Datenbank
8. Datenbank (Doctrine, MariaDB)
9. Verwaltung (EasyAdmin) und Anmeldung
10. Bildverarbeitung beim Upload
11. Controller und Routen
12. Präsentation: JSON für Vue
13. Vorlagen (Twig)
14. Vue-Inseln
15. Build: Vite, CSS und TypeScript
16. Kontaktformular und Sicherheit
17. Tests
18. Statische Analyse
19. Betrieb und Veröffentlichung
20. Bekannte Grenzen
21. Technische Entscheidungen und Hinweise aus der Einrichtung

---

## 1. Überblick

| | |
|---|---|
| Backend | Symfony 7.4 LTS (Support bis November 2029), PHP 8.3 in der Entwicklung, kompatibel ab PHP 8.2 |
| Datenbank | MariaDB 10.11 (wie bei STRATO), Zugriff über Doctrine ORM 3, Schema über Migrationen |
| Verwaltung | EasyAdmin 5 unter `/admin`, Anmeldung über Symfony Security |
| Seiten | Twig 3 – jede Seite wird auf dem Server vollständig gerendert |
| Interaktive Teile | Vue 3 mit TypeScript als „Inseln" (Fotogalerie, Kontaktformular) |
| Build | Vite 8 über das Pentatrion Vite Bundle; Ausgabe in `public/build/` |
| Gestaltung | eigenes CSS mit Design-Tokens (CSS-Variablen) |
| Tests | PHPUnit 11 (PHP, mit Testdatenbank), Vitest 5 mit Vue Test Utils (Vue) |
| Typprüfung | PHPStan 2 Stufe 8 mit Doctrine- und Symfony-Erweiterung, `vue-tsc` |
| Entwicklung | Docker: Container `app` (PHP), `db` (MariaDB), `node` (Vite) |

**Schichten**

```
Browser
   │ Seiten            │ GET /api/photos     │ POST /api/contact     │ /admin (angemeldet)
   ↓                   ↓                     ↓                       ↓
Controller ────── Api-Controller ─────── ContactHandler       EasyAdmin-CRUD-Controller
   │                   │                     │                       │
   ↓                   ↓                     ↓                       ↓
ContentSource ◄── PhotoGalleryPresenter   ContactInbox          Doctrine-Entitäten (src/Entity)
(Interface)                              (Interface)                     │
   │                                         │                           │
   ↓                                         ↓                           ↓
DoctrineContentSource ───────────────► DoctrineContactInbox ──────► MariaDB
   │  übersetzt Entitäten → Domain-Objekte
   ↓
Domain (src/Domain): Dates, Entries, Filters, Timeline, PhotoGallery, ContactValidator
   ↓
Twig → HTML ──► im Browser: assets/app.ts hängt Vue-Komponenten ein
```

**Zwei Modelle, bewusst getrennt:**
- `src/Domain/Model/` – die Fachsicht: `readonly`, ohne Symfony und Doctrine, von allen Seiten, der API und den Tests genutzt.
- `src/Entity/` – die Speicherform: Doctrine-Entitäten mit Settern für die Formulare der Verwaltung.

`DoctrineContentSource` übersetzt zwischen beiden. Dadurch blieben Domain, Seiten, API und Vue beim Wechsel von JSON auf die Datenbank unverändert.

---

## 2. Entwicklungsumgebung

### Docker

| Datei | Inhalt |
|---|---|
| `Dockerfile` | Container `app`: `php:8.3-cli` mit `intl`, `zip`, `opcache`, `pdo_mysql`, `gd` (JPEG, WebP), `exif`, dazu `git`, `unzip`, Composer 2 |
| `compose.yaml` | `app` (Port 8000), `db` (`mariadb:10.11`, Volume `db-data`, von außen nur `127.0.0.1:3307`), `node` (`node:24-slim`, Port 5180), `strato-test` (Profil `release`, Port 8080) |
| `docker/mariadb/init.sql` | beim ersten Start: Datenbank `tokart_test` für PHPUnit und Rechte für den Benutzer `tokart` |
| `docker/router.php` | Router für den eingebauten PHP-Server |
| `docker/apache/Dockerfile` | `php:8.3-apache` wie bei STRATO, für `make release-test` |
| `docker/php/uploads.ini` | in beide PHP-Container eingebunden: `upload_max_filesize = 50M`, `post_max_size = 55M`, `memory_limit = 512M` (ohne sie nimmt PHP nur 2 MB an) |

**PHP-Server:** `php -S 0.0.0.0:8000 -t public docker/router.php` – das Router-Skript gibt vorhandene Dateien direkt aus und alles andere an Symfony. Nur Entwicklung.

**Vite-Server:** Port **5180** (nicht der Standardport 5173, der bei der Einrichtung von einem anderen Projekt belegt war).

**Datenbank:** Benutzer/Passwort `tokart`/`tokart`, Datenbanken `tokart` (Entwicklung) und `tokart_test` (Tests). Die Daten liegen im Docker-Volume `db-data` und überstehen `make down`.

### Kurzbefehle (`Makefile`)

| Befehl | Was passiert |
|---|---|
| `make up` | Container bauen und starten |
| `make setup` | einmalig: `up`, Migrationen, Import aus `data/` |
| `make admin EMAIL=…` | Konto für `/admin` anlegen oder Passwort setzen (verdeckte Eingabe) |
| `make down` | stoppen |
| `make logs` | Ausgaben aller Container |
| `make sh` | Bash im PHP-Container |
| `make test` | PHPUnit (baut die Testdatenbank jedes Mal neu auf) |
| `make stan` | Cache aufwärmen, dann PHPStan |
| `make lint` | `lint:twig`, `lint:container`, `doctrine:schema:validate` |
| `make vue-test`, `make typecheck` | Vitest, `vue-tsc` |
| `make build` | `npm run build` → `public/build/` |
| `make check` | test + stan + lint + vue-test + typecheck |
| `make db-migrate` | neue Migrationen lokal ausführen |
| `make db-export` | Inhalte als SQL für phpMyAdmin → `deploy/sql/` (ohne Konten und Nachrichten) |
| `make db-migration-sql VERSION=…` | SQL einer Migration erzeugen, **ohne** sie auszuführen |
| `make release`, `make release-test`, `make release-stop` | Release bauen und unter Apache testen |

### Lokal ohne Docker (optional)

Mit `brew install php@8.3 composer` und der Symfony CLI; dann braucht es zusätzlich eine erreichbare MariaDB (z. B. den Docker-Container `db` über `127.0.0.1:3307`) und `DATABASE_URL` in einer `.env.local`. Composer und npm nur an einer Stelle ausführen, am besten im Container.

---

## 3. Abhängigkeiten

### PHP (Composer)

| Paket | Zweck |
|---|---|
| `symfony/framework-bundle`, `runtime`, `dotenv`, `yaml`, `console`, `flex` | Kern, Start, Konfiguration, `bin/console`, Rezepte |
| `symfony/twig-bundle` | Twig-Vorlagen |
| `symfony/asset` | Pfade zu Medien |
| `pentatrion/vite-bundle` | Vite ↔ Twig |
| `symfony/orm-pack` | Doctrine ORM, DoctrineBundle, Migrationen |
| `easycorp/easyadmin-bundle` (5.x) | Verwaltung |
| `symfony/security-bundle`, `symfony/rate-limiter` | Anmeldung, Begrenzung der Anmeldeversuche |
| `symfony/form`, `symfony/validator`, `symfony/translation` | Formulare und Prüfregeln der Verwaltung, deutsche Texte |
| `symfony/security-csrf`, `symfony/uid` | CSRF-Token, UUIDs für Nachrichten |
| `symfony/apache-pack` | `public/.htaccess` |
| PHP-Erweiterungen | `intl` (Datum, Sortierung), `pdo_mysql` (Datenbank), `gd` + `exif` (Bildverarbeitung) |

Entwicklung: `phpunit/phpunit`, `symfony/browser-kit`, `symfony/css-selector`, `phpstan/phpstan` mit `phpstan/phpstan-doctrine` und `phpstan/phpstan-symfony`.

`config.platform.php = 8.2.0`: Composer wählt nur Versionen, die auch auf PHP 8.2 laufen. `extra.symfony.docker = false`: Rezepte schreiben keine eigenen Dienste in `compose.yaml` (das Doctrine-Rezept hätte sonst einen PostgreSQL-Container hinzugefügt).

### JavaScript (npm)

`vue` (Laufzeit), `vite`, `@vitejs/plugin-vue`, `vite-plugin-symfony`, `typescript` (~6.0), `vue-tsc`, `vitest`, `@vue/test-utils`, `happy-dom`.

Bewusst **nicht** verwendet: Tailwind, Vue Router, Pinia, Monolog, ein separates CMS (siehe VERWALTUNG.md).

---

## 4. Konfiguration

| Datei | Inhalt |
|---|---|
| `.env` | Standardwerte, u. a. `DATABASE_URL` für den Docker-Container `db` |
| `.env.dev`, `.env.test` | `APP_SECRET` nur für Entwicklung bzw. Tests |
| `.env.local`, `deploy/.env.local` | eigene bzw. Server-Werte, **nicht** in Git |
| `config/packages/doctrine.yaml` | Verbindung über `DATABASE_URL`; in Tests Suffix `_test` |
| `config/packages/security.yaml` | Konten aus `admin_user`, `form_login`, `logout`, `login_throttling`, `^/admin → ROLE_ADMIN` |
| `config/packages/translation.yaml` | `default_locale: de` (EasyAdmin auf Deutsch) |
| `config/services.yaml` | Parameter, Austauschpunkte (siehe unten) |
| `config/routes/easyadmin.yaml` | Routen der Verwaltung aus `#[AdminDashboard]` |
| `migrations/` | Datenbank-Migrationen |
| `vite.config.ts`, `vitest.config.ts`, `tsconfig.json`, `package.json` | Frontend |
| `phpunit.dist.xml`, `phpstan.dist.neon` | Tests und statische Analyse |

### Austauschpunkte (`config/services.yaml`)

```yaml
App\Domain\ContentSource: '@App\Content\DoctrineContentSource'
App\Domain\Contact\ContactInbox: '@App\Contact\DoctrineContactInbox'
```

Vorher (ohne Datenbank): `JsonContentSource` und `FileContactInbox`. Beide Klassen sind noch vorhanden – `JsonContentSource` liefert die Startinhalte für den Import und die Testdatenbank.

`src/Domain/` ist von den Services ausgenommen (nur Wertobjekte und statische Funktionen).

---

## 5. Ablauf einer Anfrage

### Seite: `GET /trips/island`

1. `public/index.php` → `Kernel` → Routing → `TripController::show('island')`.
2. `ContentSource::trips()` → `DoctrineContentSource` lädt die Reisen aus MariaDB und übersetzt sie in `App\Domain\Model\Trip`.
3. `Lookup::byId()` sucht die Reise, sonst 404; `Filters::byTrip()` wählt die Einträge.
4. Twig rendert mit Zeitleiste (`group_by_year()` → `Timeline::groupByYear()`).

### Verwaltung: Eintrag speichern

1. `POST /admin/entry/{id}/edit` → EasyAdmin baut aus `EntryCrudController::configureFields()` das Formular, die Blöcke über `BlockCrudController`.
2. Symfony Validator prüft die Entität (Kennung, Datum, `Block::validateForType()`).
3. Doctrine speichert Eintrag und Blöcke in einer Transaktion (`cascade: persist`, `orphanRemoval`).
4. Der nächste Aufruf der Website liest den neuen Stand – es gibt keinen Inhalts-Cache.

---

## 6. Domain-Schicht

Unverändert gegenüber der JSON-Version.

| Bereich | Klassen |
|---|---|
| Modell (`src/Domain/Model/`) | `Trip`, `Topic`, `Keyword`, `MediaItem` (`MediaKind`), `Entry`, `Block` mit `TextBlock`, `ImageBlock`, `VideoBlock`, `GalleryBlock` – alle `readonly` |
| Logik | `Dates` (Vergleich, Format, Jahr), `Entries` (Sortierung mit `Collator`), `Filters` (Closures), `Timeline` + `YearGroup`, `Lookup` |
| Fotos (`src/Domain/Photo/`) | `PhotoGallery`, `PhotoFilter` (mit `without()` für Facetten), `PhotoWithEntry`, `ValueSet` |
| Kontakt (`src/Domain/Contact/`) | `ContactMessage`, `ContactValidator`, `ContactInbox` (Interface) |

Einzelheiten: Facetten über `PhotoFilter::without(field)` (ein Feld schränkt sich nie selbst ein); `ValueSet` statt Array-Schlüssel, weil PHP `"2018"` in die Zahl `2018` umwandelt; Blöcke liefern ihre Medien polymorph über `mediaIds()`.

---

## 7. Inhaltsquellen: JSON und Datenbank

| | `JsonContentSource` | `DoctrineContentSource` |
|---|---|---|
| Quelle | `data/*.json` | MariaDB |
| Rolle heute | Startinhalte für `app:import-json` und die Testdatenbank | Inhaltsquelle der App |
| Prüfung | Typen und Pflichtfelder beim Einlesen | Validator der Entitäten beim Speichern in der Verwaltung |

**`DoctrineContentSource`:**
- lädt Medien mit Themen und Schlagwörtern und Einträge mit Reise, Themen, Blöcken und Medien **je in einer Abfrage** (`leftJoin` + `select`), damit Seiten wie `/photos` nicht für jeden Eintrag nachladen;
- merkt sich die Einträge pro Anfrage (die Fotogalerie braucht sie mehrfach);
- liefert Reisen nach Titel, Themen und Schlagwörter nach Namen sortiert (alphabetische Auswahllisten);
- vergibt Block-IDs als `b` + Datenbank-ID (z. B. `b17`); Dateipfade als `/media/` + Dateiname.

**Import (`bin/console app:import-json`):** übernimmt Themen, Schlagwörter, Reisen, Medien und Einträge mit Blöcken in eine leere Datenbank; mit `--replace` werden vorhandene Inhalte (nicht Konten und Nachrichten) vorher gelöscht. Ein Integrationstest prüft, dass die Datenbank danach **inhaltlich genau dasselbe** liefert wie die JSON-Dateien.

---

## 8. Datenbank (Doctrine, MariaDB)

### Tabellen

| Tabelle | Inhalt | Besonderheiten |
|---|---|---|
| `trip`, `topic`, `keyword` | Stammdaten | ID ist die sprechende Kennung (`island`), Primärschlüssel als Text |
| `media` | Foto/Video | `file` = Dateiname in `public/media/`; `kind` wird aus der Dateiendung abgeleitet |
| `media_topic`, `media_keyword`, `entry_topic` | Verknüpfungen | `ON DELETE CASCADE` – Löschen eines Themas entfernt nur die Zuordnung |
| `entry` | Eintrag | `trip_id` mit `ON DELETE SET NULL` |
| `block` | Blöcke aller Arten | `type`, `position`; `content` (Text), `media_id` + `caption` (Bild/Video), `gallery_media_ids` (JSON-Liste); `entry_id` mit `ON DELETE CASCADE`; `media_id` mit `ON DELETE RESTRICT` |
| `message` | Kontaktnachrichten | UUID, Zeitpunkt in UTC |
| `admin_user` | Konten | E-Mail eindeutig, Passwort-Hash, Rollen (JSON) |
| `doctrine_migration_versions` | Stand der Migrationen | |

**Eine Tabelle für alle Blockarten** (statt Vererbung): einfach zu bearbeiten und abzufragen; welche Spalten gelten, prüft `Block::validateForType()`.

**Galerien als JSON-Liste von Medien-IDs** (statt Verknüpfungstabelle): erhält die Reihenfolge. Fehlende Medien werden beim Anzeigen übersprungen; gelöscht werden können ohnehin nur Medien, die nirgends mehr vorkommen.

### Migrationen

- Erzeugt mit `doctrine:migrations:diff` aus den Entitäten, ausgeführt mit `doctrine:migrations:migrate`.
- Die Testdatenbank wird **über die Migrationen** aufgebaut – jede Migration ist damit bei jedem Testlauf geprüft.
- `make lint` prüft mit `doctrine:schema:validate`, dass Entitäten und Datenbank zusammenpassen.
- Für STRATO ohne SSH: `make db-migration-sql VERSION=…` erzeugt das SQL mit `--dry-run --write-sql` (siehe Abschnitt 21 zu `--write-sql`).

### Prüfregeln (`Assert`)

- Kennungen: `^[a-z0-9]+(-[a-z0-9]+)*$` (`App\Entity\Patterns::ID`)
- Datum: Jahr, Jahr-Monat oder Jahr-Monat-Tag mit gültigen Monaten und Tagen (`Patterns::DATE`)
- Pflichtfelder: Titel, Namen, Datei
- Blöcke: je nach Art Text, Medium oder mindestens ein Galeriefoto

---

## 9. Verwaltung (EasyAdmin) und Anmeldung

### Controller (`src/Controller/Admin/`)

| Controller | Inhalt |
|---|---|
| `DashboardController` | `#[AdminDashboard(routePath: '/admin', routeName: 'admin')]`, Menü, leitet auf die Einträge weiter |
| `EntryCrudController` | Eintrag mit Blöcken (`CollectionField` + `useEntryCrudForm(BlockCrudController)`), Aktion „Ansehen", Skript zum Ein-/Ausblenden der Blockfelder |
| `BlockCrudController` | Felder eines Blocks; nicht im Menü |
| `TripCrudController`, `TopicCrudController`, `KeywordCrudController` | Stammdaten |
| `MediaCrudController` | Upload (`FileField` mit eigener `upload_new`-Funktion → `ImageSanitizer`), Vorschau, Löschschutz, Datei mitlöschen |
| `MessageCrudController` | nur lesen und löschen (`NEW`, `EDIT` deaktiviert) |
| `IdField` | Kennungsfeld: beim Anlegen frei, beim Bearbeiten gesperrt |

Routen folgen dem Muster `admin_<entity>_<action>` (z. B. `admin_entry_edit` → `/admin/entry/{entityId}/edit`); Liste mit `bin/console debug:router`.

**EasyAdmin 5** unterscheidet sich deutlich von Version 4 (z. B. `MenuItem::linkTo()` statt `linkToCrud()`, `#[AdminDashboard]` statt `#[Route]`). Das Paket bringt unter `vendor/easycorp/easyadmin-bundle/skills/easyadmin/` eine genaue API-Beschreibung mit, nach der umgesetzt wurde.

### Blockfelder je nach Art

`templates/admin/_block_fields.html.twig` (über `configureAssets()->addHtmlContentToBody()`) blendet Felder aus, die zur gewählten Art nicht passen; es reagiert auch auf neu hinzugefügte Blöcke (`ea.collection.item-added`). Ohne JavaScript sind alle Felder sichtbar; geprüft wird ohnehin auf dem Server.

### Löschschutz für Medien

`MediaCrudController::deleteEntity()` sucht das Medium in Bild-, Video- und Galerieblöcken. Wird es noch benutzt, erscheint eine Meldung mit den Einträgen und nichts wird gelöscht; zusätzlich verhindert `ON DELETE RESTRICT` in der Datenbank das Löschen eines Mediums mit Bild-/Videoblock. Nach dem Löschen wird die Datei entfernt, wenn kein anderes Medium sie nutzt – **außer** bei Dateien der Beispielmedien aus `data/media.json`, die die Testdatenbank braucht.

### Anmeldung

| Teil | Umsetzung |
|---|---|
| Konten | `App\Entity\AdminUser` (`UserInterface`, `PasswordAuthenticatedUserInterface`), Tabelle `admin_user` |
| Anlegen | `bin/console app:admin:create E-MAIL` (Passwort verdeckt, mind. 12 Zeichen); `--sql` gibt nur ein `INSERT … ON DUPLICATE KEY UPDATE` mit dem Hash aus (für phpMyAdmin) |
| Formular | `SecurityController::login()` rendert `@EasyAdmin/page/login.html.twig` (Deutsch) |
| Firewall | `form_login` mit CSRF, `logout`, `lazy: true`, `login_throttling` (5 pro Minute) |
| Zugriff | `access_control: ^/admin → ROLE_ADMIN` |

`lazy: true`: Die Sitzung startet erst, wenn sie gebraucht wird. Öffentliche Seiten und `/login` setzen kein Cookie (geprüft); das Sitzungs-Cookie gibt es nur auf der Kontaktseite (CSRF-Token in der Sitzung) und nach dem Anmelden.

---

## 10. Bildverarbeitung beim Upload

`App\Media\ImageSanitizer::sanitize(path)` – aufgerufen direkt nach dem Verschieben der hochgeladenen Datei:

1. **JPEG, PNG, WebP** werden mit GD geladen; andere Dateien (MP4) bleiben unverändert.
2. **Drehung:** bei JPEG wird `Orientation` aus den EXIF-Daten gelesen (`exif_read_data`) und das Bild gedreht (3 → 180°, 6 → −90°, 8 → 90°). Die Metadaten werden dafür nur gelesen.
3. **Verkleinern** auf höchstens 2000 Pixel an der langen Seite (`imagescale`, bikubisch).
4. **Neu speichern** (JPEG/WebP Qualität 85, PNG Stufe 6). GD schreibt **keine** Metadaten – GPS, Kameramodell und Aufnahmezeit fallen weg.

Erlaubte Typen beim Upload: `image/jpeg`, `image/png`, `image/webp`, `video/mp4`; höchstens 50 MB; Dateiname `[slug]-[randomhash].[extension]`. SVG ist ausgeschlossen (kann Skripte enthalten).

Geprüft mit Unit-Tests (eingebettete Kamera- und GPS-Angaben verschwinden, Verkleinerung 3000 × 1500 → 2000 × 1000, kleine Bilder unverändert, MP4 unverändert) und im Browser (Upload in der Verwaltung, lokal und im Release unter Apache).

---

## 11. Controller und Routen

| Route | Name | Controller | Besonderheit |
|---|---|---|---|
| `GET /` | `home` | `HomeController` | |
| `GET /trips`, `/trips/{id}` | `trip_index`, `trip_show` | `TripController` | 404 bei unbekannter ID |
| `GET /topics`, `/topics/{id}` | `topic_index`, `topic_show` | `TopicController` | Gruppe „Ohne Thema" |
| `GET /entries/{id}` | `entry_show` | `EntryController` | 404 |
| `GET /photos` | `photo_index` | `PhotoController` | Twig + Startdaten für Vue |
| `GET, POST /contact` | `contact` | `ContactController` | Formular ohne JavaScript |
| `GET /api/photos` | `api_photos` | `Api\PhotoApiController` | JSON |
| `POST /api/contact` | `api_contact` | `Api\ContactApiController` | JSON; 201/422/400 |
| `GET, POST /login`, `GET /logout` | `app_login`, `app_logout` | `SecurityController` | |
| `/admin…` | `admin`, `admin_*` | `Admin\*` | nur mit `ROLE_ADMIN` |

---

## 12. Präsentation: JSON für Vue

`App\Presentation\PhotoGalleryPresenter` übersetzt die Domain in die Daten der Vue-Galerie (Filter mit Optionen und „available", Fotos mit Adressen) – für `data-props` der Seite und für `GET /api/photos`. Ein Funktionstest prüft, dass beide identisch sind. Die Form ist als `@phpstan-type` beschrieben und in `assets/vue/types.ts` gespiegelt.

---

## 13. Vorlagen (Twig)

| Vorlage | Inhalt |
|---|---|
| `base.html.twig` | Gerüst, Titel, Navigation, Vite-Tags |
| `_timeline.html.twig`, `blocks/_*.html.twig` | Zeitleiste, je Blocktyp eine Vorlage |
| `photo/index.html.twig`, `photo/_tile.html.twig` | Vue-Einhängepunkt + Version ohne JavaScript |
| `contact/index.html.twig`, `contact/sent.html.twig` | Vue-Einhängepunkt + HTML-Formular |
| `admin/field/media_preview.html.twig` | Vorschau von Foto/Video in der Verwaltung |
| `admin/_block_fields.html.twig` | Skript für den Block-Editor |
| `bundles/TwigBundle/Exception/error404.html.twig` | eigene 404-Seite |

Twig-Erweiterung `AppExtension`: Filter `date_de`, Funktionen `is_active_tab()`, `group_by_year()`. Automatisches Escaping; `strict_variables` in Tests.

---

## 14. Vue-Inseln

`assets/app.ts` hängt Komponenten an Elemente mit `data-vue-component` (Startdaten in `data-props`). Komponenten: `PhotoGallery.vue` (Filter über `/api/photos`, `history.pushState`, Schutz gegen veraltete Antworten), `PhotoTile.vue` (Info-Fenster), `ContactForm.vue` (JSON an `/api/contact`, Fehler je Feld). Die Logik bleibt in PHP. Einzelheiten unverändert gegenüber der Einführung der Vue-Inseln.

---

## 15. Build: Vite, CSS und TypeScript

- **Entwicklung:** Container `node` startet `vite` auf Port 5180; `entrypoints.json` verweist auf den Vite-Server, Hot Reload.
- **Betrieb:** `npm run build` bzw. `make release` → Dateien mit Hash im Namen, Twig liest `entrypoints.json`.
- **CSS:** Design-Tokens unter `:root`, eine Datei für Twig und Vue; die Verwaltung nutzt das eigene Design von EasyAdmin (`public/bundles/easyadmin/`, per `assets:install`).
- **TypeScript:** `strict`, `noUncheckedIndexedAccess`, `verbatimModuleSyntax`; geprüft mit `vue-tsc`.

---

## 16. Kontaktformular und Sicherheit

**Ablauf:** Vue → `POST /api/contact` (JSON) bzw. ohne JavaScript → `POST /contact`; beide über `ContactHandler` (normalisieren, Honeypot, `ContactValidator`) → `ContactInbox::save()` → **`DoctrineContactInbox`** speichert in der Tabelle `message`. Gelesen und gelöscht wird in der Verwaltung.

| Maßnahme | Wo |
|---|---|
| CSRF | Kontaktformular, Anmeldung, alle Formulare der Verwaltung |
| Honeypot | Feld `website` |
| Eingaben normalisieren, dann prüfen | `ContactMessage::fromInput()`, Validator der Entitäten |
| Ausgabe maskieren | Twig und Vue |
| Anmeldung | Passwort-Hash, mind. 12 Zeichen, 5 Versuche pro Minute |
| Uploads | Typen begrenzt, SVG ausgeschlossen, Metadaten entfernt, zufälliger Dateiname |
| Datenbank | Zugangsdaten nur in `.env.local.php` (Server) bzw. `deploy/.env.local` (lokal, nicht in Git); von außen nur `127.0.0.1:3307` |
| Keine Daten in Git | `var/`, `.env.local`, `deploy/.env.local`, `deploy/sql/`, `node_modules/`, `public/build/`, `release/`, **`public/media/*`** (eigene Fotos; die 19 Beispielmedien bleiben versioniert) |
| Sitzung nur bei Bedarf | `lazy: true` |
| `.htaccess` | HTTPS, `/build/.vite/` gesperrt, Sicherheits-Header |

---

## 17. Tests

```
tests/
  bootstrap.php            Testdatenbank neu: create, schema:drop, migrations:migrate, app:import-json, Testkonto
  TestAdmin.php            Zugangsdaten des Testkontos
  Unit/Domain/             Dates, Entries, Filters, Timeline, ContactValidator, PhotoGallery, ValueSet
  Unit/Content/            JsonContentSourceTest (Integrität der Startdaten)
  Unit/Media/              ImageSanitizerTest
  Integration/             DoctrineContentSourceTest (Datenbank liefert dasselbe wie JSON)
  Functional/              PagesTest, ContactTest, ApiTest, AdminTest
assets/vue/                query.test.ts, PhotoTile.test.ts, ContactForm.test.ts
```

Stand: **97 PHP-Tests** (377 Zusicherungen) und **13 Vue-Tests**.

| Art | Beispiele |
|---|---|
| Domain, Vue | wie zuvor (Sortierung, Filter, Facetten, Komponenten) |
| Bildverarbeitung | Metadaten entfernt, Verkleinerung, kleine Bilder und Videos unverändert |
| Integration | Import + `DoctrineContentSource` = JSON-Inhalte (Reisen, Themen, Schlagwörter, Medien, Einträge mit Blockreihenfolge) |
| Seiten, API, Kontakt | wie zuvor; Nachrichten jetzt in der Datenbank geprüft |
| Verwaltung | ohne Anmeldung → `/login`; Anmelden mit Passwort; falsches Passwort; öffentliche Seiten ohne Cookie; alle Listen- und Formularseiten; Nachrichten nicht anlegbar (403); neue Reise erscheint auf der Website; ungültige Kennung und ungültiges Datum abgelehnt; Bildblock ohne Foto ungültig; benutztes Foto nicht löschbar; Löschen eines Eintrags entfernt seine Blöcke |

**Browser-Prüfung** bei der Einrichtung (Chrome, gesteuert mit Puppeteer – nicht Teil des Projekts): Anmelden, alle Seiten der Verwaltung, Foto mit Kamera- und GPS-Angaben hochladen (danach ohne Metadaten, 2000 × 1333), Eintrag mit Text- und Bildblock anlegen, Blockfelder werden je Art ausgeblendet, Eintrag auf der Website sichtbar, Löschschutz und Mitlöschen der Datei – lokal und im Release unter Apache.

Die Meldungen `[error] Uncaught PHP Exception …` während der Tests stammen aus Tests, die absichtlich 404, 400 oder 403 auslösen.

---

## 18. Statische Analyse

PHPStan **Stufe 8** mit:
- **`phpstan-doctrine`** – kennt die Entitäten (z. B. dass die Datenbank IDs vergibt); lädt dafür den EntityManager über `tests/phpstan/object-manager.php`.
- **`phpstan-symfony`** – kennt den Container (Typ von `getContainer()->get(...)`); liest `var/cache/dev/App_KernelDevDebugContainer.xml`, deshalb wärmt `make stan` vorher den Cache auf.

`vue-tsc` prüft alle `.ts`- und `.vue`-Dateien.

---

## 19. Betrieb und Veröffentlichung

Ausführlich in [STRATO.md](STRATO.md). Kurz:

- `make release` → `release/` mit `vendor/` (ohne Entwicklungspakete), `migrations/`, `public/build/`, `public/bundles/`, `.env.local.php` (`APP_ENV=prod`, `APP_SECRET`, `DATABASE_URL` aus `deploy/.env.local`).
- `make release-test` → Apache mit `pdo_mysql`, `gd`, `exif`; nutzt die **lokale** Datenbank (`DATABASE_URL` als echte Umgebungsvariable in `compose.yaml` hat Vorrang vor `.env.local.php`).
- Erster Import auf dem Server: `make db-export` + phpMyAdmin; Konto: `app:admin:create … --sql` + phpMyAdmin.
- Spätere Schema-Änderungen: SSH (`doctrine:migrations:migrate`) oder `make db-migration-sql` + phpMyAdmin.
- Bei Updates **nicht** überschreiben: `var/` und `public/media/` (Uploads aus der Verwaltung); danach `var/cache/` löschen.

Geprüft (13.11.2026) unter Apache im Produktionsmodus: Seiten, 404, `.htaccess`-Regeln, Verwaltung mit Anmeldung, Upload mit Bereinigung, Eintrag anlegen, Löschschutz; außerdem Import von `make db-export` und Konto-SQL in eine leere Datenbank (wie phpMyAdmin).

---

## 20. Bekannte Grenzen

| Grenze | Mögliche Lösung |
|---|---|
| Fotos und Kontakt haben ihr HTML zweimal (Twig ohne JavaScript, Vue) | bewusster Preis für „funktioniert ohne JavaScript" |
| Nur für den Desktop gestaltet | Media Queries in `app.css` |
| Blockreihenfolge über Nummern; Galerie-Reihenfolge nach dem Bearbeiten = Reihenfolge der Auswahlliste | Sortier-Komponente für EasyAdmin |
| Videos werden nicht bereinigt | Export ohne Ort; ffmpeg erst auf einem V-Server |
| Keine Begrenzung der Anfragen für das Kontaktformular | `symfony/rate-limiter` ist bereits installiert (für die Anmeldung) – kann auch hier genutzt werden |
| Kein Inhalts-Cache: jede Seite fragt die Datenbank | bei vielen Besucher:innen HTTP-Cache oder `symfony/cache` |
| Protokolle nur auf der Standardausgabe | `symfony/monolog-bundle` |
| Eine Rolle | weitere Rollen für Freundeskreis/Kommentare |

---

## 21. Technische Entscheidungen und Hinweise aus der Einrichtung

### Entscheidungen

| Entscheidung | Begründung |
|---|---|
| Symfony 7.4 LTS statt 8 | lange Unterstützung, läuft ab PHP 8.2 (STRATO-kompatibel) |
| Docker für die Entwicklung | gleiche Umgebung überall |
| `platform.php = 8.2` | Pakete bleiben mit PHP 8.2 kompatibel |
| Domain ohne Symfony und Doctrine | testbar, unabhängig; Wechsel JSON → Datenbank ohne Änderung an Domain, Seiten und Vue |
| **MariaDB statt PostgreSQL** | STRATO Hosting Basic bietet nur MySQL/MariaDB (siehe VERWALTUNG.md) |
| **EasyAdmin statt Strapi** | läuft in der App auf Hosting Basic; Strapi bräuchte Node.js und einen V-Server (siehe VERWALTUNG.md) |
| Entitäten getrennt von der Domain | Formulare brauchen veränderbare Objekte mit Settern, die Domain bleibt `readonly` |
| Sprechende IDs als Primärschlüssel | stehen in den Adressen; nach dem Anlegen gesperrt |
| Eine Tabelle für alle Blockarten | einfach; Prüfung je Art im Validator |
| Galerie als JSON-Liste | erhält die Reihenfolge |
| Bildbereinigung beim Upload | Datenschutz ohne Handarbeit; keine Metadaten auf dem Server |
| Testdatenbank über Migrationen | prüft die Migrationen bei jedem Lauf |
| Vue-Inseln statt Single-Page-App | Seiten serverseitig, ohne JavaScript nutzbar |
| Logik nur auf dem Server | keine doppelten Regeln in PHP und TypeScript |
| Vite statt AssetMapper | `.vue`-Dateien und TypeScript brauchen einen Build-Schritt |
| Vite-Port 5180 | 5173 war durch ein anderes Projekt belegt |
| Release-Ordner, kein mitgelieferter Cache | nur Nötiges auf den Server; Cache enthält feste Pfade |
| Apache-Test in Docker | `.htaccess`, Produktionsmodus und Datenbank geprüft, bevor etwas auf den Server kommt |

### Erwogene Varianten für Vue

| Weg | Warum (nicht) |
|---|---|
| 1. Vue über AssetMapper, ohne Build | keine `.vue`-Dateien, kein TypeScript |
| **2. Vue-Inseln mit Vite** | **gewählt** – Twig rendert, Vue übernimmt einzelne Bereiche |
| 3. Single-Page-App | ohne JavaScript leere Seite, schlechter für Suchmaschinen |

### Hinweise aus der Einrichtung

- **`composer remove` löscht Dateien des Rezepts** – auch geänderte (so geschehen mit `assets/app.js` und `assets/styles/app.css`). **Vor `composer remove` committen.**
- **Rezepte und Docker:** Ohne `extra.symfony.docker = false` hätte das Doctrine-Rezept einen PostgreSQL-Dienst in `compose.yaml` eingetragen.
- **`doctrine:migrations:execute … --write-sql` führt die Migration aus!** Nur zusammen mit `--dry-run` entsteht reines SQL ohne Änderung an der Datenbank. Bei der Einrichtung lief der Befehl ohne `--dry-run` gegen die Entwicklungsdatenbank und scheiterte nur, weil die Tabellen schon existierten. `make db-migration-sql` setzt `--dry-run` deshalb fest.
- **`#[Assert\Choice(self::TYPES)]` mit einem Array „Beschriftung => Wert"** liest Symfony als *Optionen* der Regel, nicht als Auswahl – das ergab erst beim Speichern einen Fehler 500. Richtig: `#[Assert\Choice(choices: [...])]` mit einer einfachen Werteliste.
- **Doctrine legt Verknüpfungstabellen (ManyToMany) bereits mit `ON DELETE CASCADE` an** – die ausdrücklichen `JoinColumn`-Angaben in den Entitäten dokumentieren nur die Absicht.
- **EasyAdmin 5 ≠ EasyAdmin 4:** Viele Anleitungen im Netz beziehen sich auf Version 4; die mitgelieferte Beschreibung unter `vendor/easycorp/easyadmin-bundle/skills/` ist maßgeblich.
- **Hochgeladene Dateien verarbeiten:** EasyAdmin verschiebt Uploads in der Formular-Option `upload_new`; dort lässt sich eine eigene Funktion einsetzen (hier: verschieben und `ImageSanitizer`).
- **Vite-Port belegt:** Antwortet `curl localhost:5173/…` mit einer fremden App, belegt ein anderes Projekt den Port – eigener Port (5180).
- **`.htaccess`:** `[R=404,L]` führte zu 500, `[F]` (403) funktioniert; ein 500 direkt nach dem Kopieren kam von einer halb übertragenen Datei.
- **`json_encode([])` ergibt `[]`**, nicht `{}` – Vue-Props, die ein Objekt erwarten, müssen beides annehmen.
- **Vue entfernt Leerzeichen zwischen zwei Elementen** – Abstände aus dem CSS.
- **PHP-Grenzen im Docker-Image:** Die offiziellen PHP-Images haben keine `php.ini`; es gelten 2 MB pro Upload und 128 MB Speicher. Für Handyfotos zu wenig – deshalb `docker/php/uploads.ini`. Auf STRATO in den PHP-Einstellungen entsprechend setzen.
- **Eigene Fotos und Git:** Uploads landen in `public/media/`, wo auch die versionierten Beispielmedien liegen. `/public/media/*` in `.gitignore` hält neue Dateien aus Git heraus, ohne die bereits versionierten zu entfernen.
- **Beispieldateien beim Löschen schützen:** Würde die Verwaltung die Beispiel-SVGs mitlöschen, fänden die Tests ihre Dateien nicht mehr.
