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
    && docker-php-ext-configure imap --with-kerberos --with-imap-ssl \
    && docker-php-ext-install -j$(nproc) imap \
    && docker-php-ext-install -j$(nproc) mysqli \
    && docker-php-ext-install -j$(nproc) intl \
    # PHP Version dependent! https://pecl.php.net/package/APCu
    # for php >=7 use apcu >= 5
    && yes '' | pecl install -f apcu-4.0.11 \
    && docker-php-ext-enable apcu \
    # For php 5.2-5.5 uncomment the following line
    # && pecl install -f zendopcache \
    && docker-php-ext-enable opcache \
    && pecl install -f xdebug \
    && docker-php-ext-enable xdebug \
    && echo "\
xdebug.remote_enable=1 \n\
xdebug.remote_connect_back=1 \n\
xdebug.remote_port=9000" \
    >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# If you don't like volume mounting, use this
# Should be the last step, for ludicrous speed
# COPY . /var/www/html/

# what's your ip address?
CMD echo "http://"$(\
        ip addr show | \
        grep inet.*global | \
        grep -Eo '[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}' | \
        head -n 1)"/" \
    && docker-php-entrypoint apache2-foreground
# private only
EXPOSE 80
