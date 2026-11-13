#!/bin/sh
#
# Stellt im Ordner release/ genau das zusammen, was auf den Server (STRATO) hochgeladen wird.
# Läuft im PHP-Container (make release). Vue, TypeScript und CSS baut danach der Node-Container
# direkt nach release/public/build/.
#
# Nicht im Release: Tests, Quellen der Assets, Docker, Dokumentation, Entwicklungspakete,
# und kein vorbereiteter Cache – der enthält feste Pfade dieses Rechners und entsteht deshalb
# beim ersten Aufruf auf dem Server.

set -eu
cd /app

RELEASE=release

echo "→ Ordner $RELEASE/ neu anlegen"
rm -rf "$RELEASE"
mkdir -p "$RELEASE/var"

echo "→ Dateien kopieren"
cp -R bin config data migrations src templates "$RELEASE/"
cp .env composer.json composer.lock symfony.lock "$RELEASE/"
mkdir -p "$RELEASE/public"
for item in public/* public/.htaccess; do
    case "$item" in
        public/build|public/bundles) continue ;;
    esac
    cp -R "$item" "$RELEASE/public/"
done

echo "→ Konfiguration für den Betrieb"
if [ ! -f deploy/.env.local ]; then
    secret=$(php -r 'echo bin2hex(random_bytes(16));')
    sed "s/__WIRD_AUTOMATISCH_ERZEUGT__/$secret/" deploy/env.local.dist > deploy/.env.local
    echo "  deploy/.env.local mit neuem APP_SECRET angelegt (wird bei weiteren Releases wiederverwendet)"
fi
if ! grep -q '^DATABASE_URL=' deploy/.env.local; then
    grep -B3 '^DATABASE_URL=' deploy/env.local.dist >> deploy/.env.local
    echo "  DATABASE_URL-Vorlage an deploy/.env.local angehängt"
fi
if grep -q '^DATABASE_URL=.*BENUTZER:PASSWORT@HOST' deploy/.env.local; then
    echo "  ⚠ In deploy/.env.local stehen noch keine STRATO-Datenbankzugangsdaten (DATABASE_URL)."
    echo "    Der Release funktioniert erst auf dem Server, wenn sie eingetragen sind (STRATO.md, Abschnitt 1.5)."
fi
cp deploy/.env.local "$RELEASE/.env.local"

echo "→ PHP-Pakete ohne Entwicklungswerkzeuge"
cd "$RELEASE"
APP_ENV=prod composer install --no-dev --optimize-autoloader --classmap-authoritative \
    --no-scripts --no-interaction --no-progress --quiet
APP_ENV=prod composer dump-env prod --quiet
rm .env.local

echo "→ CSS und JavaScript der Verwaltung (EasyAdmin) nach public/bundles/"
APP_ENV=prod php bin/console assets:install public --quiet

echo "→ Prüfen"
APP_ENV=prod php bin/console lint:container --quiet
APP_ENV=prod php bin/console about | grep -E "Environment|Debug"
rm -rf var/cache var/log

echo "✓ PHP-Teil fertig"
