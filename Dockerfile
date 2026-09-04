FROM php:8.2.33-apache-bookworm AS runtime

ENV CI_ENVIRONMENT=production \
    APACHE_DOCUMENT_ROOT=/var/www/html/public

RUN apt-get update && apt-get install -y --no-install-recommends \
    libicu-dev libpq-dev libzip-dev libonig-dev curl ca-certificates \
    && docker-php-ext-install -j$(nproc) intl mbstring mysqli pgsql pdo_pgsql zip \
    && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite

WORKDIR /var/www/html

FROM runtime AS dependencies
COPY --from=composer:2.8.12 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --optimize-autoloader --no-interaction --no-scripts

FROM runtime AS production
COPY --from=dependencies /var/www/html/vendor ./vendor
COPY app ./app
COPY public ./public
COPY spark ./spark
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY docker/apache/ports.conf /etc/apache2/ports.conf
COPY docker/php-production.ini /usr/local/etc/php/conf.d/production.ini
COPY docker-entrypoint.sh /usr/local/bin/fihex-entrypoint

RUN mkdir -p writable/cache writable/logs writable/session writable/uploads writable/debugbar \
    && chown -R www-data:www-data writable /var/run/apache2 /var/lock/apache2 \
    && sed -i 's/\r$//' /usr/local/bin/fihex-entrypoint \
    && chmod 755 /usr/local/bin/fihex-entrypoint

USER www-data
EXPOSE 8080
HEALTHCHECK --interval=15s --timeout=5s --start-period=90s --retries=3 \
    CMD curl --fail --silent --show-error --output /dev/null http://127.0.0.1:8080/ || exit 1

ENTRYPOINT ["/usr/local/bin/fihex-entrypoint"]
CMD ["apache2-foreground"]
