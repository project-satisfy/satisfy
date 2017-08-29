#!/bin/bash

/usr/local/bin/php /var/www/satisfy/bin/console satisfy:rebuild --skip-errors /var/www/satisfy/packages.conf /var/www/html > /tmp/satis.log

