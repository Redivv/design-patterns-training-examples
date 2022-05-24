FROM php:8.1-apache
WORKDIR /var/www/html

RUN pecl install xdebug && docker-php-ext-enable xdebug
RUN rm -rf /var/lib/apt/lists/*

COPY . .