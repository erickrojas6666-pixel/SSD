FROM php:8.2-apache

# 1. Instalar dependencias del sistema y librerías necesarias
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip

# 2. Instalar extensiones de PHP requeridas por Laravel (incluyendo zip)
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# 3. Instalar Composer oficial
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 4. Configurar el directorio de trabajo
WORKDIR /var/www/html

# 5. Copiar los archivos del proyecto al contenedor
COPY . .

# 6. Instalar dependencias de Composer sin entornos de desarrollo
RUN composer install --no-dev --optimize-autoloader

# 7. Asignar permisos correctos a las carpetas de Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 8. Configurar Apache para que apunte a la carpeta /public de Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -s 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -s 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN a2enmod rewrite

EXPOSE 80