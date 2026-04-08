FROM php:8.5-cli-alpine

RUN apk add --no-cache autoconf gcc g++ make openssl-dev && \
    pecl install mongodb && \
    docker-php-ext-enable mongodb && \
    apk del autoconf gcc g++ make

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /app
