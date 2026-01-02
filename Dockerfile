FROM php:8.4-apache

WORKDIR /var/www

# Installation des dépendances nécessaires à certaines extensions PHP
RUN apt-get update && apt-get install -y \
    libicu-dev \
    unzip \
    git \
    zip \
    curl \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        intl \
        gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Skip composer install during build; run it on the mounted volume at runtime if needed.

# Copier ta configuration Apache
COPY ./docker/apache/vhost.conf /etc/apache2/sites-available/000-default.conf

# Activer mod_rewrite
RUN a2enmod rewrite

# Fichier php.ini personnalisé (à créer dans ./php/conf/php.ini)
COPY ./php/conf/php.ini /usr/local/etc/php/php.ini

# Configuration de Xdebug (copie du fichier une seule fois)
COPY ./php/conf/xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Donner les bonnes permissions au dossier /var/www
RUN chown -R www-data:www-data /var/www && chmod -R 755 /var/www
