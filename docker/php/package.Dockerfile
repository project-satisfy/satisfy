FROM unit:1.32.0-php8.2

RUN apt update && \
    apt install -qy wget curl git zip unzip && \
    apt clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

ADD --chmod=0755 https://github.com/mlocati/docker-php-extension-installer/releases/download/2.2.5/install-php-extensions /usr/local/bin/
RUN install-php-extensions @composer zip

COPY docker/php/unit.json /docker-entrypoint.d/config.json
COPY docker/php/php.ini /usr/local/etc/php/conf.d/satisfy.ini
RUN chown -R www-data:www-data /var/www

USER www-data
RUN mkdir -p ~/.ssh && chmod 0700 ~/.ssh && ssh-keyscan -H github.com >> ~/.ssh/known_hosts && \
    mkdir -p ~/.composer && \
    wget -O ~/.composer/keys.dev.pub https://composer.github.io/snapshots.pub && \
    wget -O ~/.composer/keys.tags.pub https://composer.github.io/releases.pub
VOLUME /var/www/.composer

ENV APP_PATH=/var/www/html
ENV APP_ENV=prod
ENV APP_DEBUG=0
ENV COMPOSER_HOME=/var/www/.composer
ENV COMPOSER_CACHE=/var/www/html/var/cache/composer
WORKDIR /var/www/html

COPY --chown=www-data:www-data . /var/www/html/

RUN mkdir /var/www/html/var
VOLUME /var/www/html/var

USER root
