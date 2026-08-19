# syntax=docker/dockerfile:1

FROM php:8.5-fpm AS base

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpq-dev \
        libicu-dev \
        libzip-dev \
        postgresql-client \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        bcmath \
        intl \
        pcntl \
        zip \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html

COPY . .

RUN composer install --no-interaction --prefer-dist --no-progress --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint

RUN sed -i 's/\r$//' /usr/local/bin/entrypoint \
    && chmod +x /usr/local/bin/entrypoint

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
