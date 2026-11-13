# tok-art-projekt – Foto-Tagebuch mit Symfony, Vue und Verwaltung

Ein digitales Reisetagebuch mit Fotos, Videos und Texten – die Symfony-Umsetzung des Nuxt-Projekts „Foto-Tagebuch". Alle Funktionen entsprechen dem Original; Architektur und Domain-Logik sind aus TypeScript nach PHP übertragen. Interaktive Teile (Fotogalerie, Kontaktformular) sind Vue-Komponenten. Die Inhalte liegen in **MariaDB** und werden in einer **Verwaltung (EasyAdmin)** unter `/admin` gepflegt.

Alle Daten bleiben bei mir: keine externen Dienste, keine Cookies auf den öffentlichen Seiten außer der Kontaktseite, keine EXIF-Auswertung, keine Koordinaten – hochgeladene Fotos werden automatisch von Metadaten befreit.

| Dokument | Inhalt |
|---|---|
| [VERWALTUNG.md](VERWALTUNG.md) | Bedienung der Verwaltung; warum EasyAdmin und MariaDB (statt Strapi und PostgreSQL) |
| [TECHNIK.md](TECHNIK.md) | Aufbau, Datenbank, Sicherheit, Tests, Entscheidungen, Hinweise aus der Einrichtung |
| [STRATO.md](STRATO.md) | Veröffentlichung auf STRATO Hosting Basic, Schritt für Schritt |

## Schnellstart

Voraussetzung: **Docker Desktop**. PHP, Composer, MariaDB und Node.js laufen in Containern.

```bash
make setup                          # einmalig: starten, Tabellen anlegen, Beispielinhalte übernehmen
make admin EMAIL=deine@adresse.de   # Konto für die Verwaltung anlegen (Passwort wird abgefragt)
```

Danach:

```bash
make up        # starten → http://localhost:8000, Verwaltung: http://localhost:8000/admin
make check     # alle Tests und Prüfungen (PHP und Vue)
make down      # stoppen (die Datenbank bleibt erhalten)
```

| Container | Aufgabe | Adresse |
|---|---|---|
| `app` | PHP 8.3 mit Symfony | http://localhost:8000 – hier die App öffnen |
| `db` | MariaDB 10.11 (wie bei STRATO) | nur für die Container; zum Ansehen `127.0.0.1:3307`, `tokart`/`tokart` |
| `node` | Vite für Vue, TypeScript und CSS, mit Hot Reload | http://localhost:5180 – nur für Dateien |

Alle Kurzbefehle: `make help`.

## Was die App kann

**Website** – fünf Tabs, für den Desktop gebaut:

| Tab | Inhalt | Technik |
|---|---|---|
| **Über mich** | Startseite | Twig |
| **Reisen** | alle Reisen, neueste zuerst; je Reise ihre Einträge als Zeitleiste | Twig |
| **Themen** | alle Themen; je Thema seine Einträge als Zeitleiste; Gruppe „Ohne Thema" | Twig |
| **Fotos** | Galerie mit vier kombinierbaren, facettierten Filtern (Reise, Thema, Jahr, Schlagwort) und Info-Icon an jedem Foto | Twig + **Vue** |
| **Kontakt** | Formular; Nachrichten landen in der Datenbank | Twig + **Vue** |

- **Einträge aus Blöcken:** Text, Bild, Galerie und Video in beliebiger Reihenfolge.
- **Ungefähre Daten:** `2016`, `2018-05` oder `2019-07-12`. Sortierung: neueste zuerst, genaueres Datum vor ungenauerem, ohne Datum am Ende, bei Gleichstand alphabetisch.
- **Facettierte Filter:** Ausgegraut ist jede Option, die mit den anderen Filtern kein Foto ergibt.
- **Ohne Neuladen:** Fotos filtern, Kontaktformular senden; die Filterauswahl steht in der Adresse.
- **Funktioniert auch ohne JavaScript:** Twig rendert jede Seite vollständig.

**Verwaltung** unter `/admin` (nach Anmeldung):

- Einträge mit **Block-Editor** – je Blockart werden nur die passenden Felder gezeigt
- Reisen, Themen, Schlagwörter
- **Fotos und Videos hochladen** – Fotos werden automatisch gedreht, auf 2000 Pixel verkleinert und **von allen Metadaten befreit** (GPS, Kamera)
- **Löschschutz:** Fotos, die noch in einem Eintrag stecken, lassen sich nicht löschen
- **Kontaktnachrichten** lesen und löschen
- Anmeldung mit Passwort (mind. 12 Zeichen), höchstens 5 Versuche pro Minute

## Architektur

```
MariaDB ◄── Doctrine-Entitäten (src/Entity) ◄── Verwaltung (EasyAdmin, src/Controller/Admin)
   │
   ↓
DoctrineContentSource        übersetzt Entitäten → Domain-Objekte
   │                         (Schnittstelle ContentSource; früher JsonContentSource)
   ↓
src/Controller/              Seiten → Twig
src/Controller/Api/          JSON für Vue: GET /api/photos, POST /api/contact
   ↓
templates/  +  assets/vue/   Twig rendert, Vue-Inseln übernehmen Fotos und Kontakt

src/Domain/                  Modell und reine Logik – ohne Symfony und Doctrine, von allem genutzt
```

**Regeln**
- **Die Domain ist unabhängig.** `src/Domain/` kennt weder Symfony noch Doctrine. Beim Wechsel von JSON-Dateien auf die Datenbank blieben Domain, Seiten, API und Vue unverändert – es änderte sich eine Zeile in `config/services.yaml`.
- **Logik nur einmal, auf dem Server.** Facetten und Prüfregeln stecken in PHP; Vue fragt die API.
- **Austauschpunkte als Schnittstellen:** `ContentSource` (woher die Inhalte kommen) und `ContactInbox` (wohin Nachrichten gehen).
- **Gestaltung über Variablen.** Farben und Schriften sind CSS-Variablen in `assets/styles/app.css`.

### Projektstruktur

```
src/
  Domain/                 Modell und Logik (Dates, Entries, Filters, Timeline, Photo/, Contact/)
  Entity/                 Doctrine-Entitäten: Trip, Topic, Keyword, Media, Entry, Block, Message, AdminUser
  Content/                DoctrineContentSource (Datenbank), JsonContentSource (Startinhalte)
  Contact/                ContactHandler, DoctrineContactInbox, FileContactInbox
  Media/                  ImageSanitizer – Fotos beim Upload bereinigen
  Presentation/           PhotoGalleryPresenter – Fotos und Filter als JSON
  Command/                app:import-json, app:admin:create
  Controller/             Seiten, Api/, Admin/ (EasyAdmin), SecurityController (Anmeldung)
  Twig/                   date_de, is_active_tab, group_by_year
migrations/               Datenbank-Migrationen
templates/                Twig: Seiten, blocks/, photo/, admin/
assets/                   app.ts, styles/app.css, vue/ (Komponenten, Typen, Tests)
data/                     Startinhalte als JSON (Import und Testdatenbank)
public/media/             Fotos und Videos (auch Uploads aus der Verwaltung)
tests/                    Unit/, Integration/, Functional/, bootstrap.php (Testdatenbank)
docker/                   Router, Apache-Test, MariaDB-Initialisierung, Release-Skript
deploy/                   Vorlage für die Server-Konfiguration; .env.local und sql/ nicht in Git
```

## Nuxt → Symfony + Vue

| Foto-Tagebuch (Nuxt) | tok-art-projekt |
|---|---|
| `shared/domain/*.ts` | `src/Domain/*.php` |
| `types.ts` (Interfaces) | `readonly`-Klassen in `src/Domain/Model/`; Speicherform getrennt in `src/Entity/` |
| `server/content/json-content-source.ts` | `JsonContentSource` (Startinhalte) und `DoctrineContentSource` (Betrieb) |
| `server/utils/content.ts` (eine Zeile tauscht die Quelle) | Alias in `config/services.yaml` |
| `server/api/*.get.ts` | Seiten: Controller; Vue: `src/Controller/Api/` |
| `app/pages/trips/[id].vue` | `TripController::show()` + Twig |
| `useHead`, `definePageMeta` | `{% block title %}`, `'wide' => true` |
| `EntryTimeline.vue`, `PhotoTile.vue`, `photos.vue`, `contact.vue` | `_timeline.html.twig`, `PhotoTile.vue`, `PhotoGallery.vue`, `ContactForm.vue` |
| Facetten im `computed` der Seite | `PhotoGallery` in der PHP-Domain – mit eigenen Tests |
| `server/api/contact.post.ts` + `useStorage` (`.data/`) | `ContactApiController` + `ContactHandler` + Tabelle `message` |
| **geplant:** Verwaltung mit Strapi und PostgreSQL | **umgesetzt:** EasyAdmin und MariaDB (läuft auf STRATO Hosting Basic) |
| Vitest, `nuxi typecheck` | PHPUnit + Vitest, PHPStan + `vue-tsc` |
| Tailwind-Klassen | eigenes CSS mit denselben Design-Tokens |

## So erweiterst du die App

### Neuer Blocktyp (z. B. Zitat)

1. `src/Domain/Model/Block/QuoteBlock.php` – implementiert `Block`.
2. `src/Entity/Block.php` – Typ in `TYPES`/`TYPE_VALUES`, ggf. neue Spalte, Regel in `validateForType()`; Migration mit `doctrine:migrations:diff`.
3. `src/Content/DoctrineContentSource.php` (und `JsonContentSource`) – eine Zeile im `match`.
4. `src/Controller/Admin/BlockCrudController.php` – Feld ergänzen; `templates/admin/_block_fields.html.twig` – Feld der Art zuordnen.
5. `templates/blocks/_quote.html.twig` – die Vorlage.

### Neuer Fotos-Filter (z. B. nach Ort)

`PhotoFilter` und `PhotoGallery` (Domain), `PhotoGalleryPresenter` (Parametername, Feld), Twig-Vorlage für die Version ohne JavaScript, Tests. **Vue ändert sich nicht.**

### Andere Inhaltsquelle oder Ablage

Neue Klasse, die `ContentSource` bzw. `ContactInbox` implementiert, und eine Zeile in `config/services.yaml`.

## Qualität

| Befehl | Zweck |
|---|---|
| `make test` | PHPUnit: Domain, Bildverarbeitung, Datenbank, Seiten, API, Kontakt, Verwaltung (Testdatenbank wird jedes Mal neu aufgebaut) |
| `make stan` | PHPStan Stufe 8 mit Doctrine- und Symfony-Erweiterung |
| `make lint` | Twig, Service-Container, Datenbank-Mapping |
| `make vue-test`, `make typecheck` | Vitest, `vue-tsc` |
| `make check` | alles zusammen |

## Veröffentlichung

Vorbereitet für **STRATO Hosting Basic** (PHP 8.2+, MariaDB, kein Node.js auf dem Server):

```bash
make release         # baut release/ – genau dieser Inhalt kommt auf den Server
make release-test    # prüft release/ unter Apache → http://localhost:8080
make db-export       # Inhalte als SQL für phpMyAdmin
```

Schritt-für-Schritt-Anleitung: **[STRATO.md](STRATO.md)**. Vor dem Online-Gang außerdem: echte Inhalte, Impressum und Datenschutzerklärung, responsives Layout.

## Technik

Symfony 7.4 LTS, PHP 8.3 (kompatibel ab 8.2), Doctrine ORM 3, MariaDB 10.11, EasyAdmin 5, Twig, Vue 3, TypeScript, Vite, PHPUnit 11, Vitest, PHPStan 2, Docker.
