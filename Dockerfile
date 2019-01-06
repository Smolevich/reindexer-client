ARG PHP_VERSION=7.2
ARG COMPOSER_VERSION=1.8

FROM composer:${COMPOSER_VERSION}
FROM php:${PHP_VERSION}-alpine

COPY --from=composer /usr/bin/composer /usr/local/bin/composer

WORKDIR /app