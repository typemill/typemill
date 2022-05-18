FROM php:8-apache

# Install OS dependencies required
RUN apt-get update && apt-get upgrade -y && apt-get install git unzip zlib1g-dev libpng-dev -y

# Adapt apache config
RUN a2enmod rewrite \
    && echo "ServerName 127.0.0.1" >> /etc/apache2/apache2.conf

# Install PHP ext-gd
RUN docker-php-ext-install gd

COPY docker-utils /usr/local/bin

#WORKDIR /src
#
#COPY system ./system
#COPY .htaccess \
#     composer* \
#     index.php \
#     /src/
#COPY cache ./cache
#COPY data ./data
#COPY media ./media
#COPY settings ./settings
#COPY themes ./themes

#WORKDIR /tmp
#
#COPY content ./content

WORKDIR /var/www/html
COPY . /src/
COPY . .

RUN chmod +x /usr/local/bin/install-composer && \
    /usr/local/bin/install-composer && \
    ./composer.phar update && \
    chmod +x /usr/local/bin/init-server
#    chmod +x /usr/local/bin/adjust-rights && \
#    /usr/local/bin/adjust-rights


# Create a non-root user to own the files and run our server
#WORKDIR /var/www/html

# Expose single volume of data to simplify maintenance
#VOLUME /var/www/html/content

#RUN cp -R /src/* /var/www/html/
#RUN cp -R /tmp/* /var/www/html/
#ARG UNAME=www-data
#ARG UGROUP=www-data
#ENV UID=1000
#ENV GID=1000
#RUN usermod  --uid $UID $UNAME && \
#    groupmod --gid $GID $UGROUP

# Install PHP dependencies
#RUN /usr/local/bin/install-composer && \
#    ./composer.phar update && \
#    rm -rf composer* Dockerfile docker-utils/install-composer
#
## Adjust rights for www-data, a non-root user, to own the files and run our server
#RUN /usr/local/bin/adjust-rights && \
#    rm -rf Dockerfile


#RUN sed -i 's/^exec /chown www-data:www-data \/var\/www\/html/\n\nexec /' /usr/local/bin/apache2-foreground
#
### Use our non-root user
#RUN chown -R www-data:www-data /var/www/html/
#USER www-data

# Run the server
CMD ["/usr/local/bin/init-server"]