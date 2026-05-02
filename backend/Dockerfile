FROM php:8.3-fpm-alpine

# Dépendances système
RUN apk add --no-cache \
    nginx \
    supervisor \
    postgresql-dev \
    libxml2-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    curl \
    unzip

# Extensions PHP
RUN docker-php-ext-install \
    pdo_pgsql \
    mbstring \
    xml \
    zip \
    bcmath \
    intl \
    opcache

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Installer les dépendances PHP en premier (cache Docker)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-autoloader --no-scripts --no-interaction

# Copier le reste de l'application
COPY . .

# Finaliser l'autoloader
RUN composer dump-autoload --optimize --no-dev

# Permissions sur les répertoires Laravel
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Configs Docker
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
