FROM richarvey/nginx-php-fpm:3.1.6

# Copy only Composer files for dependency installation
COPY composer.json composer.lock /var/www/html/

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy the rest of the application files
COPY . /var/www/html/

# Image config
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Laravel config
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr

# Allow composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER 1

# Create storage directory and symbolic link
RUN mkdir -p /var/www/html/storage/app/public && \
    php artisan storage:link

# Start the application using the start.sh script
CMD ["/start.sh"]