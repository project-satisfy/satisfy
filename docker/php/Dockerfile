FROM php:8.0-fpm

RUN apt update && \
    apt install -qy wget curl git zip unzip libzip-dev && \
    apt clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN docker-php-ext-install zip

RUN wget -O /usr/local/bin/composer https://getcomposer.org/download/latest-stable/composer.phar && chmod +x /usr/local/bin/composer
RUN chown -R www-data:www-data /var/www

COPY php.ini /usr/local/etc/php/conf.d/satisfy.ini

USER www-data
RUN mkdir -p ~/.ssh && chmod 0700 ~/.ssh && ssh-keyscan -H github.com >> ~/.ssh/known_hosts && \
    mkdir -p ~/.composer && chown www-data:www-data ~/.composer
