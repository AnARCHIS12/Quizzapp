FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash \
    libzip-dev \
    linux-headers \
    nginx \
    supervisor \
    unzip \
    && docker-php-ext-install pdo_mysql sockets zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN echo "expose_php = Off" > /usr/local/etc/php/conf.d/security.ini && \
    echo "disable_functions = exec,shell_exec,system,passthru,show_source" >> /usr/local/etc/php/conf.d/security.ini && \
    echo "php_admin_value[open_basedir] = /var/www:/tmp" >> /usr/local/etc/php-fpm.d/www.conf

WORKDIR /var/www

COPY . .
COPY docker/nginx.app.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf

RUN composer install --no-interaction --optimize-autoloader --prefer-dist && \
    mkdir -p /run/nginx /var/log/supervisor /var/www/logs && \
    chown -R www-data:www-data /var/www

EXPOSE 80 8080
CMD ["supervisord", "-c", "/etc/supervisord.conf"]
