FROM php:8.4-apache

WORKDIR /var/www

# Installation des dépendances nécessaires à certaines extensions PHP
RUN apt-get update && apt-get install -y \
    libicu-dev \
    unzip \
    git \
    zip \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        intl \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Nettoyage d'une éventuelle config xdebug activée par défaut
RUN rm -f /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini || true

# Réactivation propre de Xdebug (s'il est déjà installé dans l'image)
RUN docker-php-ext-enable xdebug || true

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
