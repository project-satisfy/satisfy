#!/bin/bash

chmod +w  /var/www/satisfy/storage/
/usr/bin/supervisord -n
