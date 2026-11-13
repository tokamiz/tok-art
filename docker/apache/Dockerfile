# Testumgebung für den Release-Ordner: Apache mit PHP wie bei klassischem Webhosting (STRATO).
# Nur für `make release-test` – prüft .htaccess, Produktionsmodus, Datenbank und Schreibrechte.
FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libjpeg62-turbo-dev libpng-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install intl opcache pdo_mysql gd exif \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite headers

# Webverzeichnis = public/ des Release – so muss es auch bei STRATO eingestellt sein.
ENV APACHE_DOCUMENT_ROOT=/var/www/app/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!AllowOverride None!AllowOverride All!g' /etc/apache2/apache2.conf
