FROM php:8.4-apache

# PHP-Extensions installieren
RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libpng-dev \
        libonig-dev \
        unzip \
        git \
    && docker-php-ext-install pdo_mysql zip mbstring opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Composer installieren
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache: DocumentRoot auf /public setzen + mod_rewrite aktivieren
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite

WORKDIR /var/www/html

# Abhängigkeiten installieren (zuerst nur composer.json für besseres Caching)
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Code kopieren
COPY . .

# Storage & Cache: Rechte setzen
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Entrypoint
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
