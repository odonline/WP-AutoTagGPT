FROM php:7.4-apache

# Set the working directory to /var/www/html
WORKDIR /var/www/html

# Copy the WordPress files to the working directory
COPY wordpress/ .

# Copy the my-plugin files to the WordPress plugins directory
COPY src/ /var/www/html/wp-content/plugins/AutoTagWP/

# Copy the custom php.ini file to the container
COPY php.ini /usr/local/etc/php/

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install the mysqli PHP extension
RUN docker-php-ext-install mysqli pdo_mysql

# Install the zip PHP extension
RUN apt-get update && apt-get install -y \
    libzip-dev \
    && docker-php-ext-install zip

# Set the file permissions for the WordPress files and plugins directory
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chown -R www-data:www-data /var/www/html/wp-content/plugins/AutoTagWP \
    && chmod -R 755 /var/www/html/wp-content/plugins/AutoTagWP

# Set the Apache document root to /var/www/html
ENV APACHE_DOCUMENT_ROOT /var/www/html

# Update the Apache virtual host configuration to use the new document root
RUN sed -i -e "s#/var/www/html#$APACHE_DOCUMENT_ROOT#" /etc/apache2/sites-available/000-default.conf \
    && sed -i -e "s#/var/www#$APACHE_DOCUMENT_ROOT#" /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Start Apache
CMD ["apache2-foreground"]
