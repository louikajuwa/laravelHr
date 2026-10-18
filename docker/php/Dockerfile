FROM php:8.4-fpm

ARG UID=1000
ARG GID=1000

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

# Unprivileged runtime user. php-fpm (master and workers), `docker exec`,
# artisan and composer all run as this user; ownership of the writable
# bind-mounted dirs is fixed by the one-shot `app-init` service.
RUN groupadd -g "${GID}" app \
    && useradd -u "${UID}" -g app -m -s /bin/bash app \
    && sed -i -e '/^user = /d' -e '/^group = /d' /usr/local/etc/php-fpm.d/www.conf

COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /var/www/html
USER app
EXPOSE 9000
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
