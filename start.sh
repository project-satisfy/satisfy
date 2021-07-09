#!/bin/bash

chmod +w  /var/www/satisfy/storage/
ln -s     /var/www/satisfy/storage/satisfy/satis.json /var/www/satisfy/satis.json
/usr/bin/supervisord -n
