FROM php:8.2-fpm
# Install system dependencies, curl, unzip, and libraries required by Composer
RUN apt-get update && apt-get install -y curl unzip libpng-dev libjpeg-dev libfreetype6-dev

# Install PHP extensions required for Laravel (e.g., GD for image processing, etc.)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd

#MongoDb Installing 
RUN pecl install mongodb \
    && docker-php-ext-enable mongodb
# Install Composer globally
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Set the working directory inside the container
WORKDIR /var/www/html

# Copy application files into the container
COPY . .

# Install Laravel dependencies using Composer
RUN /usr/local/bin/composer install --no-dev --optimize-autoloader

# Expose the port to be used by Laravel
EXPOSE 8080

# Use artisan to start the Laravel application on the correct port
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=$PORT"]

RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www

