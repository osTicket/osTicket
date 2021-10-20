# docker build -t unitely/osticket:latest .
# docker run -d -p 80:80 --name unitely_osticket unitely/osticket

FROM php:7.4-apache

# Maintainer
MAINTAINER Patrick Hewes "patrick.hewes@unite.ly"

ENV ADMIN_EMAIL='support@email.com'
ENV DBHOST='database'
ENV DBNAME='dbname'
ENV DBPASS='somepassword'
ENV DBTYPE='mysql'
ENV DBUSER='dbuser'
ENV SALT='1234567890'

RUN apt-get update -y && apt-get install -yf cron libpng-dev libc-client-dev libkrb5-dev libicu-dev && \
    pecl channel-update pecl.php.net && \
    pecl install apcu && \
    rm -r /var/lib/apt/lists/*  && \
    docker-php-ext-install mysqli && \
    docker-php-ext-configure imap --with-kerberos --with-imap-ssl && docker-php-ext-install imap && \
    docker-php-ext-install gd && \
    docker-php-ext-configure intl && \
    docker-php-ext-install intl && \
    docker-php-ext-enable apcu && \
    docker-php-ext-install opcache

ARG OST_ROOT=/var/www/html

COPY ./php.ini-development $PHP_INI_DIR/php.ini

RUN rm -rf $OST_ROOT/*

COPY . $OST_ROOT

RUN rm -rf $OST_ROOT/setup

RUN rm -f $OST_ROOT/php.ini-development

RUN mv $OST_ROOT/include/ost-sampleconfig.php $OST_ROOT/include/ost-config.php

RUN chmod 644 $OST_ROOT/include/ost-config.php