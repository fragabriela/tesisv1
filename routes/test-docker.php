<?php

use Illuminate\Support\Facades\Route;
use App\Services\DockerService;

Route::get('/test-docker-service', function() {
    try {
        $dockerService = new DockerService();
        
        // Test de métodos privados usando reflexión
        $reflection = new ReflectionClass($dockerService);
        $getInternalPortMethod = $reflection->getMethod('getInternalPort');
        $getInternalPortMethod->setAccessible(true);
        
        $tests = [
            'laravel' => $getInternalPortMethod->invoke($dockerService, 'laravel'),
            'php' => $getInternalPortMethod->invoke($dockerService, 'php'),
            'java' => $getInternalPortMethod->invoke($dockerService, 'java'),
            'node' => $getInternalPortMethod->invoke($dockerService, 'node'),
            'python' => $getInternalPortMethod->invoke($dockerService, 'python'),
            'unknown' => $getInternalPortMethod->invoke($dockerService, 'unknown'),
        ];
        
        return response()->json([
            'message' => 'DockerService funciona correctamente',
            'getInternalPort_tests' => $tests,
            'docker_available' => $dockerService->checkDockerAvailability()
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'error' => 'Error en DockerService: ' . $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
});