#!/bin/bash

PHP_BIN=$(which php)

$PHP_BIN $APP_PATH/bin/console satisfy:rebuild --skip-errors $APP_PATH/satis.json >> $APP_PATH/var/logs/cron.log
