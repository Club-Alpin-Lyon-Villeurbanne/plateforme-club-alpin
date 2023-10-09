FROM php:8.2-apache

COPY --from=composer:2.1.9 /usr/bin/composer /usr/bin/composer

RUN apt install -y \
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
        mariadb-client && \
    apt clean

RUN curl -Lo /usr/local/bin/gosu https://github.com/tianon/gosu/releases/download/1.10/gosu-amd64 && chmod +x /usr/local/bin/gosu

RUN /usr/local/bin/docker-php-ext-install -j$(nproc) mysqli pdo_mysql && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) zip && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) opcache && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) mbstring && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) pcntl && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) sockets && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) intl && \
    /usr/local/bin/docker-php-ext-configure gd --with-jpeg && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) gd

RUN pecl install apcu-5.1.22 && \
    echo "extension=apcu.so" > /usr/local/etc/php/conf.d/ext-apcu.ini && \
    pecl install imagick-3.7.0 && \
    echo "extension=imagick.so" > /usr/local/etc/php/conf.d/ext-imagick.ini

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache Document Root
ENV APACHE_DOC_ROOT /var/www/html/public

# Additional configuration files
COPY ./utils/docker-999-php.ini /usr/local/etc/php/conf.d/999-php.ini
COPY ./utils/docker-phpinfo.php /var/www/html/phpinfo.php

RUN wget -O phive.phar "https://github.com/phar-io/phive/releases/download/0.15.1/phive-0.15.1.phar" && \
    wget -O phive.phar.asc "https://github.com/phar-io/phive/releases/download/0.15.1/phive-0.15.1.phar.asc" && \
    gpg --no-tty --keyserver hkps://keys.openpgp.org --recv-keys 0x9D8A98B29B2D5D79 && \
    gpg --no-tty --verify phive.phar.asc phive.phar && \
    rm phive.phar.asc && \
    chmod +x phive.phar && \
    mv phive.phar /usr/local/bin/phive

# network
EXPOSE 80
EXPOSE 443

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
