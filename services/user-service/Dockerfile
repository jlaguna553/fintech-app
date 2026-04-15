FROM php:8.2-cli

# 1. Instalamos dependencias del sistema necesarias para Composer y PHP
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql zip

# 2. Copiamos el binario de Composer desde la imagen oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 3. Establecemos el directorio de trabajo
WORKDIR /app

# 4. Copiamos primero los archivos de dependencias
# Esto ayuda a aprovechar la caché de las capas de Docker
COPY composer.json ./

# 5. Instalamos las dependencias de PHP
# --no-interaction evita que el proceso se detenga pidiendo datos
RUN composer install --no-interaction --no-scripts --no-autoloader

# 6. Copiamos el resto del código fuente
COPY . .

# 7. Generamos el autoloader optimizado
RUN composer dump-autoload --optimize

# 8. Comando para iniciar el servidor de desarrollo
CMD ["php", "-S", "0.0.0.0:8000"]
