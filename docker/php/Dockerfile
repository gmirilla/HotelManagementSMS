# syntax=docker/dockerfile:1

# ---- Base PHP-FPM image with required extensions ----
FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
        bash \
        git \
        icu-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        freetype-dev \
        oniguruma-dev \
        libxml2-dev \
        exif \
        supervisor \
        mysql-client \
    && docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        intl \
        zip \
        gd \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/cache/apk/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# ---- Dependencies layer (cached separately from source for faster rebuilds) ----
FROM base AS deps
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

# ---- Application image ----
FROM base AS app
COPY --from=deps /var/www/html/vendor ./vendor
COPY . .
RUN composer dump-autoload --optimize --no-dev \
    && php artisan storage:link || true \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-hms.ini
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/99-opcache.ini

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
