FROM php:8.2-fpm AS system

# Install necessary components
RUN set -eux; \
    echo "deb http://deb.debian.org/debian bookworm main" > /etc/apt/sources.list.d/bookworm.list; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        curl \
        libc-client2007e-dev \
        libkrb5-dev \
        libxml2-dev \
        libzip-dev \
        unzip; \
    rm -f /etc/apt/sources.list.d/bookworm.list; \
    rm -rf /var/lib/apt/lists/*

# Build stage for system plugins
FROM system AS plugins-build

ARG INSTALL_SYS_PLUGINS=audit,auth-2fa,auth-cas,auth-ldap,auth-oauth2,auth-passthru,storage-fs,storage-s3
ENV DEBIAN_FRONTEND noninteractive

# Install git for cloning
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        git; \
    rm -f /etc/apt/sources.list.d/bookworm.list; \
    rm -rf /var/lib/apt/lists/*

# Clone osTicket-plugins repository and build plugins
RUN set -eux; \
    mkdir -p "/tmp"; \
    cd "/tmp"; \
    git clone --depth 1 https://github.com/osTicket/osTicket-plugins.git osticket-plugins; \
    cd "/tmp/osticket-plugins"; \
    rm -rf .git; \
    php make.php hydrate; \
    IFS=','; \
    for plugin in $INSTALL_SYS_PLUGINS; do \
        plugin=$(echo "$plugin" | xargs); \
        php -dphar.readonly=0 make.php build "$plugin"; \
    done; \
    find . ! -name . -type d -print0 -maxdepth 1 | xargs -0 rm -r --; \
    find . -type f -not -name '*.phar' -print0 | xargs -0 rm -- ; \
    rm composer.phar

# Main application stage
FROM system AS app

ENV TZ Europe/Kiev
ENV DEBIAN_FRONTEND noninteractive

# Install system dependencies and IMAP requirements in a single layer
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends \
        antiword \
        catdoc \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        nginx \
        poppler-utils; \
    rm -f /etc/apt/sources.list.d/bookworm.list; \
    rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
# Note: xml, dom, phar, fileinfo, opcache are built-in in PHP 8.2
RUN set -eux; \
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j"$(nproc)" \
        mysqli \
        gd \
        gettext \
        mbstring \
        intl \
        zip

# Configure and install IMAP extension
RUN set -eux; \
    docker-php-ext-configure imap --with-kerberos --with-imap-ssl; \
    docker-php-ext-install -j"$(nproc)" imap

# Install APCu extension
RUN set -eux; \
    pecl install apcu; \
    docker-php-ext-enable apcu; \
    rm -rf /tmp/pear

# Copy PHP configuration
COPY .docker/php/php.ini /usr/local/etc/php/conf.d/osticket.ini

# Remove default nginx configuration and copy project-specific config
RUN rm -f /etc/nginx/conf.d/default.conf && rm -f /etc/nginx/sites-enabled/default
COPY .docker/nginx/nginx.conf /etc/nginx/conf.d/default.conf

# Set working directory
WORKDIR /var/www/html

# Copy built phar plugins from plugins-build stage
COPY --from=plugins-build /tmp/osticket-plugins/ /var/www/html/include/plugins/

COPY . /var/www/html
COPY include/ost-sampleconfig.php include/ost-config.php

# Set proper permissions (will be set on mounted volume at runtime)
# Note: We keep root user for proper file permissions in container

EXPOSE 80

CMD ["bash", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
