#!/bin/bash

mv ./satis.json /var/www/satisfy/storage/
/usr/bin/supervisord -n
