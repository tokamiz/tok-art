# Entwicklungsumgebung: PHP 8.3 mit Composer und den Erweiterungen für Symfony
# intl: deutsche Daten und Sortierung · pdo_mysql: MariaDB · gd + exif: Fotos beim Upload drehen,
# verkleinern und von Metadaten befreien
FROM php:8.3-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libicu-dev libzip-dev \
        libjpeg62-turbo-dev libpng-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install intl zip opcache pdo_mysql gd exif \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
EXPOSE 8000

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public", "docker/router.php"]
