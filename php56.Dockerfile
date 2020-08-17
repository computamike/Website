FROM php:5.6-apache
#79RUN apt-get update && apt-get install -y php5-mysql
RUN docker-php-ext-install pdo pdo_mysql  
# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
# Enable mod_rewrite
RUN a2enmod rewrite