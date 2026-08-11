FROM php:8.2-apache

# Install extensions needed for MySQL connection (mysqli)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy project files into the Apache public directory
COPY . /var/www/html/

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80