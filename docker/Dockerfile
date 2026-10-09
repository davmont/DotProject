FROM php:8.3-apache
RUN apt-get update && apt-get install -y --no-install-recommends libpng-dev libjpeg-dev libfreetype6-dev libzip-dev curl \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install -j"$(nproc)" mysqli gd zip \
 && rm -rf /var/lib/apt/lists/*
COPY php.ini /usr/local/etc/php/conf.d/zz-audit.ini
