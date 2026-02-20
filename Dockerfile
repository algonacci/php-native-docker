FROM serversideup/php:8.4-frankenphp

USER root

# Install ekstensi yang lu butuhin (tambahin pgsql & pdo_pgsql)
RUN install-php-extensions intl gd zip pdo_pgsql pgsql

WORKDIR /var/www/html

# Copy source code ke container
COPY . .

# Set permissions (penting biar FrankenPHP bisa baca file)
RUN chown -R www-data:www-data /var/www/html

USER www-data
