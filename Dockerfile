ARG PHP_VERSION=8.1
ARG COMPOSER_VERSION=2.4

FROM composer:${COMPOSER_VERSION} as composer-image
FROM php:${PHP_VERSION}-fpm-alpine
RUN apk add --update --no-cache -t .php-build-deps \
    autoconf cmake automake gcc g++ make

RUN pecl install xdebug-3.1.5 && \
    docker-php-ext-enable xdebug

ADD .docker/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
COPY --from=composer-image /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html
