FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
    nginx \
    nodejs \
    npm \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libzip-dev \
    oniguruma-dev \
    postgresql-dev \
    sqlite-dev \
    icu-dev \
    libsodium-dev \
    libxml2-dev \
    openssl-dev \
    supervisor \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pdo_sqlite \
        mbstring \
        zip \
        gd \
        opcache \
        bcmath \
        intl \
        sodium \
        pcntl \
        xml \
        dom \
        simplexml \
        fileinfo

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer update --no-dev --optimize-autoloader --no-scripts --no-interaction --ignore-platform-reqs

COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN npm run build \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

EXPOSE 8080

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
