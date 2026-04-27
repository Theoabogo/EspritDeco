FROM php:8.2-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache mysql-dev git unzip \
    && docker-php-ext-install pdo pdo_mysql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer