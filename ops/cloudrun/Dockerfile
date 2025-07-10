FROM composer:2.2 AS composer_base

# Pastikan ARG ini didefinisikan di awal Dockerfile Anda
ARG PHP_EXTS="pdo_mysql pcntl gd zip intl exif mysqli"
ARG PHP_PECL_EXTS="redis"
ARG PHP_ENABLE_EXTS="exif redis intl" # Perhatikan: intl sudah diaktifkan oleh docker-php-ext-install

RUN set -eux; \
    apk add --virtual build-dependencies --no-cache ${PHPIZE_DEPS} openssl ca-certificates libxml2-dev oniguruma-dev \
    && apk add --update --no-cache freetype-dev libjpeg-turbo-dev jpeg-dev libpng-dev libzip-dev icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) ${PHP_EXTS} \
    && pecl install ${PHP_PECL_EXTS} \
    && docker-php-ext-enable ${PHP_ENABLE_EXTS} \
    && apk del build-dependencies

COPY . /var/www

WORKDIR /var/www

RUN composer install --optimize-autoloader --no-interaction --no-progress --prefer-dist

FROM node:22-slim AS node_base

COPY --from=composer_base /var/www /var/www

# Set working directory
WORKDIR /var/www

# Install Node.js dependencies and build assets
RUN npm install && npm run build

FROM php:8.3.4-fpm-alpine

USER root

RUN apk add --no-cache postgresql-dev msmtp perl wget procps shadow libzip libpng libjpeg-turbo libwebp freetype icu

RUN apk add --no-cache --virtual build-essentials \
    icu-dev icu-libs zlib-dev g++ make automake autoconf libzip-dev \
    libpng-dev libwebp-dev libjpeg-turbo-dev freetype-dev && \
    docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp && \
    docker-php-ext-install gd && \
    docker-php-ext-install pgsql && \
    docker-php-ext-install pgsql pdo pdo_pgsql && \
    docker-php-ext-install intl && \
    docker-php-ext-install bcmath && \
    docker-php-ext-install opcache && \
    docker-php-ext-install exif && \
    docker-php-ext-install zip && \
    pecl install redis && \
    docker-php-ext-enable redis && \
    apk del build-essentials && rm -rf /usr/src/php*

RUN apk add --no-cache nginx wget

RUN mkdir -p /run/nginx

# Define a variável de ambiente LISTEN_PORT
ARG PORT=8000
ENV PORT=$PORT

COPY ops/cloudrun/nginx/nginx.conf /etc/nginx/nginx.conf

# Copy the application code and dependencies from the build stage
COPY --from=node_base --chown=www-data:www-data /var/www /var/www

CMD [ "sh", "/var/www/ops/cloudrun/nginx/startup.sh" ]
