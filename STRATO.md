# Veröffentlichen auf STRATO

Schritt für Schritt: das Foto-Tagebuch (tok-art-projekt) mit Datenbank und Verwaltung auf **STRATO Hosting Basic** einrichten, hochladen, prüfen und aktualisieren.

> Die Bezeichnungen der Menüs im STRATO-Kundenlogin können sich ändern. Wenn ein Punkt anders heißt, hilft die STRATO-Hilfe unter den genannten Stichwörtern.

---

## Überblick

| Wo | Was |
|---|---|
| **Dein Rechner** | `make release` baut den Ordner `release/` – genau dieser Inhalt kommt auf den Server |
| **Dein Rechner** | `make release-test` prüft `release/` vorher unter Apache mit Datenbank, wie bei STRATO |
| **Dein Rechner** | `make db-export` erzeugt die Inhalte als SQL-Datei für den ersten Import |
| **STRATO-Kundenlogin** | einmalig: PHP-Version und -Erweiterungen, Datenbank, Zielverzeichnis der Domain, SSL |
| **phpMyAdmin** (bei STRATO) | Datenbank füllen, Konto anlegen, später Schema-Änderungen |
| **SFTP** | `release/` hochladen |

Auf dem Server werden **kein** Node.js und **kein** Composer gebraucht. SSH ist optional.

---

## 0. Warum dieser Weg

### Die vorhandenen STRATO-Produkte

| Produkt | Was es kann | Rolle für das Projekt |
|---|---|---|
| **Hosting Basic** | klassisches Webhosting: Apache, PHP (8.2–8.5), SFTP/SSH, SSL-Zertifikate, **MySQL/MariaDB-Datenbanken** mit automatischer Sicherung. **Kein Node.js, kein Root-Zugriff.** | liefert die App aus, speichert die Inhalte |
| **HiDrive** | Cloud-Speicher, **kein Webspace** | Sicherungen: Originalfotos, Releases, Datenbank-Exporte, `deploy/.env.local` |

### Warum die Symfony-Version dort direkt läuft

- Hosting Basic führt **PHP** aus – genau das braucht Symfony. Seiten, API, Kontaktformular und Verwaltung laufen vollständig auf dem Server.
- **MariaDB** ist im Paket enthalten – dieselbe Datenbank wie in der Entwicklung (Docker, Version 10.11).
- **Vue braucht auf dem Server kein Node.js:** Vite baut Vue, TypeScript und CSS vorher auf dem eigenen Rechner zu fertigen Dateien (`public/build/`).
- **Composer ist nicht nötig:** `make release` installiert die PHP-Pakete vorher; `vendor/` wird mit hochgeladen.

Zum Vergleich: Das Nuxt-Original braucht einen Node.js-Server und kann auf Hosting Basic nur als statische Seite laufen (siehe dort `VEROEFFENTLICHUNG.md`). Warum die Verwaltung mit **EasyAdmin und MariaDB** statt Strapi und PostgreSQL umgesetzt ist: [VERWALTUNG.md](VERWALTUNG.md), Abschnitt 1.

### Wann ein V-Server nötig würde

Erst, wenn Dienste dazukommen, die eigene Prozesse brauchen – z. B. Strapi mit PostgreSQL. Dann: STRATO V-Server (Linux, Root), Node.js, nginx, HTTPS mit Let's Encrypt, Firewall, Updates und Backups in eigener Verantwortung. Für das Foto-Tagebuch in der jetzigen Form reicht Hosting Basic.

### Was STRATO-seitig nicht automatisch geprüft werden konnte

Die Angaben zu den Paketen stammen von den STRATO-Webseiten (Stand November 2026). Nicht nachprüfbar waren von hier aus:
- welche PHP-Erweiterungen im Paket aktiv sind (Abschnitt 1.2),
- die genaue Datenbank-Version und die Form der Zugangsdaten (Abschnitt 1.5),
- welche PHP-Version die Kommandozeile per SSH nutzt (Abschnitt 5.2),
- die genauen **Menünamen** im Kundenlogin.

---

## 1. Einmalig im STRATO-Kundenlogin

### 1.1 PHP-Version

Unter den PHP-Einstellungen des Pakets **PHP 8.3** wählen (mindestens 8.2). Das ist dieselbe Version wie in der Entwicklung.

### 1.2 PHP-Erweiterungen prüfen – wichtig

| Erweiterung | Wofür | Ohne sie |
|---|---|---|
| **`intl`** | deutsche Datumsangaben („12. Juli 2019"), deutsche Sortierung | App startet nicht („Class Collator not found") |
| **`pdo_mysql`** | Verbindung zur Datenbank | App startet nicht |
| **`gd`** (mit JPEG und WebP) | Fotos beim Upload drehen, verkleinern, von Metadaten befreien | Upload von Fotos schlägt fehl |
| **`exif`** | Drehung von Handyfotos erkennen | Fotos werden nicht automatisch gedreht (Upload funktioniert trotzdem) |

In den PHP-Einstellungen bzw. der Liste der Erweiterungen nachsehen und bei Bedarf einschalten. Ist das nicht möglich: beim STRATO-Support nachfragen, **bevor** du hochlädst.

**Upload-Grenzen:** PHP nimmt ohne Anpassung oft nur wenige MB pro Datei an; Handyfotos haben 3–10 MB. In den PHP-Einstellungen nach Möglichkeit setzen (wie lokal in `docker/php/uploads.ini`):

| Einstellung | Wert |
|---|---|
| `upload_max_filesize` | 50M |
| `post_max_size` | 55M |
| `memory_limit` | 512M (GD braucht für große Fotos viel Speicher) |

Sind höhere Werte nicht möglich: Fotos vor dem Upload auf ca. 2000 Pixel verkleinern.

**Warum es für `intl` keinen Ersatz gibt:** Das Ersatzpaket `symfony/polyfill-intl-icu` unterstützt nur Englisch. Deutsche Monatsnamen und die richtige Einordnung von Umlauten („Ägypten" vor „Zug") gehen nur mit der echten Erweiterung.

### 1.3 Ordner und Zielverzeichnis der Domain

1. Per SFTP im Webspace einen Ordner anlegen, z. B. **`/tok-art`**.
2. Im Kundenlogin bei der Domain das **Zielverzeichnis** auf **`/tok-art/public`** setzen.

**Warum `public`?** Nur dieser Ordner darf aus dem Internet erreichbar sein. Zeigt die Domain auf `/tok-art`, wären Konfiguration (inklusive Datenbank-Passwort in `.env.local.php`) und Code öffentlich abrufbar.

### 1.4 SSL

Für die Domain das SSL-Zertifikat aktivieren. Die App leitet alle Aufrufe automatisch auf `https://` um (`public/.htaccess`).

### 1.5 Datenbank anlegen und Zugangsdaten eintragen

1. Im Kundenlogin unter **Datenbanken** eine neue Datenbank anlegen und ein sicheres Passwort vergeben.
2. Die Zugangsdaten notieren: **Host** (Server), **Datenbankname**, **Benutzer**, **Passwort** und die angezeigte **Version** (z. B. MariaDB 10.11 oder MySQL 8).
3. Auf dem eigenen Rechner in **`deploy/.env.local`** die Zeile `DATABASE_URL` ausfüllen:

   ```
   DATABASE_URL="mysql://BENUTZER:PASSWORT@HOST:3306/DATENBANK?serverVersion=10.11.0-MariaDB&charset=utf8mb4"
   ```

   - Sonderzeichen im Passwort URL-kodieren: `@` → `%40`, `:` → `%3A`, `/` → `%2F`, `#` → `%23`, `%` → `%25`.
   - Bei MySQL statt MariaDB: `serverVersion=8.0.36` (die angezeigte Version).
   - `deploy/.env.local` legt `make release` beim ersten Mal an; die Vorlage ist `deploy/env.local.dist`. Die Datei steht **nicht** in Git.

`make release` warnt, solange dort noch die Vorlage steht.

### 1.6 SFTP und phpMyAdmin

- **SFTP:** Server, Benutzername und Passwort stehen im Kundenlogin. Programme: **Cyberduck** oder **FileZilla**.
- **phpMyAdmin:** über den Kundenlogin bei der Datenbank erreichbar. Damit wird die Datenbank gefüllt (Abschnitt 3.2).

---

## 2. Release bauen und lokal prüfen

```bash
cd ~/Documents/tok-art-projekt
make up              # Entwicklungsumgebung starten (falls nicht schon läuft)
make check           # alle Tests und Prüfungen – müssen grün sein
make release         # baut release/
make release-test    # startet release/ unter Apache (mit der lokalen Datenbank) → http://localhost:8080
```

Unter http://localhost:8080 durchklicken: alle Tabs, ein Eintrag mit Video, Fotos filtern, Info-Fenster, Kontaktformular, eine falsche Adresse (eigene 404-Seite), `/admin` mit Anmeldung. Danach `make release-stop`.

`make release-test` verbindet sich mit der **lokalen** Datenbank (Einstellung in `compose.yaml`), nicht mit STRATO. Was dort angelegt oder hochgeladen wird, landet in der lokalen Datenbank bzw. in `release/public/media/` – Testdaten danach in der Verwaltung wieder löschen.

### Was `make release` macht

1. Kopiert nur, was auf dem Server gebraucht wird: `bin/`, `config/`, `data/`, `migrations/`, `public/` (ohne `build/`), `src/`, `templates/`, `.env`, `composer.*`, `symfony.lock`.
2. Installiert die PHP-Pakete **ohne** Entwicklungswerkzeuge (`vendor/`).
3. Erzeugt `.env.local.php` mit `APP_ENV=prod`, `APP_DEBUG=0`, `APP_SECRET` und `DATABASE_URL` aus `deploy/.env.local`.
   - Beim ersten Release wird `deploy/.env.local` mit einem zufälligen `APP_SECRET` angelegt und danach wiederverwendet.
   - `deploy/.env.local` wird **nie** hochgeladen – nur das daraus erzeugte `.env.local.php`.
4. Kopiert CSS und JavaScript der Verwaltung (EasyAdmin) nach `public/bundles/`.
5. Baut Vue, TypeScript und CSS nach `release/public/build/`.
6. Prüft den Service-Container im Produktionsmodus.

**Kein vorbereiteter Cache:** Symfony schreibt beim Aufbau des Caches die festen Pfade des Rechners hinein. Der Cache entsteht deshalb beim ersten Aufruf auf dem Server in `var/cache/`.

### Inhalt von `release/`

```
release/
  .env                  Standardwerte
  .env.local.php        Konfiguration für den Betrieb (APP_SECRET, DATABASE_URL)
  bin/  config/  data/  migrations/  src/  templates/  vendor/
  composer.json  composer.lock  symfony.lock
  public/               ← Zielverzeichnis der Domain
    .htaccess           Weiterleitung an index.php, HTTPS, Zwischenspeicher, Sicherheits-Header
    index.php
    build/              gebaute CSS- und JS-Dateien (Website)
    bundles/            CSS und JS der Verwaltung
    media/              Fotos und Videos (auch neue Uploads aus der Verwaltung)
    favicon.ico  robots.txt
  var/                  Cache – muss beschreibbar sein
```

---

## 3. Erstes Hochladen

### 3.1 Dateien

1. Per SFTP mit dem Webspace verbinden.
2. **Versteckte Dateien anzeigen** einschalten (Dateien mit Punkt am Anfang):
   - Cyberduck: Darstellung → Versteckte Dateien anzeigen
   - FileZilla: Server → Anzeige versteckter Dateien erzwingen
3. Den **Inhalt** von `release/` nach `/tok-art/` hochladen – nicht den Ordner `release` selbst.
4. Kontrollieren, dass diese Dateien angekommen sind:
   - `/tok-art/.env.local.php`
   - `/tok-art/public/.htaccess`
   - `/tok-art/var/` (Ordner, darf leer sein)

**Schreibrechte:** Die App schreibt in `var/` (Cache) und in `public/media/` (Uploads aus der Verwaltung). Bei STRATO darf PHP normalerweise in den eigenen Webspace schreiben. Falls Fehler auftreten: für beide Ordner im SFTP-Programm die Rechte `755` setzen (bei weiterhin Fehlern `775`).

### 3.2 Datenbank füllen (phpMyAdmin)

Auf dem eigenen Rechner:

```bash
make db-export
```

Das erzeugt `deploy/sql/tokart-JJJJ-MM-TT.sql`: alle Tabellen mit den Inhalten der lokalen Datenbank und dem Stand der Migrationen – **ohne** Konten und Nachrichten (deren Tabellen sind leer).

In phpMyAdmin:
1. Links die STRATO-Datenbank auswählen.
2. **Importieren** → die SQL-Datei wählen → **OK**.
3. Danach müssen links 12 Tabellen stehen, darunter `entry`, `media`, `admin_user`, `doctrine_migration_versions`.

Die Fotos und Videos zu diesen Inhalten liegen in `public/media/` und kommen mit dem Release auf den Server (3.1).

### 3.3 Konto für die Verwaltung anlegen

Auf dem eigenen Rechner – das Passwort wird verdeckt abgefragt, ausgegeben wird nur SQL mit dem **Hash**, nicht das Passwort:

```bash
docker compose exec app php bin/console app:admin:create deine@adresse.de --sql
```

Die ausgegebene Zeile (`INSERT INTO admin_user …`) in phpMyAdmin unter **SQL** einfügen und ausführen. Erneut ausgeführt, setzt sie das Passwort neu.

**Nie** das Entwicklungskonto (`admin@example.org`) auf den Server übertragen – `make db-export` lässt die Konten deshalb weg.

---

## 4. Nach dem Hochladen prüfen

| Aufruf | Erwartet |
|---|---|
| `http://deine-domain.de/` | Weiterleitung auf `https://…` |
| `https://deine-domain.de/` | Startseite „Über mich" |
| `…/trips/island` | Reiseseite mit Zeitleiste (zeigt: `.htaccess` wirkt, Datenbank verbunden) |
| `…/photos` | Filter wirken sofort ohne Neuladen (zeigt: Vue und `public/build/` passen) |
| `…/contact` | Nachricht schicken → Dank; danach in der Verwaltung unter „Nachrichten" sichtbar |
| `…/admin` | Weiterleitung zur Anmeldung; danach Liste der Einträge |
| Verwaltung → Foto hochladen | Foto erscheint; Datei liegt in `public/media/` (zeigt: `gd` und Schreibrechte passen) |
| `…/gibt-es-nicht` | eigene Seite „Nicht gefunden" |
| `…/.env.local.php`, `…/build/.vite/manifest.json` | **nicht** erreichbar (404 bzw. 403) |

---

## 5. Aktualisieren

Ab dem Online-Gang werden die Inhalte **auf dem Server** gepflegt (`/admin`, siehe VERWALTUNG.md, Abschnitt 6). Aktualisiert wird nur noch der **Code**.

### 5.1 Code

```bash
make check
make release
make release-test     # optional
```

Hochladen:

1. Alles aus `release/` hochladen **außer**:
   - `var/` – Cache auf dem Server
   - `public/media/` – auf dem Server liegen dort auch die Fotos aus der Verwaltung
2. Auf dem Server den Ordner **`/tok-art/var/cache/`** löschen. Sonst arbeitet Symfony mit dem alten Cache weiter und zeigt z. B. alte Vorlagen.
3. In `public/build/assets/` dürfen alte Dateien gelöscht werden; die neuen haben andere Namen.

### 5.2 Datenbank-Änderungen (Migrationen)

Nur nötig, wenn sich das Datenmodell geändert hat – dann gibt es eine neue Datei in `migrations/` (z. B. `Version20270115…php`). Die Inhalte bleiben erhalten; es ändern sich nur Tabellen.

**Weg A – phpMyAdmin (ohne SSH):**

```bash
make db-migration-sql VERSION=Version20270115120000
```

Das schreibt `deploy/sql/migration-Version20270115120000.sql`, **ohne** die Migration lokal auszuführen. Die Datei in phpMyAdmin unter **SQL** bzw. **Importieren** ausführen – sie trägt die Version auch in `doctrine_migration_versions` ein. Bei mehreren neuen Migrationen jede einzeln, in der Reihenfolge ihrer Nummern.

> Achtung, falls du den Doctrine-Befehl selbst aufrufst: `--write-sql` **ohne** `--dry-run` führt die Migration zusätzlich aus. Der Kurzbefehl setzt `--dry-run` deshalb immer.

**Weg B – SSH:** Hosting Basic bietet SSH. Im Ordner `/tok-art`:

```bash
php -v                                              # muss 8.2 oder neuer sein
php bin/console doctrine:migrations:migrate --no-interaction
```

Zeigt `php -v` eine ältere Version, bietet STRATO eventuell versionsbezogene Befehle an – siehe STRATO-Hilfe zur PHP-Kommandozeile.

Reihenfolge bei einem Update mit Migration: **erst** die Datenbank anpassen, **dann** den neuen Code hochladen und `var/cache/` löschen.

---

## 6. Kontaktnachrichten, Sicherungen

- **Nachrichten** stehen in der Verwaltung unter **Nachrichten**. Beantwortete Nachrichten dort löschen (Datenschutz: nur so lange aufbewahren wie nötig).
- **Datenbank:** STRATO sichert automatisch. Zusätzlich gelegentlich in phpMyAdmin **Exportieren** und die Datei nach **HiDrive** legen.
- **Fotos und Videos:** `/tok-art/public/media/` per SFTP herunterladen und nach HiDrive sichern – Uploads aus der Verwaltung gibt es nur auf dem Server.
- **Originalfotos** (mit Metadaten) gehören nur in die private Sicherung, nie auf die Website.

---

## 7. Fehlersuche

| Anzeichen | Ursache und Lösung |
|---|---|
| Startseite geht, Unterseiten liefern 404 | `public/.htaccess` fehlt – versteckte Dateien mit hochladen |
| Fehler 500 auf allen Seiten | `intl` oder `pdo_mysql` nicht aktiv (1.2), PHP-Version zu alt (1.1), `var/` nicht beschreibbar (3.1) oder `.env.local.php` fehlt |
| Fehler 500, im Protokoll „SQLSTATE[HY000] [2002]" oder „Access denied" | `DATABASE_URL` falsch (Host, Benutzer, Passwort, Sonderzeichen) – `deploy/.env.local` korrigieren, `make release`, nur `.env.local.php` neu hochladen, `var/cache/` löschen |
| Fehler 500, im Protokoll „Table … doesn't exist" | Datenbank nicht gefüllt (3.2) oder Migration fehlt (5.2) |
| Seite ohne Gestaltung, Filter laden die Seite neu | `public/build/` fehlt oder ist unvollständig – neu hochladen |
| Verwaltung ohne Gestaltung | `public/bundles/` fehlt – neu hochladen |
| Foto-Upload schlägt fehl | `gd` nicht aktiv (1.2), `public/media/` nicht beschreibbar (3.1) oder Datei größer als die PHP-Grenze `upload_max_filesize` |
| Anmeldung „Ungültige Zugangsdaten" | Konto fehlt oder Passwort falsch – SQL aus 3.3 erneut ausführen |
| Nach einem Update alte Inhalte oder Vorlagen | `var/cache/` auf dem Server löschen (5.1) |
| Code oder `.env.local.php` im Browser abrufbar | Zielverzeichnis der Domain zeigt nicht auf `public/` (1.3) – sofort korrigieren |
| Fehler 500 direkt nach dem Hochladen einer neuen `.htaccess` | Datei wurde gelesen, während sie noch übertragen wurde (so beim lokalen Test passiert). Seite neu laden; bleibt der Fehler und steht im Protokoll „Expected &lt;/IfModule&gt;", ist die Datei unvollständig → erneut hochladen |
| Fehler 500 nach eigener Änderung an `.htaccess` | eine Regel, die Apache nicht versteht. Beispiel aus der Einrichtung: `RewriteRule … - [R=404,L]` führte zu 500; richtig ist `[F]` (403). Änderung zurücknehmen und vorher mit `make release-test` prüfen |

PHP-Fehler stehen im Fehlerprotokoll des Webservers (im Kundenlogin unter den Protokollen bzw. Logfiles).

**Grundregel:** Jede Änderung an `public/.htaccess` zuerst lokal mit `make release-test` prüfen und den Test-Apache danach neu starten (`make release-stop`, `make release-test`).

---

## 8. Sicherheit

- **`deploy/.env.local`** enthält `APP_SECRET` und das **Datenbank-Passwort**. Nur lokal aufbewahren, nicht weitergeben, nicht hochladen; eine Kopie gehört in die eigene Sicherung (z. B. HiDrive).
- **Wozu `APP_SECRET` dient:** Symfony nutzt ihn für Signaturen, z. B. bei signierten Links oder einer späteren Funktion „angemeldet bleiben". Im jetzigen Projekt hängt davon nichts Sichtbares ab. Geht er verloren, einfach einen neuen erzeugen (Zeile `APP_SECRET` in `deploy/.env.local` durch einen neuen Wert ersetzen, z. B. aus `php -r 'echo bin2hex(random_bytes(16));'`, dann `make release`).
- **Konten:** nur über das SQL aus 3.3 anlegen; Passwort mindestens 12 Zeichen; nach 5 Fehlversuchen pro Minute wird die Anmeldung gebremst.
- **Nie** den ganzen Projektordner hochladen, nur den Inhalt von `release/`.
- **`/build/.vite/`** ist per `.htaccess` gesperrt (403); Symfony liest diese Dateien direkt.
- `deploy/sql/` (Datenbank-Exporte) steht nicht in Git; die Dateien nach dem Import löschen oder privat sichern.

---

## 9. Vor dem Online-Gang (Inhalte und Recht)

Technisch ist das Projekt bereit. Vor einer öffentlichen Seite fehlen noch:

- [ ] **Echte Inhalte** – in der Verwaltung anlegen (lokal vor dem ersten Export oder danach online); Beispielinhalte und Platzhalterbilder löschen
- [ ] **Fotos:** werden beim Upload automatisch von Metadaten befreit und verkleinert. **Videos** vorher ohne Ortsangaben exportieren (VERWALTUNG.md, Abschnitt 4)
- [ ] **Impressum** und **Datenschutzerklärung** als eigene Seiten, verlinkt in einer Fußzeile; die Datenschutzerklärung nennt das Kontaktformular, die Speicherung der Nachrichten in der Datenbank, das Sitzungs-Cookie (Kontaktseite, Verwaltung) und die Server-Logs von STRATO
- [ ] **Responsives Layout** für Handys
- [ ] **Suchmaschinen:** soll das Tagebuch gefunden werden? Sonst in `public/robots.txt` `Disallow: /` eintragen
