FROM php:8.4-cli

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev zip librabbitmq-dev \
    && docker-php-ext-install pdo pdo_pgsql zip \
    && pecl install amqp \
    && docker-php-ext-enable amqp

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader

COPY . .
RUN composer dump-autoload --optimize

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
