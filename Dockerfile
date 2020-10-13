FROM php:7.3-apache

RUN apt-get update -y
RUN apt-get install -y sendmail libpng-dev zlib1g-dev libc-client-dev libkrb5-dev libicu-dev

RUN docker-php-ext-install mysqli
RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl
RUN docker-php-ext-install imap
RUN docker-php-ext-configure intl
RUN docker-php-ext-install intl
RUN docker-php-ext-configure gd
RUN docker-php-ext-install gd
