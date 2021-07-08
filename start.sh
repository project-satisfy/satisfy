#!/bin/bash

chmod +w  /var/www/satisfy/storage/
mv ./satis.json /var/www/satisfy/storage/
/usr/bin/supervisord -n
