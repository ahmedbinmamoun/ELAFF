# Targets: dev (source mounted), prod (self-contained app/queue/scheduler), web (nginx + public assets).

ARG PHP_VERSION=8.3
ARG NODE_VERSION=24

############################ base ############################
FROM php:${PHP_VERSION}-fpm-bookworm AS base

COPY --from=mlocati/php-extension-installer:2.9 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_mysql redis gd imagick intl zip bcmath opcache pcntl exif \
 && apt-get update \
 && apt-get install -y --no-install-recommends poppler-utils unzip \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2.9 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-elaf.ini

WORKDIR /var/www/html

############################ dev ############################
FROM base AS dev

ARG UID=1000
ARG GID=1000
RUN groupadd -g ${GID} elaf || true \
 && useradd -m -u ${UID} -g ${GID} -s /bin/bash elaf \
 && cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini \
 && sed -i 's/^user = .*/user = elaf/; s/^group = .*/group = elaf/' /usr/local/etc/php-fpm.d/www.conf
USER elaf

############################ composer deps (prod) ############################
FROM base AS vendor
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

############################ assets ############################
FROM node:${NODE_VERSION}-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
COPY resources ./resources
COPY src ./src
RUN npm run build

############################ prod ############################
FROM base AS prod

RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini \
 && echo "opcache.validate_timestamps=0" > /usr/local/etc/php/conf.d/zz-opcache-prod.ini

COPY --chown=www-data:www-data --from=vendor /var/www/html/vendor ./vendor
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
 && php artisan package:discover --ansi \
 && rm -rf tests docker/mysql node_modules .env \
 && chown -R www-data:www-data storage bootstrap/cache

COPY docker/php/entrypoint.prod.sh /usr/local/bin/elaf-entrypoint
RUN chmod +x /usr/local/bin/elaf-entrypoint
USER www-data
ENTRYPOINT ["elaf-entrypoint"]
CMD ["php-fpm"]

############################ web ############################
FROM nginx:1.27-alpine AS web
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=prod /var/www/html/public /var/www/html/public
