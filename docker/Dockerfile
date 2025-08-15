# docker/Dockerfile
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql zip \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# In Dockerfile
ENV COMPOSER_ALLOW_SUPERUSER=1

# Configure PHP
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
RUN a2enmod rewrite


# Set working directory to parent of vendor
WORKDIR /var/www

# Copy composer files separately
COPY composer.* ./

# Install dependencies (without autoloader optimization)
RUN composer install --no-autoloader --no-scripts

# Copy application code
COPY src/ ./src

# Now generate autoloader
RUN composer dump-autoload -o

# Dockerfile
# Point Apache to public directory
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Update virtual host
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/sites-available/*.conf
RUN sed -ri -e "s!/var/www/!${APACHE_DOCUMENT_ROOT}!g" \
    /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf