<?php

use Symfony\Component\Dotenv\Dotenv;

require __DIR__ . '/../config/bootstrap.php';

unset($_SERVER['APP_ENV']);
(new Dotenv())->usePutenv()->loadEnv(__DIR__ . '/../.env');
