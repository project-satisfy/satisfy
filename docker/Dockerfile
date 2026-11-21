# Satisfy 4: nginx serves the catalogue Satis writes, php-fpm serves the admin
# UI and the webhooks, and satisfy:worker runs Satis. The entrypoint's first
# argument picks which of them run; see docker/entrypoint.sh and
# docs/4.0-architecture.md.
#
# Build from the repository root: docker build -f docker/Dockerfile .

ARG PHP_IMAGE=php:8.4-fpm-bookworm
ARG COMPOSER_IMAGE=composer:2

FROM ${COMPOSER_IMAGE} AS composer

FROM ${PHP_IMAGE}

# git, ssh, zip and unzip are what Satis shells out to while it builds. libzip4
# is installed by name so that purging libzip-dev leaves it in place. APCu is
# the authz plugin's cache, shared by the PHP-FPM workers.
RUN set -eux; \
    apt-get update; \
    apt-get install -y --no-install-recommends nginx tini git openssh-client unzip zip libzip4 libzip-dev $PHPIZE_DEPS; \
    docker-php-ext-install -j"$(nproc)" zip pcntl opcache; \
    pecl install apcu; \
    docker-php-ext-enable apcu; \
    apt-get purge -y --auto-remove libzip-dev $PHPIZE_DEPS; \
    rm -rf /var/lib/apt/lists/* /tmp/pear /etc/nginx/sites-enabled/default

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY docker/php.ini /usr/local/etc/php/conf.d/satisfy.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zzz-satisfy.conf
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/ /usr/local/share/satisfy-nginx/
COPY --chmod=0755 docker/entrypoint.sh /usr/local/bin/satisfy-entrypoint

ENV APP_PATH=/var/www/html \
    APP_ENV=prod \
    APP_DEBUG=0 \
    COMPOSER_HOME=/var/www/.composer \
    COMPOSER_CACHE=/var/www/html/var/cache/composer \
    SATISFY_QUEUE_DIR=/var/www/queue \
    AUTHZ_DATA_DIR=/var/www/authz

WORKDIR /var/www/html
# The base image leaves /var/www/html writable by everyone. The nginx includes
# for plugins start empty and belong to www-data, so the entrypoint can write
# them whichever user the container runs as.
RUN set -eux; \
    chown www-data:www-data /var/www /var/www/html; \
    chmod 0755 /var/www/html; \
    mkdir -p /etc/nginx/satisfy; \
    touch /etc/nginx/satisfy/http.conf /etc/nginx/satisfy/proxy.conf /etc/nginx/satisfy/server.conf /etc/nginx/satisfy/catalogue.conf; \
    chown -R www-data:www-data /etc/nginx/satisfy

USER www-data
RUN set -eux; \
    mkdir -p ~/.ssh ~/.composer ~/queue ~/authz; \
    chmod 0700 ~/.ssh ~/authz; \
    ssh-keyscan -H github.com >> ~/.ssh/known_hosts; \
    git config --global --add safe.directory /var/www/html

COPY --chown=www-data:www-data . /var/www/html/
RUN composer install --no-dev --no-interaction --no-progress --prefer-dist --classmap-authoritative --no-cache

USER root
# The image's own front controller and assets. The entrypoint copies them back
# into public/ on start, because a volume mounted there keeps whatever image
# first filled it.
RUN cp -a /var/www/html/public /usr/local/share/satisfy-public

# The queue has its own directory so that a web container and a worker
# container can share it without sharing var/. authz keeps its tokens in
# /var/www/authz, which is irreplaceable: losing it means reissuing every one.
VOLUME ["/var/www/.composer", "/var/www/html/var", "/var/www/queue", "/var/www/authz"]
EXPOSE 80
STOPSIGNAL SIGTERM
ENTRYPOINT ["tini", "--", "satisfy-entrypoint"]
CMD ["all"]
