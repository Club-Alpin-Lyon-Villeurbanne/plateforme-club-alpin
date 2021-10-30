FROM php:7.4-apache

COPY --from=composer:2.1.9 /usr/bin/composer /usr/bin/composer

RUN apt update && \
    apt install -y \
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
        mariadb-client-10.5 && \
    apt clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN /usr/local/bin/docker-php-ext-install -j$(nproc) mysqli pdo_mysql && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) zip && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) opcache && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) mbstring && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) pcntl && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) sockets && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) intl && \
    /usr/local/bin/docker-php-ext-install -j$(nproc) gd

RUN pecl install apcu-5.1.20 && \
    echo "extension=apcu.so" > /usr/local/etc/php/conf.d/ext-apcu.ini && \
    pecl install apcu_bc-1.0.5 && \
    echo "extension=apc.so" > /usr/local/etc/php/conf.d/ext-apcu_bc.ini

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Configure Apache Document Root
ENV APACHE_DOC_ROOT /var/www/html/public

# Additional configuration files
COPY ./utils/docker-999-php.ini /usr/local/etc/php/conf.d/999-php.ini
COPY ./utils/docker-phpinfo.php /var/www/html/phpinfo.php
COPY ./utils/docker-start-apache.sh /usr/local/bin/start-apache.sh
RUN chmod +x /usr/local/bin/start-apache.sh

RUN wget -O phive.phar "https://github.com/phar-io/phive/releases/download/0.14.5/phive-0.14.5.phar" && \
    wget -O phive.phar.asc "https://github.com/phar-io/phive/releases/download/0.14.5/phive-0.14.5.phar.asc" && \
    gpg --no-tty --keyserver hkps://keys.openpgp.org --recv-keys 0x9D8A98B29B2D5D79 && \
    gpg --no-tty --verify phive.phar.asc phive.phar && \
    rm phive.phar.asc && \
    chmod +x phive.phar && \
    mv phive.phar /usr/local/bin/phive

# network
EXPOSE 80
EXPOSE 443

# Start!
CMD ["start-apache.sh"]
