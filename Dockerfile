FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev zip \
    && docker-php-ext-install pdo pdo_pgsql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY . .

CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
