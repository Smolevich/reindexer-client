ARG PHP_VERSION=8.0
ARG COMPOSER_VERSION=2.0

FROM composer:${COMPOSER_VERSION}
FROM php:${PHP_VERSION}-fpm-alpine
RUN mkdir -p /usr/src/php/ext/xdebug && \
    curl -fsSL https://pecl.php.net/get/xdebug | tar xvz -C "/usr/src/php/ext/xdebug" --strip 1 && \
    docker-php-ext-install xdebug

RUN docker-php-ext-enable xdebug

ADD ./xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
COPY --from=composer /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html
