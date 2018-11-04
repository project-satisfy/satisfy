FROM php:7.1-apache

RUN apt-get update -q \
    && apt-get install -qy git cron supervisor \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

ADD conf/supervisor/* /etc/supervisor/conf.d/
ADD conf/cron.conf /etc/cron.d/satisfy
ADD conf/apache.conf /etc/apache2/sites-available/000-default.conf
ADD conf/php.ini /usr/local/etc/php/conf.d/php.ini
RUN a2enmod rewrite

RUN mkdir /var/www/.ssh && chown -R www-data:www-data /var/www
VOLUME /var/www/.ssh

# symbolic links to index.php and static files
ARG APP_PATH=/var/www/satisfy

WORKDIR /var/www/satisfy

ENTRYPOINT ["supervisord", "-n"]
