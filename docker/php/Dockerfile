FROM php:8.4-fpm

RUN apt-get update && apt-get install -y --no-install-recommends \
        git curl unzip zip \
        libpng-dev libjpeg-dev libfreetype6-dev \
        libonig-dev libxml2-dev libzip-dev libicu-dev \
        default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql mbstring bcmath gd zip exif pcntl intl opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /var/www/html
EXPOSE 9000
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
