FROM php:8.3-fpm-bookworm

ENV APP_ENV=production \
    COMPOSER_ALLOW_SUPERUSER=1 \
    PORT=10000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        gettext-base \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libpq-dev \
        libzip-dev \
        nginx \
        supervisor \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_pgsql \
        zip \
    && rm -rf /var/lib/apt/lists/* /var/www/html/* /etc/nginx/sites-enabled/default

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --optimize-autoloader \
    && mkdir -p \
        storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod +x docker/start.sh

COPY docker/nginx.conf.template /etc/nginx/templates/menuos.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/menuos.conf

EXPOSE 10000

ENTRYPOINT ["/var/www/html/docker/start.sh"]
