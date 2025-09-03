ARG PHP_VERSION=8.4
FROM php:${PHP_VERSION}-fpm-alpine

RUN apk add --no-cache \
    bash \
    autoconf \
    make \
    gcc \
    g++ \
    musl-dev \
    pkgconf \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    gmp-dev \
    imap-dev \
    krb5-dev \
    postgresql-dev \
    oniguruma-dev \
    openssl-dev \
    zlib-dev \
    libxml2-dev \
    git \
    openssh \
    curl \
    file \
    curl-dev

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions apcu bcmath redis intl curl \
    mbstring iconv json openssl imap gmp calendar fileinfo pdo_pgsql  \
    gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# https://getcomposer.org/doc/03-cli.md#composer-allow-superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

