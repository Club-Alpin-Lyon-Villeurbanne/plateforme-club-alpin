FROM php:8.2-apache

COPY --from=composer:2.1.9 /usr/bin/composer /usr/bin/composer

COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

ENV APACHE_DOC_ROOT /var/www/html/public

RUN apt-get update &&  apt-get install -y --no-install-recommends \
  libicu-dev \
  libpng-dev \
  libxml2-dev \
  libzip-dev \
  libonig-dev \
  libxslt-dev \
  libmagickwand-dev \
  libpcre3-dev \
  libpng-dev \
  libfreetype6-dev \
  libjpeg62-turbo-dev \
  curl \
  wget \
  unzip \
  gpg \
  vim \
  jq \
  cron \
  locales \
  apt-utils \
  git \
  g++ \
  mariadb-client

RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen
RUN echo "fr_FR.UTF-8 UTF-8" >> /etc/locale. &&  locale-gen

RUN a2enmod rewrite

RUN /usr/local/bin/docker-php-ext-install -j$(nproc) mysqli pdo_mysql && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) zip && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) opcache && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) mbstring && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) pcntl && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) sockets && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) intl && \
    /usr/local/bin/docker-php-ext-configure gd --with-jpeg && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) gd

COPY ./utils/docker-999-php.ini /usr/local/etc/php/conf.d/999-php.ini

RUN pecl install apcu && docker-php-ext-enable apcu
RUN pecl install imagick && docker-php-ext-enable imagick

WORKDIR /var/www/
