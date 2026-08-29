FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash \
    libzip \
    libzip-dev \
    linux-headers \
    nginx \
    supervisor \
    && docker-php-ext-install pdo_mysql sockets zip \
    && apk del libzip-dev \
    && rm -rf /var/cache/apk/* /tmp/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN echo "expose_php = Off" > /usr/local/etc/php/conf.d/security.ini && \
    echo "disable_functions = exec,shell_exec,system,passthru,show_source" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "php_admin_value[open_basedir] = /var/www:/tmp" >> /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www

# 1. Install Composer dependencies first (cached layer)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader \
    && rm -rf /root/.composer /tmp/*

# 2. Copy application source code
COPY app/ app/
COPY bin/ bin/
COPY config/ config/
COPY database/ database/
COPY public/ public/
COPY views/ views/
COPY docker/nginx.app.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# 3. Optimize autoload and permissions
RUN composer dump-autoload --optimize --no-dev \
    && rm -rf /root/.composer /tmp/* \
    && mkdir -p /run/nginx /var/log/supervisor /var/www/logs \
    && chown -R www-data:www-data /var/www

EXPOSE 80 8080
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
