FROM php:8.2-fpm

RUN apt update && \
    apt install -qy wget curl git zip unzip && \
    apt clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/2.1.58/install-php-extensions /usr/local/bin/
RUN install-php-extensions @composer zip

COPY docker/php/php.ini /usr/local/etc/php/conf.d/satisfy.ini
RUN chown -R www-data:www-data /var/www

USER www-data
RUN mkdir -p ~/.ssh && chmod 0700 ~/.ssh && ssh-keyscan -H github.com >> ~/.ssh/known_hosts && \
    mkdir -p ~/.composer && \
    wget -O ~/.composer/keys.dev.pub https://composer.github.io/snapshots.pub && \
    wget -O ~/.composer/keys.tags.pub https://composer.github.io/releases.pub && \
    mkdir -p /var/www/satisfy/var && \
    mkdir -p /var/www/satisfy/public

ENV APP_PATH=/var/www/satisfy
ENV APP_ENV=prod
ENV APP_DEBUG=0
WORKDIR /var/www/satisfy

VOLUME /var/www/.composer
VOLUME /var/www/satisfy

COPY --chown=www-data:www-data . /var/www/satisfy/
