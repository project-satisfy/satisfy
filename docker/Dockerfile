FROM php:7.3-apache

RUN apt-get update -q \
    && apt-get install -qy git cron supervisor zip unzip wget \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

ADD docker/conf/supervisor/*.conf /etc/supervisor/conf.d/
ADD docker/conf/cron.conf         /etc/cron.d/satisfy
ADD docker/conf/apache.conf       /etc/apache2/sites-available/000-default.conf
ADD docker/conf/php.ini           /usr/local/etc/php/conf.d/php.ini
RUN a2enmod rewrite

COPY ./*  /var/www/satisfy/
RUN mkdir /var/www/.ssh && chown -R www-data:www-data /var/www
VOLUME    /var/www/.ssh

ARG APP_DEBUG=0
ARG APP_ENV=dev
# symbolic links to index.php and static files
ARG APP_PATH=/var/www/satisfy

WORKDIR /var/www/satisfy

#ENTRYPOINT ["supervisord", "-n"]
CMD [ "bash", "start.sh &" ]
