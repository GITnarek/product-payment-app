FROM ubuntu:latest
LABEL authors="narek"
ENTRYPOINT ["top", "-b"]

FROM php:8.2-fpm

RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install zip

RUN docker-php-ext-install pdo_mysql

WORKDIR /var/www/product-payment-app

COPY . .

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install