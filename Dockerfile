FROM php:7.3-apache

RUN apt-get update

RUN apt-get install -y libzip-dev libjpeg62-turbo-dev libpng-dev libfreetype6-dev \
  && docker-php-ext-install pdo_mysql mbstring zip exif pcntl \
  && a2enmod rewrite headers

WORKDIR /var/www/html/

COPY . .
