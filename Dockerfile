# Building
# docker build -t osti .

# Mac
# docker run -p 3380:80 -v /var/lib/mysql/mysql.sock:/var/lib/mysql/mysql.sock -it --rm --name my-running-php-ost my-php-ost bash

# Linux
# docker run -p 3380:80 -v /var/lib/mysql/mysql.sock:/var/lib/mysql/mysql.sock -it --rm --name my-running-php-ost my-php-ost bash
# sans bash, no kill
# docker run -v /var/lib/mysql/mysql.sock:/var/lib/mysql/mysql.sock my-php-ost

# Xdebug NOTE
# You'll have to listen on the remote address IP address which is typically the
# gateway of docker. IE if the docker container's IP is 172.17.0.2,
# you'll need to listen on 172.17.0.1 or 0.0.0.0

FROM php:5.6-apache

RUN apt-get update && apt-get install -y \
        # gd build dep
        libpng12-dev \
        # gd optional build dep
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        # intl build dep
        libicu-dev \
        # imap build dep
        libkrb5-dev \
        libc-client-dev \
    && docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-install -j$(nproc) mysqli \
    && docker-php-ext-install -j$(nproc) intl \
    # PHP Version dependent! https://pecl.php.net/package/APCu
    # for php >=7 use apcu >= 5
    && yes '' | pecl install -f apcu-4.0.11 \
    && docker-php-ext-enable apcu \
    # For php 5.2-5.5 uncomment the following line
    # && pecl install -f zendopcache \
    && docker-php-ext-enable opcache

# If you don't like volume mounting, use this
# Should be the last step, for ludicrous speed
COPY . /var/www/html/

WORKDIR /var/www/html/

CMD php -f setup/test/run-tests.php

# private only
EXPOSE 80
