FROM php:8.2-apache

# ── Módulos Apache ──────────────────────────────────────────────────────────
RUN a2enmod rewrite headers

# ── Extensiones PHP ─────────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y --no-install-recommends \
        libcurl4-openssl-dev \
        libonig-dev \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install pdo_mysql mbstring curl

# ── PHP: desactivar output buffering (necesario para SSE) ───────────────────
RUN { \
        echo "output_buffering = Off"; \
        echo "implicit_flush = On"; \
        echo "zlib.output_compression = Off"; \
    } > /usr/local/etc/php/conf.d/devioz-sse.ini

# ── Apache: DocumentRoot → public/ + AllowOverride All ─────────────────────
RUN sed -i \
        's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' \
        /etc/apache2/sites-available/000-default.conf \
    && printf '\n<Directory /var/www/html/public>\n  AllowOverride All\n  Require all granted\n</Directory>\n' \
        >> /etc/apache2/sites-available/000-default.conf

# ── Composer ────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# ── Código fuente ────────────────────────────────────────────────────────────
WORKDIR /var/www/html
COPY . .

# ── Dependencias PHP ─────────────────────────────────────────────────────────
RUN composer install --no-dev --optimize-autoloader --no-interaction

# ── Permisos ─────────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www/html \
    && find /var/www/html -type d -exec chmod 755 {} \; \
    && find /var/www/html -type f -exec chmod 644 {} \;
