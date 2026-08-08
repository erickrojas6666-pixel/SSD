FROM php:8.4-apache

# 1. Instalar dependencias del sistema esenciales
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip

# 2. Instalar extensiones de PHP requeridas
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# 3. Instalar Composer oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . .

# 4. Instalar dependencias de Composer para producción
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 5. Configurar permisos y Apache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -s 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -s 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN a2enmod rewrite

EXPOSE 80