FROM php:8.2-apache

# Required libs for pdo_sqlite (+ useful zlib/zip); also need pkg-config
RUN apt-get update && apt-get install -y \
    libsqlite3-dev \
    pkg-config \
    zlib1g-dev \
    libzip-dev \
  && docker-php-ext-install pdo pdo_sqlite \
  && rm -rf /var/lib/apt/lists/*

# Enable rewrites and allow .htaccess overrides
RUN a2enmod rewrite \
  && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . .

EXPOSE 80
