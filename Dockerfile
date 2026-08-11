FROM php:8.2-apache

# Install dependencies and extensions needed for both MySQL and PostgreSQL (pdo_pgsql)
RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install mysqli pdo pdo_mysql pdo_pgsql pgsql

# Copy project files into the Apache public directory
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80