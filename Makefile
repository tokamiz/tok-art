# Kurzbefehle für die Arbeit mit Docker. Aufruf z. B.: make up
.PHONY: help up setup admin down logs sh test stan lint vue-test typecheck build check db-migrate db-export db-migration-sql release release-test release-stop

TODAY := $(shell date +%Y-%m-%d)

help: ## Diese Übersicht
	@grep -E '^[a-z-]+:.*## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*## "} {printf "  make %-13s %s\n", $$1, $$2}'

up: ## App, Datenbank und Vite starten → http://localhost:8000
	docker compose up -d --build
	@echo "Foto-Tagebuch läuft auf http://localhost:8000 (Vite auf http://localhost:5180, Verwaltung: /admin)"

setup: up ## Einmalig: Datenbank anlegen und Beispielinhalte aus data/ übernehmen
	@sleep 5
	docker compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction
	-docker compose exec -T app php bin/console app:import-json
	@echo "Jetzt ein Konto für die Verwaltung anlegen: make admin EMAIL=deine@adresse.de"

admin: ## Konto für /admin anlegen oder Passwort ändern: make admin EMAIL=…
	@test -n "$(EMAIL)" || (echo "Bitte so aufrufen: make admin EMAIL=deine@adresse.de" && exit 1)
	docker compose exec app php bin/console app:admin:create $(EMAIL)

down: ## Alles stoppen
	docker compose down

logs: ## Ausgaben von PHP, Datenbank und Vite ansehen (Ende mit Ctrl+C)
	docker compose logs -f

sh: ## Shell im PHP-Container öffnen (für composer, bin/console …)
	docker compose exec app bash

test: ## PHP-Tests (PHPUnit, baut die Testdatenbank jedes Mal neu auf)
	docker compose exec -T app php bin/phpunit

stan: ## PHP-Typen prüfen (PHPStan, Stufe 8)
	docker compose exec -T app php bin/console cache:warmup --quiet
	docker compose exec -T app vendor/bin/phpstan analyse --no-progress --memory-limit=1G

lint: ## Twig-Vorlagen, Service-Container und Datenbank-Mapping prüfen
	docker compose exec -T app php bin/console lint:twig templates
	docker compose exec -T app php bin/console lint:container
	docker compose exec -T app php bin/console doctrine:schema:validate

vue-test: ## Vue-Tests (Vitest)
	docker compose exec -T node npm test

typecheck: ## TypeScript und Vue prüfen (vue-tsc)
	docker compose exec -T node npm run typecheck

build: ## CSS und JavaScript für den Betrieb bauen → public/build/
	docker compose exec -T node npm run build

check: test stan lint vue-test typecheck ## Alles prüfen – vor jedem Commit

db-migrate: ## Neue Datenbank-Migrationen ausführen
	docker compose exec -T app php bin/console doctrine:migrations:migrate --no-interaction

db-export: ## Lokale Inhalte als SQL für phpMyAdmin (ohne Konten und Nachrichten) → deploy/sql/
	@mkdir -p deploy/sql
	docker compose exec -T db mariadb-dump -utokart -ptokart --skip-comments --no-tablespaces tokart --ignore-table=tokart.admin_user --ignore-table=tokart.message > deploy/sql/tokart-$(TODAY).sql
	docker compose exec -T db mariadb-dump -utokart -ptokart --skip-comments --no-tablespaces --no-data tokart admin_user message >> deploy/sql/tokart-$(TODAY).sql
	@echo "✓ deploy/sql/tokart-$(TODAY).sql – Anleitung: STRATO.md, Abschnitt 3"

db-migration-sql: ## SQL einer Migration für phpMyAdmin, ohne sie auszuführen: make db-migration-sql VERSION=Version2026…
	@test -n "$(VERSION)" || (echo "Bitte so aufrufen: make db-migration-sql VERSION=Version20261113220821 (Liste: migrations/)" && exit 1)
	@mkdir -p deploy/sql
	@# --dry-run ist zwingend: --write-sql allein würde die Migration auch lokal ausführen
	docker compose exec -T app php bin/console doctrine:migrations:execute 'DoctrineMigrations\$(VERSION)' --up --dry-run --write-sql=deploy/sql/migration-$(VERSION).sql --no-interaction
	@echo "✓ deploy/sql/migration-$(VERSION).sql – in phpMyAdmin ausführen (STRATO.md, Abschnitt 5)"

release: ## Upload-Ordner für STRATO bauen → release/
	docker compose exec -T app sh docker/release.sh
	docker compose exec -T node npx vite build --outDir release/public/build --emptyOutDir
	@echo "✓ release/ ist bereit. Testen mit: make release-test – Anleitung: STRATO.md"

release-test: ## release/ unter Apache testen (mit der lokalen Datenbank) → http://localhost:8080
	docker compose --profile release up -d --build strato-test
	@echo "Release läuft unter Apache auf http://localhost:8080 (stoppen: make release-stop)"

release-stop: ## Apache-Test stoppen
	docker compose --profile release stop strato-test
