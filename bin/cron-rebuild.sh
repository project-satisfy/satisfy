#!/bin/bash

PHP_BIN=$(which php)

$PHP_BIN $APP_PATH/bin/console satisfy:rebuild $APP_PATH/satis.json >> $APP_PATH/var/log/cron.log
