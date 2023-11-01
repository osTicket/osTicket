FROM php:7.3-apache

RUN apt-get update -y &&\
    apt-get install -y sendmail libpng-dev zlib1g-dev libc-client-dev libkrb5-dev libicu-dev libzip-dev zip

RUN docker-php-ext-configure imap --with-kerberos --with-imap-ssl && \
    docker-php-ext-configure intl && \
    docker-php-ext-configure gd && \
    docker-php-ext-install mysqli imap intl gd zip

RUN echo "file_uploads = On\n" \
         "memory_limit = 500M\n" \
         "upload_max_filesize = 500M\n" \
         "post_max_size = 500M\n" \
         "max_execution_time = 600\n" \
         > /usr/local/etc/php/conf.d/uploads.ini
