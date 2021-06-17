FROM php:7.3-apache

RUN apt-get update -y &&\
    apt-get install -y sendmail libpng-dev zlib1g-dev libc-client-dev libkrb5-dev libicu-dev libzip-dev zip

RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
    docker-php-ext-configure intl && \
    docker-php-ext-configure gd && \
    docker-php-ext-install mysqli imap intl gd zip
