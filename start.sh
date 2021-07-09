#!/bin/bash

composer config --global github-oauth.github.com $DSEBOT_GH_TOKEN
chmod +w  /var/www/satisfy/storage/
ln -s     /var/www/satisfy/storage/satisfy/satis.json /var/www/satisfy/satis.json
/usr/bin/supervisord -n
