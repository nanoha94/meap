# Production image for Railway (or similar): nginx + php-fpm in one container.
# Build from the `server` directory:
#   docker build -f docker/production/Dockerfile -t meap-api .
#
# Railway sets PORT; the entrypoint injects it into nginx listen.
# Leave Railway "Start Command" empty — a custom start command replaces ENTRYPOINT
# and skips docker-entrypoint.sh (nginx config + artisan cache never run).

FROM php:8.3-fpm-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        curl \
        git \
        libicu-dev \
        libonig-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libpq-dev \
        libzip-dev \
        nginx \
        supervisor \
        unzip \
        zip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        opcache \
        pdo_pgsql \
        pgsql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Debian 同梱のデフォルトサイト（Welcome to nginx!）を完全に除去
RUN rm -f /etc/nginx/sites-enabled/default \
    /etc/nginx/sites-available/default \
    && rm -f /etc/nginx/conf.d/*.conf \
    && mkdir -p /var/log/supervisor /etc/nginx/conf.d

WORKDIR /var/www/html

COPY docker/production/php/zz-production.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY docker/production/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/production/nginx/default.conf /etc/nginx/templates/default.conf
COPY docker/production/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh \
    && sed 's/__PORT__/8080/g' /etc/nginx/templates/default.conf > /etc/nginx/conf.d/default.conf

COPY . /var/www/html

RUN composer install --no-interaction --no-dev --optimize-autoloader \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R ug+rwx /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8080

# Railway: Start Command 空でも ENTRYPOINT が使われるよう CMD を明示
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD []
