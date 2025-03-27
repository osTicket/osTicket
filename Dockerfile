FROM php:8.2-apache
WORKDIR /var/www/html
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    libzip-dev \
    libicu-dev \
    libpq-dev \
    unzip \
    git \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
    gd \
    mysqli \
    pdo_mysql \
    zip \
    intl \
    opcache
RUN a2enmod rewrite headers
COPY . .
RUN mkdir -p /var/www/html/include/plugins \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html
RUN cp include/ost-sampleconfig.php include/ost-config.php \
    && chown www-data:www-data include/ost-config.php \
    && chmod 666 include/ost-config.php
EXPOSE 80

HEALTHCHECK --interval=30s --timeout=3s \
    CMD curl -f http://localhost/ || exit 1
CMD ["apache2-foreground"]
