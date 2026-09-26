FROM php:8.3-apache-bookworm

RUN docker-php-ext-install -j"$(nproc)" pdo_mysql opcache \
    && a2enmod rewrite headers \
    && printf '%s\n' 'upload_max_filesize=25M' 'post_max_size=260M' 'max_file_uploads=10' 'max_input_time=300' 'display_errors=Off' 'log_errors=On' > /usr/local/etc/php/conf.d/hub-uploads.ini \
    && printf '%s\n' 'ServerTokens Prod' 'ServerSignature Off' > /etc/apache2/conf-available/hub-security.conf \
    && a2enconf hub-security

COPY infrastructure/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/app

RUN mkdir -p /var/www/app/public /var/www/shared /var/www/storage/uploads \
    && chown -R www-data:www-data /var/www/app /var/www/storage

EXPOSE 80
