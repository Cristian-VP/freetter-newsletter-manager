# PHP 8.4 - Compatible con Laravel 12 + Inertia.js 2.0
FROM php:8.4-fpm-alpine

# Crear usuario (idéntico al CLI)
RUN addgroup -g 1000 -S user && adduser -u 1000 -D user -G user

# Dependencias del sistema para compilar extensiones
RUN apk add --no-cache postgresql-dev icu-dev libzip-dev linux-headers

# Extensiones de PHP (Idénticas al CLI)
RUN docker-php-ext-install pdo pdo_pgsql intl zip bcmath pcntl

# Instalar Redis Driver (Versión 6.1.0 compatible con PHP 8.4)
RUN mkdir -p /usr/src/php/ext/redis; \
    curl -fsSL --ipv4 https://github.com/phpredis/phpredis/archive/6.1.0.tar.gz | tar xvz -C "/usr/src/php/ext/redis" --strip 1; \
    docker-php-ext-install redis

# Crear directorios con permisos correctos
RUN mkdir -p /var/log/php-fpm && \
    chown -R user:user /var/log/php-fpm /var/run

# Configuración de FPM
COPY fpm-pool.conf /usr/local/etc/php-fpm.d/zz-app.conf

USER user