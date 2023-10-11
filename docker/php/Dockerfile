FROM php:8.1-fpm

RUN apt update && \
    apt install -qy wget curl git zip unzip && \
    apt clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/2.1.58/install-php-extensions /usr/local/bin/
RUN install-php-extensions @composer zip

RUN chown -R www-data:www-data /var/www

COPY php.ini /usr/local/etc/php/conf.d/satisfy.ini

USER www-data
RUN mkdir -p ~/.ssh && chmod 0700 ~/.ssh && ssh-keyscan -H github.com >> ~/.ssh/known_hosts && \
    mkdir -p ~/.composer && chown www-data:www-data ~/.composer
