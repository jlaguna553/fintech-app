FROM php:8.2-cli

# 1. Dependencias del sistema (incluimos libcurl4 para futuras comunicaciones entre APIs)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libcurl4-openssl-dev \
    && docker-php-ext-install pdo pdo_mysql zip curl

# 2. Composer desde la imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# 3. Gestión de dependencias (Caché de capas)
COPY composer.json ./
RUN composer install --no-interaction --no-scripts --no-autoloader

# 4. Código fuente y Autoload
COPY . .
RUN composer dump-autoload --optimize

CMD ["php", "-S", "0.0.0.0:8000"]
