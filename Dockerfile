FROM php:8.2-apache

# SQLite + PDO
RUN apt-get update && apt-get install -y zlib1g-dev libzip-dev \
  && rm -rf /var/lib/apt/lists/* \
  && docker-php-ext-install pdo pdo_sqlite

# Allow .htaccess + rewrites
RUN a2enmod rewrite \
  && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . .

EXPOSE 80
