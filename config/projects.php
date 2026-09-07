<?php

return [
    'laragon_path' => env('PROJECT_LARAGON_PATH', 'C:/laragon'),
    'php_binary' => env('PROJECT_PHP_BINARY'),
    'npm_binary' => env('PROJECT_NPM_BINARY'),
    'docker' => [
        'binary' => env('PROJECT_DOCKER_BINARY', 'C:/Program Files/Docker/Docker/resources/bin/docker.exe'),
        'compose_binary' => env('PROJECT_DOCKER_COMPOSE_BINARY', 'C:/Program Files/Docker/Docker/resources/bin/docker-compose.exe'),
    ],
    'composer' => [
        'phar' => env('PROJECT_COMPOSER_PHAR'),
        'runtime_path' => env('PROJECT_COMPOSER_RUNTIME_PATH'),
    ],
    'mysql' => [
        'host' => env('PROJECT_DB_HOST', env('DB_HOST', '127.0.0.1')),
        'port' => env('PROJECT_DB_PORT', env('DB_PORT', '3306')),
        'username' => env('PROJECT_DB_USERNAME', env('DB_USERNAME', 'root')),
        'password' => env('PROJECT_DB_PASSWORD', env('DB_PASSWORD', '')),
        'binary' => env('PROJECT_MYSQL_BINARY'),
    ],
];
