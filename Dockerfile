FROM php:8.4-cli-alpine

RUN apk add --no-cache \
    postgresql-dev libzip-dev oniguruma-dev \
    libpng-dev libjpeg-turbo-dev libwebp-dev \
    icu-dev libsodium-dev libxml2-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install \
        pdo pdo_pgsql mbstring zip gd opcache \
        bcmath intl sodium pcntl xml dom simplexml fileinfo

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

RUN for i in 1 2 3 4 5; do \
      COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs && break; \
      echo "Attempt $i failed, retrying in 10s..."; sleep 10; \
    done

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 8080

CMD ["sh", "-c", "php artisan config:cache && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=8080"]
