FROM php:8-apache

# Install OS dependencies required
RUN apt-get update && apt-get upgrade -y && apt-get install git unzip zlib1g-dev libpng-dev -y

# Adapt apache config
RUN a2enmod rewrite \
    && echo "ServerName 127.0.0.1" >> /etc/apache2/apache2.conf

# Install PHP ext-gd
RUN docker-php-ext-install gd

WORKDIR /var/www/html
COPY . .

RUN chmod +x /var/www/html/docker-utils/install-composer && \
    /var/www/html/docker-utils/install-composer && \
    ./composer.phar update && \
    chmod +x /var/www/html/docker-utils/init-server

# Run the server
CMD ["/var/www/html/docker-utils/init-server"]