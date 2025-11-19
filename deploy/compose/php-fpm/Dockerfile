FROM php:8.3-fpm-alpine

RUN apk add --no-cache icu-dev oniguruma-dev libzip-dev git bash \
    && docker-php-ext-install intl mbstring pdo pdo_mysql zip

RUN apk add --no-cache $PHPIZE_DEPS openssl-dev \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && apk del $PHPIZE_DEPS

RUN apk add --no-cache autoconf gcc g++ make linux-headers \
    && pecl install xdebug redis \
    && docker-php-ext-enable xdebug redis \
    && apk del autoconf gcc g++ make linux-headers

COPY xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

WORKDIR /var/www/backend

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && php -r "unlink('composer-setup.php');"