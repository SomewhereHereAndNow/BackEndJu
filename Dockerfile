FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    curl \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libssl-dev \
    pkg-config \
    openssl

# Install PHP extensions required for Laravel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd

# Install MongoDB extension
RUN pecl install mongodb && docker-php-ext-enable mongodb

# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy application files into the container
COPY . .

# Install Laravel dependencies using Composer
RUN composer install --no-dev --optimize-autoloader

# Set permissions for Laravel storage and cache
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www

# Expose port 8080
EXPOSE 8080

# Start PHP's built-in server
CMD ["php", "-S", "0.0.0.0:8080", "-t", "public"]
