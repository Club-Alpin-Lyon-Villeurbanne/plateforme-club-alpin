ARG PHP_VERSION=8.2.25           # https://hub.docker.com/_/php/tags?page=1&name=8.2
ARG COMPOSER_VERSION=2.8.1       # https://hub.docker.com/_/composer/tags

ARG PHIVE_VERSION=0.15.3         # https://github.com/phar-io/phive/releases
ARG PHPEXTINST_VERSION=2.6.1     # https://github.com/mlocati/docker-php-extension-installer/releases

ARG IGBINARY_VERSION=3.2.16      # https://pecl.php.net/package/igbinary
ARG APCU_VERSION=5.1.24          # https://pecl.php.net/package/apcu
ARG IMAGICK_VERSION=3.7.0        # https://pecl.php.net/package/imagick
ARG XDEBUG_VERSION=3.3.2         # https://pecl.php.net/package/xdebug
ARG UID
ARG GID

FROM composer:${COMPOSER_VERSION} as build_composer

WORKDIR /build

FROM php:${PHP_VERSION}-apache as build_installer
ARG PHPEXTINST_VERSION

RUN curl -fsLo /usr/local/bin/install-php-extensions https://github.com/mlocati/docker-php-extension-installer/releases/download/${PHPEXTINST_VERSION}/install-php-extensions && \
    chmod +x /usr/local/bin/install-php-extensions

FROM php:${PHP_VERSION}-apache as build_phive
ARG PHIVE_VERSION

RUN apt-get update && \
    apt-get install -y \
        gpg

RUN curl -fsLo phive.phar "https://github.com/phar-io/phive/releases/download/${PHIVE_VERSION}/phive-${PHIVE_VERSION}.phar" && \
    curl -fsLo phive.phar.asc "https://github.com/phar-io/phive/releases/download/${PHIVE_VERSION}/phive-${PHIVE_VERSION}.phar.asc" && \
    gpg --no-tty --keyserver hkps://keys.openpgp.org --recv-keys 0x9D8A98B29B2D5D79 && \
    gpg --no-tty --verify phive.phar.asc phive.phar && \
    rm phive.phar.asc && \
    chmod +x phive.phar && \
    mv phive.phar /usr/local/bin/phive

FROM php:${PHP_VERSION}-apache

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
    nano \
    vim \
    jq \
    cron \
    locales \
    apt-utils \
    git \
    g++ \
    default-mysql-client && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY --from=build_composer /usr/bin/composer /usr/bin/composer
COPY --from=build_phive /usr/local/bin/phive /usr/local/bin/phive
COPY --from=build_installer /usr/local/bin/install-php-extensions /usr/local/bin/install-php-extensions

ARG APCU_VERSION \
    IGBINARY_VERSION \
    IMAGICK_VERSION \
    XDEBUG_VERSION

# Install some extension and activate them
RUN install-php-extensions \
        imagick-${IMAGICK_VERSION} \
        igbinary-${IGBINARY_VERSION} \
        apcu-${APCU_VERSION} \
        pdo_mysql-stable \
        zip-stable \
        opcache-stable \
        mbstring-stable \
        mysqli-stable \
        pcntl-stable \
        sockets-stable \
        intl-stable \
        gd \
        xdebug-${XDEBUG_VERSION}

COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules
COPY --from=node /usr/local/bin/node /usr/local/bin/node
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen && locale-gen

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
ENV APACHE_DOC_ROOT=/var/www/html/public
RUN a2enmod rewrite

COPY ./utils/docker-999-php.ini $PHP_INI_DIR/conf.d/999-php.ini

WORKDIR /var/www/

ARG UID \
    GID
ENV UID=${UID}
ENV GID=${GID}
RUN groupmod -g $GID www-data
RUN usermod -u $UID -g $GID www-data
USER www-data