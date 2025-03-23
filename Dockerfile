FROM richarvey/nginx-php-fpm:3.1.6

# Copy application files
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

# Create storage directory and manually create the symbolic link
RUN mkdir -p /var/www/html/storage/app/public && \
    ln -s /var/www/html/storage/app/public /var/www/html/public/storage

# Start the application using the start.sh script
CMD ["/start.sh"]