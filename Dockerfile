FROM serversideup/php:8.4-frankenphp

USER root

# Install ekstensi yang dibutuhkan aplikasi (DB + Redis + utility)
RUN install-php-extensions intl gd zip pdo_pgsql pgsql redis

WORKDIR /var/www/html

# Copy source code ke container
COPY . .

# Set permissions (penting biar FrankenPHP bisa baca file)
RUN chown -R www-data:www-data /var/www/html

USER www-data
