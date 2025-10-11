<?php

/**
 * Script para corregir la ubicación del Dockerfile
 * Copia el Dockerfile existente a todas las posibles ubicaciones donde el sistema lo busca
 */

$projectId = 'HigfxI9k01';
$baseDir = __DIR__;

// Ruta donde existe el Dockerfile
$sourceDockerfile = $baseDir . '/storage/app/public/repos/' . $projectId . '/Dockerfile';

// Posibles rutas donde el sistema puede estar buscando el Dockerfile
$possiblePaths = [
    $baseDir . '/storage/app/repos/' . $projectId . '/Dockerfile',
    $baseDir . '/storage/app/temp/' . $projectId . '/Dockerfile',
    $baseDir . '/storage/app/projects/' . $projectId . '/Dockerfile',
    $baseDir . '/storage/repos/' . $projectId . '/Dockerfile',
    $baseDir . '/storage/temp/' . $projectId . '/Dockerfile',
    $baseDir . '/storage/projects/' . $projectId . '/Dockerfile',
    $baseDir . '/tmp/' . $projectId . '/Dockerfile',
    $baseDir . '/temp/' . $projectId . '/Dockerfile',
];

echo "=== Script de Corrección de Dockerfile ===\n\n";

// Verificar que el archivo fuente existe
if (!file_exists($sourceDockerfile)) {
    echo "❌ ERROR: Dockerfile fuente no encontrado en: $sourceDockerfile\n";
    exit(1);
}

echo "✅ Dockerfile fuente encontrado: $sourceDockerfile\n\n";
echo "📋 Copiando Dockerfile a posibles ubicaciones...\n\n";

$copiedCount = 0;
$errorCount = 0;

foreach ($possiblePaths as $targetPath) {
    $targetDir = dirname($targetPath);
    
    echo "🔍 Procesando: $targetPath\n";
    
    // Crear directorio si no existe
    if (!is_dir($targetDir)) {
        if (mkdir($targetDir, 0755, true)) {
            echo "   📁 Directorio creado: $targetDir\n";
        } else {
            echo "   ❌ Error creando directorio: $targetDir\n";
            $errorCount++;
            continue;
        }
    } else {
        echo "   📁 Directorio ya existe: $targetDir\n";
    }
    
    // Copiar Dockerfile
    if (copy($sourceDockerfile, $targetPath)) {
        echo "   ✅ Dockerfile copiado exitosamente\n";
        $copiedCount++;
    } else {
        echo "   ❌ Error copiando Dockerfile\n";
        $errorCount++;
    }
    
    echo "\n";
}

echo "=== Resumen ===\n";
echo "✅ Archivos copiados exitosamente: $copiedCount\n";
echo "❌ Errores encontrados: $errorCount\n\n";

// También copiar docker-compose.yml si existe
$sourceCompose = $baseDir . '/storage/app/public/repos/' . $projectId . '/docker-compose.yml';
if (file_exists($sourceCompose)) {
    echo "📋 Copiando docker-compose.yml...\n\n";
    
    foreach ($possiblePaths as $targetPath) {
        $targetComposePath = dirname($targetPath) . '/docker-compose.yml';
        
        if (file_exists(dirname($targetPath))) {
            if (copy($sourceCompose, $targetComposePath)) {
                echo "✅ docker-compose.yml copiado a: " . dirname($targetPath) . "\n";
            }
        }
    }
}

echo "\n🎯 Script completado!\n";
echo "💡 Ahora puedes intentar el despliegue desde la interfaz web.\n";

?>