FROM php:7-apache

MAINTAINER Ramunas Dronga <ieskok@ramuno.lt>

RUN apt-get update -q \
    && apt-get install -qy git cron supervisor \
    && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

ADD docker/conf/supervisor/* /etc/supervisor/conf.d/
ADD docker/conf/cron.conf /etc/cron.d/
ADD docker/conf/apache.conf /etc/apache2/sites-available/000-default.conf
ADD docker/conf/php.ini /usr/local/etc/php/conf.d/php.ini
RUN a2enmod rewrite

RUN mkdir /var/www/.composer && mkdir /var/www/.ssh && chown -R www-data:www-data /var/www
VOLUME /var/www/.composer
VOLUME /var/www/.ssh

ADD ./app /var/www/app
ADD ./bin /var/www/bin
ADD ./src /var/www/src
ADD ./vendor /var/www/vendor
ADD ./public /var/www/html
ADD ./satis.json /var/www/satis.json

# symbolic links to index.php and static files
ARG APP_PATH=/var/www/

ENTRYPOINT ["supervisord", "-n"]
