# osTicket application image -- Apache + mod_php serving the source in this repo.
#
# PHP 8.3 is used rather than 8.4 because ext-imap is no longer bundled with
# 8.4 and uw-imap is no longer packaged by Debian. osTicket only calls the
# imap_* helpers behind function_exists() guards (include/class.format.php)
# and fetches mail through laminas-mail, so the extension stays optional.
FROM php:8.3-apache

# Libraries needed to build (and run) the PHP extensions osTicket wants. The
# -dev packages are deliberately kept so `docker-php-ext-install` still works
# in images derived from this one.
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libwebp-dev \
        libicu-dev \
        libldap2-dev \
        libxml2-dev \
        libzip-dev \
        ; \
    docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp; \
    docker-php-ext-configure ldap --with-libdir="lib/$(dpkg-architecture -q DEB_HOST_MULTIARCH)"; \
    docker-php-ext-install -j"$(nproc)" \
        gd \
        gettext \
        intl \
        ldap \
        mysqli \
        opcache \
        zip \
        ; \
    pecl install apcu; \
    docker-php-ext-enable apcu; \
    rm -rf /var/lib/apt/lists/*

# osTicket relies on the .htaccess rewrite rules shipped in api/, pages/ and
# scp/apps/ to route extension-less URLs.
RUN a2enmod rewrite headers expires

COPY docker/app/php-osticket.ini /usr/local/etc/php/conf.d/zz-osticket.ini
COPY docker/app/apache-osticket.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

# The whole application lives in the image; only state goes to /data. The code
# stays root-owned and read-only to www-data -- everything osTicket needs to
# write (ost-config.php) is symlinked out to the volume by the entrypoint.
COPY . /var/www/html/

# Pristine copies used to bootstrap an empty /data on first boot.
RUN set -eux; \
    rm -rf /var/www/html/docker; \
    mkdir -p /opt/osticket-defaults /data; \
    cp -a /var/www/html/include/plugins /opt/osticket-defaults/plugins; \
    cp /var/www/html/include/ost-sampleconfig.php /opt/osticket-defaults/ost-config.php

COPY docker/app/entrypoint.sh /usr/local/bin/entrypoint.sh
COPY docker/app/cron.sh /usr/local/bin/osticket-cron.sh
RUN chmod +x /usr/local/bin/entrypoint.sh /usr/local/bin/osticket-cron.sh

VOLUME ["/data"]
EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
