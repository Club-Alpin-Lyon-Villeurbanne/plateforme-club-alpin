FROM php:8.2-apache

COPY --from=composer:2.1.9 /usr/bin/composer /usr/bin/composer

COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

RUN apt-get update &&  apt-get install -y --no-install-recommends \
  locales apt-utils git libicu-dev g++ libpng-dev libxml2-dev libzip-dev libonig-dev libxslt-dev unzip \
  libmagickwand-dev \
        libpcre3-dev \
        libonig-dev \
        libzip-dev \
        libicu-dev \
        libpng-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        curl \
        wget \
        unzip \
        git \
        gpg \
        nano \
        vim \
        jq \
        cron \
        mariadb-client

RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen
RUN echo "fr_FR.UTF-8 UTF-8" >> /etc/locale. &&  locale-gen

RUN docker-php-ext-configure intl \
  &&  docker-php-ext-install \
  pdo pdo_mysql opcache intl zip calendar dom mbstring gd xsl mysqli

RUN pecl install apcu && docker-php-ext-enable apcu
RUN pecl install imagick && docker-php-ext-enable imagick

WORKDIR /var/www/
