FROM php:8.2-apache

COPY --from=composer:2.1.9 /usr/bin/composer /usr/bin/composer

COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm


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
  default-mysql-client

RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen
RUN echo "fr_FR.UTF-8 UTF-8" >> /etc/locale. &&  locale-gen

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
ENV APACHE_DOC_ROOT /var/www/html/public
RUN a2enmod rewrite

RUN docker-php-ext-install -j$(nproc) mysqli pdo_mysql && \
    docker-php-ext-install -j$(nproc) zip && \
    docker-php-ext-install -j$(nproc) opcache && \
    docker-php-ext-install -j$(nproc) mbstring && \
    docker-php-ext-install -j$(nproc) pcntl && \
    docker-php-ext-install -j$(nproc) sockets && \
    docker-php-ext-install -j$(nproc) intl && \
    docker-php-ext-configure gd --with-jpeg && \
    docker-php-ext-install -j$(nproc) gd

RUN pecl install apcu && docker-php-ext-enable apcu
RUN pecl install imagick && docker-php-ext-enable imagick
RUN pecl install xdebug && docker-php-ext-enable xdebug

COPY ./utils/docker-999-php.ini $PHP_INI_DIR/conf.d/999-php.ini

WORKDIR /var/www/

ARG UID
ARG GID
ENV UID=${UID}
ENV GID=${GID}
RUN groupmod -g $GID www-data
RUN usermod -u $UID -g $GID www-data
USER www-data