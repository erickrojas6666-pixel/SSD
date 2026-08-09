#!/bin/sh

# Limpiar y cachear configuraciones para producción
php artisan config:cache
php artisan route:cache

# Ejecutar las migraciones automáticamente en la base de datos remota
php artisan migrate --force --seed

# Iniciar el servidor Apache en primer plano
exec apache2-foreground