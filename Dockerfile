FROM php:8.3-apache

WORKDIR /var/www/html
COPY index.php .
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
