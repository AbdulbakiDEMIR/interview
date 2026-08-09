# PHP 8.4 ve Apache tabanlı resmi imajı kullanıyoruz
FROM php:8.4-apache

# Apache mod_rewrite modülünü aktifleştir (Routing işlemleri için şart)
RUN a2enmod rewrite

# Sistem bağımlılıklarını ve PHP eklentilerini yüklüyoruz
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install zip pdo pdo_mysql

# Composer'ı resmi imajdan kopyalıyoruz
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Çalışma dizinini ayarlıyoruz
WORKDIR /var/www/html

# Proje dosyalarını konteyner içine kopyalıyoruz
COPY . /var/www/html

# Apache DocumentRoot izinlerini düzenliyoruz
RUN mkdir -p /var/www/html/templates_c \
    && composer install --no-interaction --prefer-dist --optimize-autoloader \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/templates_c