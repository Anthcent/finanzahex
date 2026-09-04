# Base Image: PHP 8.2 with Apache (Debian Bookworm)
FROM php:8.2-apache-bookworm

# Set environment variables for production
ENV CI_ENVIRONMENT=production \
    PORT=8080 \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

# Install system dependencies and required PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    curl \
    ca-certificates \
    && docker-php-ext-install -j$(nproc) \
    intl \
    pdo \
    pdo_mysql \
    mysqli \
    pdo_pgsql \
    pgsql \
    zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache mod_rewrite for CodeIgniter 4 routing
RUN a2enmod rewrite

# Copy official Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application source code (filtered by .dockerignore)
COPY . /var/www/html

# Copy clean Apache VirtualHost and Ports configuration (supports 8080, 80, 3000)
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache/ports.conf /etc/apache2/ports.conf

# Install production dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Create and configure writable directories with proper permissions for www-data
RUN mkdir -p /var/www/html/writable/cache \
             /var/www/html/writable/logs \
             /var/www/html/writable/session \
             /var/www/html/writable/uploads \
             /var/www/html/writable/debugbar \
    && chown -R www-data:www-data /var/www/html/writable \
    && chmod -R 775 /var/www/html/writable

# Setup entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# Single EXPOSE directive for Dokploy / Hexper Ops
EXPOSE 8080

# Healthcheck checking 127.0.0.1 on /health (fast, does not depend on DB status)
HEALTHCHECK --interval=15s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -f http://127.0.0.1:8080/health || curl -f http://127.0.0.1:80/health || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
