<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Docker API URL
    |--------------------------------------------------------------------------
    |
    | URL para la API de Docker. Normalmente es http://localhost:2375
    | Si Docker está en un host remoto, cambiar la URL adecuadamente.
    |
    */
    'api_url' => env('DOCKER_API_URL', 'http://localhost:2375'),
    
    /*
    |--------------------------------------------------------------------------
    | Docker Socket Path
    |--------------------------------------------------------------------------
    |
    | Path para el socket de Docker. Normalmente en sistemas Unix es
    | /var/run/docker.sock
    |
    */
    'socket_path' => env('DOCKER_SOCKET_PATH', '/var/run/docker.sock'),
    
    /*
    |--------------------------------------------------------------------------
    | Proxy Host
    |--------------------------------------------------------------------------
    |
    | Hostname usado para acceder a los contenedores desde el exterior
    |
    */
    'proxy_host' => env('DOCKER_PROXY_HOST', 'localhost'),
    
    /*
    |--------------------------------------------------------------------------
    | Project Storage Path
    |--------------------------------------------------------------------------
    |
    | Directorio donde se almacenarán los proyectos clonados
    |
    */
    'storage_path' => env('DOCKER_STORAGE_PATH', 'projects'),
    
    /*
    |--------------------------------------------------------------------------
    | Container Limits
    |--------------------------------------------------------------------------
    |
    | Límites de recursos para los contenedores
    |
    */
    'container_limits' => [
        'memory' => env('DOCKER_CONTAINER_MEMORY_LIMIT', '256m'),
        'cpus' => env('DOCKER_CONTAINER_CPU_LIMIT', '0.5'),
    ],
];
