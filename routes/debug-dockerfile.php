<?php

use Illuminate\Support\Facades\Route;
use App\Services\DockerService;

Route::get('/debug-dockerfile-creation/{tesisId}', function($tesisId) {
    try {
        $tesis = \App\Models\Tesis::find($tesisId);
        if (!$tesis) {
            return response()->json(['error' => 'Tesis no encontrada'], 404);
        }

        $dockerService = new DockerService();
        
        $repoPath = storage_path('app/public/' . $tesis->project_repo_path);
        $dockerfilePath = $repoPath . '/Dockerfile';
        
        $debug = [
            'tesis_id' => $tesisId,
            'project_repo_path' => $tesis->project_repo_path,
            'full_repo_path' => $repoPath,
            'dockerfile_path' => $dockerfilePath,
            'project_type' => $tesis->project_type,
            'dockerfile_exists_before' => file_exists($dockerfilePath),
        ];
        
        if (file_exists($dockerfilePath)) {
            $dockerfileContent = file_get_contents($dockerfilePath);
            $debug['dockerfile_size'] = strlen($dockerfileContent);
            $debug['dockerfile_preview'] = substr($dockerfileContent, 0, 200) . '...';
            
            // Verificar versión PHP en Dockerfile existente
            if (preg_match('/FROM php:(\d+\.\d+)-/', $dockerfileContent, $matches)) {
                $debug['current_php_version'] = $matches[1];
            }
        }
        
        // Intentar detectar versión PHP requerida
        $reflection = new ReflectionClass($dockerService);
        $detectMethod = $reflection->getMethod('detectRequiredPhpVersion');
        $detectMethod->setAccessible(true);
        $requiredPhpVersion = $detectMethod->invoke($dockerService, $tesis->project_repo_path);
        $debug['required_php_version'] = $requiredPhpVersion;
        
        // Intentar crear Dockerfile
        $createResult = $dockerService->createDockerfile($tesis->project_repo_path, $tesis->project_type);
        $debug['create_dockerfile_result'] = $createResult;
        $debug['dockerfile_exists_after'] = file_exists($dockerfilePath);
        
        return response()->json([
            'success' => true,
            'debug_info' => $debug
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'error' => 'Error en debug: ' . $e->getMessage(),
            'line' => $e->getLine(),
            'file' => basename($e->getFile())
        ], 500);
    }
});