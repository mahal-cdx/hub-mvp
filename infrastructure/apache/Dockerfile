FROM php:8.3-apache-bookworm

RUN docker-php-ext-install -j"$(nproc)" pdo_mysql opcache \
    && a2enmod rewrite headers

COPY infrastructure/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/app

RUN mkdir -p /var/www/app/public /var/www/shared /var/www/storage/uploads \
    && chown -R www-data:www-data /var/www/app /var/www/storage

EXPOSE 80
