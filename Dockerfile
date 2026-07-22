FROM php:8.3-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    bash \
    unzip \
    libzip-dev \
    linux-headers \
    && docker-php-ext-install pdo_mysql sockets zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# PHP Hardening — global security settings (no open_basedir here, set per FPM pool below)
RUN echo "expose_php = Off" > /usr/local/etc/php/conf.d/security.ini && \
    echo "disable_functions = exec,shell_exec,system,passthru,show_source" >> /usr/local/etc/php/conf.d/security.ini

# Set open_basedir only for FPM worker processes (not CLI — so Composer still works)
RUN echo "php_admin_value[open_basedir] = /var/www:/tmp" >> /usr/local/etc/php-fpm.d/www.conf

# Set working directory
WORKDIR /var/www

# Copy project files
COPY . .

# Run Composer installation (runs as CLI, open_basedir is NOT active here)
RUN composer install --no-interaction --optimize-autoloader --prefer-dist

# Set permissions to non-root user
RUN chown -R www-data:www-data /var/www

# Switch container execution context to non-root
USER www-data

EXPOSE 9000
CMD ["php-fpm"]
