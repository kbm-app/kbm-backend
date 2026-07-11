FROM php:8.2-fpm

RUN apt update && apt install -y \
    git unzip libzip-dev libpng-dev libonig-dev libxml2-dev libpq-dev libicu-dev zip curl \
    && docker-php-ext-install pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip intl

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

EXPOSE 9000
CMD ["php-fpm"]
