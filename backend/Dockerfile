# --- Stage 1: Frontend-Assets (htmx, Bootstrap, CSS) mit Vite bauen ---
FROM node:22-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
RUN npm run build
# Ergebnis: /app/public/build (manifest.json + gehashte JS/CSS-Bundles)

# --- Stage 2: PHP / Laravel ---
FROM php:8.4-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libpng-dev \
        libonig-dev \
        unzip \
        git \
    && docker-php-ext-install pdo_mysql zip mbstring opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /web

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

COPY . .

# Gebaute Vite-Assets aus der Asset-Stage in einen Stash legen. Der Entrypoint
# spiegelt sie nach /web/public/build (Bind-Mount), damit der Apache-Container
# sie ausliefern kann.
COPY --from=assets /app/public/build /build-dist

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
