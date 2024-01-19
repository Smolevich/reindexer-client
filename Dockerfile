ARG PHP_VERSION=8.1
ARG COMPOSER_VERSION=2.4

FROM composer:${COMPOSER_VERSION} as composer-image
FROM php:${PHP_VERSION}-fpm-alpine
RUN apk add --update --no-cache -t .php-build-deps \
    autoconf cmake automake gcc g++ make grpc libstdc++ zlib-dev linux-headers

ENV build_deps \
    dpkg \
    dpkg-dev \
    libmagic \
    file \
    re2c

# https://github.com/grpc/grpc/issues/25250
# Install grpc
RUN apk add --no-cache --virtual .build-deps $build_deps && \
    pecl install xdebug-3.1.5 && \
    CPPFLAGS="-Wno-maybe-uninitialized" pecl install grpc-1.50.0 && \
    docker-php-ext-enable xdebug grpc && \
    apk del -f .build-deps

ADD .docker/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini
COPY --from=composer-image /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/html
