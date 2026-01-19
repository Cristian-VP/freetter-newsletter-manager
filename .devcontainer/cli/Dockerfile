# PHP 8.4 - Compatible con Laravel 12 + Inertia.js 2.0 + React 19
FROM php:8.4-cli-alpine

# Crear usuario
RUN addgroup -g 1000 -S user && adduser -u 1000 -D user -G user

# Dependencias del sistema (linux-headers es vital para compilar en PHP 8.4)
RUN apk add --no-cache bash curl git vim \
    postgresql-dev icu-dev libzip-dev linux-headers \
    libpng-dev libjpeg-turbo-dev freetype-dev \
    libxml2-dev oniguruma-dev \
    nodejs npm

# Instalar Composer (evita instalar PHP de Alpine y conflictos)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

## Purga de PHP de Alpine y configuración de entorno para usar PHP del contenedor
# Elimina cualquier instalación previa de php84 y sus INI que puedan provocar conflictos
RUN apk del --no-cache php84 php84-common php84-cli php84-opcache php84-zip php84-bcmath php84-gd php84-intl php84-pcntl php84-pdo_pgsql php84-redis php84-sodium 2>/dev/null || true && \
     rm -rf /etc/php84 /lib/php/extensions /usr/bin/php84

# Asegura que PHP use los INI del contenedor y priorice /usr/local/bin en PATH
ENV PHP_INI_SCAN_DIR=/usr/local/etc/php/conf.d
ENV PATH="/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin"

# Enlace para que 'php' invoque a la versión 8.4
RUN rm -f /usr/bin/php83 /usr/bin/php 2>/dev/null || true && ln -s /usr/local/bin/php /usr/bin/php

# Extensiones de PHP requeridas para Laravel 12 + Inertia.
# Extraemos las fuentes de PHP antes de compilar para que existan los archivos de las extensiones.
# Nota: tokenizer, session, xml, dom, fileinfo, simplexml, xmlwriter, mbstring y opcache 
# ya vienen habilitadas por defecto en PHP 8.4
RUN docker-php-source extract && \
    docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install -j$(nproc) \
    pdo \
    pdo_pgsql \
    intl \
    zip \
    bcmath \
    pcntl \
    gd && \
    docker-php-source delete

# Instalar Redis Driver (Versión 6.1.0 compatible con PHP 8.4)
RUN mkdir -p /usr/src/php/ext/redis; \
    curl -fsSL --ipv4 https://github.com/phpredis/phpredis/archive/6.1.0.tar.gz | tar xvz -C "/usr/src/php/ext/redis" --strip 1; \
    docker-php-ext-install redis

# NUEVO: Habilitar explícitamente las extensiones para evitar warnings de carga dinámica
RUN docker-php-ext-enable opcache pdo pdo_pgsql intl zip bcmath pcntl gd redis

USER user

# Mantener vivo el contenedor
CMD ["sleep", "infinity"]