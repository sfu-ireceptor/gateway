FROM php:7.4-apache

# install MongoDB PHP extension
RUN pecl install mongodb && echo "extension=mongodb.so" > /usr/local/etc/php/conf.d/mongo.ini

# install zip, composer
RUN apt-get update && \
	apt-get install -y zip libzip-dev && \
	curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# install mysql driver
RUN docker-php-ext-install mysqli pdo_mysql zip

# Apache setup
RUN a2dismod cgi
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
	sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf && \
	sed -ri -e 's!daily!monthly!g' /etc/logrotate.d/apache2 && \
	sed -ri -e 's!rotate 14!rotate 120!g' /etc/logrotate.d/apache2 && \
	a2enmod rewrite && \
	a2enmod headers

# add source code and dependencies
COPY . /var/www/html
WORKDIR /var/www/html
RUN composer install

# Laravel setup
RUN touch /var/www/html/storage/logs/laravel.log && \
        chmod -R 777 /var/www/html/storage && \
        cp .env.example .env && \
        php artisan key:generate && \
        php artisan -q storage:link
