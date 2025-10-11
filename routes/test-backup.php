<?php

use Illuminate\Support\Facades\Route;
use App\Services\ProjectBackupService;
use Illuminate\Http\Request;

Route::get('/test-backup-detection', function() {
    $backupService = new ProjectBackupService();
    
    // Probar detección MySQL
    $mysqlBackupPath = storage_path('app/public/backups/test_mysql_backup.sql');
    $mysqlResult = $backupService->detectDatabaseType($mysqlBackupPath);
    
    // Probar detección PostgreSQL  
    $postgresBackupPath = storage_path('app/public/backups/test_postgres_backup.sql');
    $postgresResult = $backupService->detectDatabaseType($postgresBackupPath);
    
    return response()->json([
        'mysql_detection' => $mysqlResult,
        'postgres_detection' => $postgresResult,
        'test_files' => [
            'mysql_exists' => file_exists($mysqlBackupPath),
            'postgres_exists' => file_exists($postgresBackupPath)
        ]
    ]);
});

Route::post('/test-docker-deploy', function(Request $request) {
    try {
        $backupService = new ProjectBackupService();
        
        $projectPath = storage_path('app/public/repos/euDPtMoU2s');
        $backupFile = $request->input('backup_file', 'test_mysql_backup.sql');
        $backupPath = storage_path('app/public/backups/' . $backupFile);
        
        if (!file_exists($backupPath)) {
            return response()->json([
                'error' => 'Archivo de backup no encontrado: ' . $backupPath
            ], 404);
        }
        
        // Detectar tipo de base de datos
        $dbInfo = $backupService->detectDatabaseType($backupPath);
        
        // Simular ID de contenedor para prueba
        $containerId = 'test-container';
        
        return response()->json([
            'backup_path' => $backupPath,
            'database_detection' => $dbInfo,
            'message' => 'Detección completada exitosamente'
        ]);
        
    } catch (Exception $e) {
        return response()->json([
            'error' => 'Error en prueba: ' . $e->getMessage()
        ], 500);
    }
});