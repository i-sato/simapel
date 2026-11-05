# syntax=docker/dockerfile:1.7
#
# Image aplikasi SIMAPEL (PHP-FPM).
#   target dev  : pengembangan & pengujian (Composer + PCOV), kode di-mount dari host
#   target prod : image rilis, non-root, dependensi tanpa dev, OPcache tanpa revalidasi
#
# Versi dipin agar build dapat direproduksi; pembaruan diusulkan Dependabot.

ARG PHP_VERSION=8.5
ARG DEBIAN_RELEASE=trixie

FROM composer:2.11.1 AS composer
FROM mlocati/php-extension-installer:2.13.2 AS php-ext

# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm-${DEBIAN_RELEASE} AS base

ENV TZ=Asia/Jakarta

COPY --from=php-ext /usr/bin/install-php-extensions /usr/local/bin/

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends tzdata; \
    install-php-extensions pdo_mysql intl zip bcmath opcache; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/*

COPY docker/php/conf.d/app.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html

# ---------------------------------------------------------------------------
FROM base AS dev

# Samakan UID/GID www-data dengan pengguna host agar file hasil generate
# (cache, log, vendor) tidak dimiliki root pada bind mount Linux/WSL2.
ARG HOST_UID=1000
ARG HOST_GID=1000

RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends git unzip; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/*; \
    install-php-extensions pcov; \
    groupmod -o -g "${HOST_GID}" www-data; \
    usermod -o -u "${HOST_UID}" -g www-data www-data; \
    mkdir -p /home/www-data/.composer; \
    usermod -d /home/www-data www-data; \
    chown -R www-data:www-data /home/www-data /var/www/html

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY docker/php/conf.d/dev.ini /usr/local/etc/php/conf.d/zz-dev.ini

ENV COMPOSER_HOME=/home/www-data/.composer

USER www-data

# ---------------------------------------------------------------------------
FROM base AS vendor

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN set -eux; \
    composer dump-autoload --no-dev --optimize --classmap-authoritative; \
    rm -f /usr/local/bin/composer

# ---------------------------------------------------------------------------
FROM base AS prod

COPY docker/php/conf.d/prod.ini /usr/local/etc/php/conf.d/zz-prod.ini
COPY --from=vendor --chown=www-data:www-data /var/www/html /var/www/html

RUN set -eux; \
    rm -rf /var/www/html/tests /var/www/html/docker; \
    mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache; \
    chown -R www-data:www-data storage bootstrap/cache

USER www-data

HEALTHCHECK --interval=30s --timeout=5s --retries=3 CMD php-fpm -t || exit 1
