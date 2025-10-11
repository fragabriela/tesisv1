<?php

require_once 'vendor/autoload.php';

use App\Models\Tesis;

echo "=== Verificación de project_repo_path ===\n\n";

$tesis = Tesis::where('id', 22)->first();

if ($tesis) {
    echo "✅ Tesis encontrada (ID: 22)\n";
    echo "📁 project_repo_path: " . $tesis->project_repo_path . "\n\n";
    
    $dockerfilePath = $tesis->project_repo_path . '/Dockerfile';
    $composePath = $tesis->project_repo_path . '/docker-compose.yml';
    
    echo "🔍 Verificando archivos:\n";
    echo "   Dockerfile: " . $dockerfilePath . "\n";
    echo "   Existe: " . (file_exists($dockerfilePath) ? "✅ SÍ" : "❌ NO") . "\n\n";
    
    echo "   docker-compose.yml: " . $composePath . "\n";
    echo "   Existe: " . (file_exists($composePath) ? "✅ SÍ" : "❌ NO") . "\n\n";
    
    // Verificar permisos
    if (file_exists($dockerfilePath)) {
        echo "📋 Información del Dockerfile:\n";
        echo "   Tamaño: " . filesize($dockerfilePath) . " bytes\n";
        echo "   Permisos: " . substr(sprintf('%o', fileperms($dockerfilePath)), -4) . "\n";
        echo "   Es legible: " . (is_readable($dockerfilePath) ? "✅ SÍ" : "❌ NO") . "\n\n";
    }
    
    // Mostrar contenido de project_repo_path
    echo "📂 Contenido del directorio project_repo_path:\n";
    if (is_dir($tesis->project_repo_path)) {
        $files = scandir($tesis->project_repo_path);
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                echo "   - $file\n";
            }
        }
    } else {
        echo "   ❌ El directorio no existe o no es accesible\n";
    }
    
} else {
    echo "❌ Tesis con ID 22 no encontrada\n";
    
    // Buscar todas las tesis disponibles
    echo "\n📋 Tesis disponibles:\n";
    $allTesis = Tesis::all();
    foreach ($allTesis as $t) {
        echo "   ID: {$t->id} - proyecto_id: {$t->proyecto_id} - project_repo_path: {$t->project_repo_path}\n";
    }
}

?>