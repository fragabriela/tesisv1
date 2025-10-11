<?php

use Illuminate\Support\Facades\Route;
use App\Services\DockerService;

Route::get('/test-create-dockerfile', function() {
    try {
        $dockerService = new DockerService();
        
        // Probar crear Dockerfile para el proyecto HigfxI9k01
        $result = $dockerService->createDockerfile('repos/HigfxI9k01', 'laravel');
        
        $dockerfilePath = storage_path('app/public/repos/HigfxI9k01/Dockerfile');
        $dockerfileExists = file_exists($dockerfilePath);
        
        return response()->json([
            'create_dockerfile_result' => $result,
            'dockerfile_exists' => $dockerfileExists,
            'dockerfile_path' => $dockerfilePath,
            'project_directory' => storage_path('app/public/repos/HigfxI9k01'),
            'directory_contents' => scandir(storage_path('app/public/repos/HigfxI9k01')),
            'message' => $result ? 'Dockerfile creado exitosamente' : 'Error creando Dockerfile'
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'error' => 'Error: ' . $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile()
        ], 500);
    }
});