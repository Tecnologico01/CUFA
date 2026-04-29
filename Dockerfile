FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql \
    && a2enmod rewrite

COPY docker/apache-legacy-prefix.conf /etc/apache2/conf-available/cufa-legacy-prefix.conf
COPY docker/php-upload.ini /usr/local/etc/php/conf.d/uploads.ini
COPY docker/railway-entrypoint.sh /usr/local/bin/railway-entrypoint

RUN a2enconf cufa-legacy-prefix \
    && chmod +x /usr/local/bin/railway-entrypoint

WORKDIR /var/www/html

COPY . /var/www/html/

RUN mkdir -p uploads/docentes uploads/planeaciones uploads/materiales \
    && chown -R www-data:www-data uploads

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/railway-entrypoint"]
CMD ["apache2-foreground"]
