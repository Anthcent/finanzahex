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

# Configure Apache DocumentRoot to CodeIgniter's public folder
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Configure Apache to listen on 0.0.0.0:8080
RUN sed -ri -e 's/80/8080/g' /etc/apache2/ports.conf /etc/apache2/sites-available/*.conf

# Enable Apache mod_rewrite for CodeIgniter 4 routing
RUN a2enmod rewrite

# Allow .htaccess overrides in DocumentRoot
RUN echo '<Directory /var/www/html/public>\n\
    Options -Indexes +FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/codeigniter.conf \
    && a2enconf codeigniter

# Copy official Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application source code (filtered by .dockerignore)
COPY . /var/www/html

# Install production dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Configure writable directory permissions for www-data
RUN chown -R www-data:www-data /var/www/html/writable \
    && chmod -R 775 /var/www/html/writable

# Setup entrypoint script
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/docker-entrypoint.sh \
    && chmod +x /usr/local/bin/docker-entrypoint.sh

# Single EXPOSE directive for Dokploy / Hexper Ops
EXPOSE 8080

# Healthcheck checking 127.0.0.1:8080
HEALTHCHECK --interval=30s --timeout=5s --start-period=15s --retries=3 \
    CMD curl -f http://127.0.0.1:8080/ || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
