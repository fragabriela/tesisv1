<?php
require_once 'vendor/autoload.php';

// Cargar configuración de Laravel
$app = require 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tesis;
use App\Services\DockerService;
use Illuminate\Support\Facades\Log;

echo "=== DIAGNÓSTICO DE DESPLIEGUE ===\n\n";

// 1. Verificar Docker
echo "1. Verificando Docker...\n";
exec('docker --version 2>&1', $dockerVersion, $dockerReturnCode);
if ($dockerReturnCode === 0) {
    echo "✓ Docker instalado: " . implode("\n", $dockerVersion) . "\n";
} else {
    echo "✗ Docker no está instalado o no es accesible\n";
    echo "Error: " . implode("\n", $dockerVersion) . "\n";
}

// 2. Verificar Docker Compose
echo "\n2. Verificando Docker Compose...\n";
exec('docker compose version 2>&1', $composeVersion, $composeReturnCode);
if ($composeReturnCode === 0) {
    echo "✓ Docker Compose disponible: " . implode("\n", $composeVersion) . "\n";
} else {
    echo "✗ Docker Compose no está disponible con 'docker compose'\n";
    exec('docker-compose --version 2>&1', $composeVersionOld, $composeReturnCodeOld);
    if ($composeReturnCodeOld === 0) {
        echo "✓ Docker Compose disponible (versión antigua): " . implode("\n", $composeVersionOld) . "\n";
    } else {
        echo "✗ Docker Compose no está disponible\n";
    }
}

// 3. Verificar contenedores en ejecución
echo "\n3. Verificando contenedores en ejecución...\n";
exec('docker ps 2>&1', $containers, $psReturnCode);
if ($psReturnCode === 0) {
    echo "✓ Contenedores activos:\n";
    foreach ($containers as $container) {
        echo "  " . $container . "\n";
    }
} else {
    echo "✗ Error al listar contenedores\n";
    echo "Error: " . implode("\n", $containers) . "\n";
}

// 4. Verificar espacio en disco
echo "\n4. Verificando espacio en disco...\n";
exec('docker system df 2>&1', $diskSpace, $dfReturnCode);
if ($dfReturnCode === 0) {
    echo "✓ Uso de disco de Docker:\n";
    foreach ($diskSpace as $line) {
        echo "  " . $line . "\n";
    }
} else {
    echo "✗ Error al verificar espacio en disco\n";
}

// 5. Verificar última tesis desplegada
echo "\n5. Verificando última tesis...\n";
try {
    $lastTesis = Tesis::orderBy('updated_at', 'desc')->first();
    if ($lastTesis) {
        echo "✓ Última tesis encontrada:\n";
        echo "  ID: " . $lastTesis->id . "\n";
        echo "  Título: " . $lastTesis->titulo . "\n";
        echo "  Estado del contenedor: " . ($lastTesis->container_status ?? 'sin estado') . "\n";
        echo "  ID del contenedor: " . ($lastTesis->container_id ?? 'no asignado') . "\n";
        echo "  Error de despliegue: " . ($lastTesis->deployment_error ?? 'ninguno') . "\n";
        echo "  Ruta del repositorio: " . ($lastTesis->project_repo_path ?? 'no definida') . "\n";
        
        // Verificar si existe la ruta del proyecto
        if ($lastTesis->project_repo_path) {
            $repoPath = storage_path('app/public/' . $lastTesis->project_repo_path);
            echo "  Ruta completa: " . $repoPath . "\n";
            if (is_dir($repoPath)) {
                echo "  ✓ Directorio del proyecto existe\n";
                
                // Verificar archivos Docker
                $dockerfilePath = $repoPath . '/Dockerfile';
                $composePath = $repoPath . '/docker-compose.yml';
                
                echo "  Dockerfile: " . (file_exists($dockerfilePath) ? "✓ existe" : "✗ no existe") . "\n";
                echo "  docker-compose.yml: " . (file_exists($composePath) ? "✓ existe" : "✗ no existe") . "\n";
                
                if (file_exists($composePath)) {
                    echo "  Contenido de docker-compose.yml:\n";
                    $composeContent = file_get_contents($composePath);
                    $lines = explode("\n", $composeContent);
                    foreach (array_slice($lines, 0, 10) as $line) {
                        echo "    " . $line . "\n";
                    }
                    if (count($lines) > 10) {
                        echo "    ... (archivo truncado)\n";
                    }
                }
            } else {
                echo "  ✗ Directorio del proyecto NO existe\n";
            }
        }
    } else {
        echo "✗ No hay tesis en la base de datos\n";
    }
} catch (\Exception $e) {
    echo "✗ Error al verificar tesis: " . $e->getMessage() . "\n";
}

// 6. Probar DockerService
echo "\n6. Probando DockerService...\n";
try {
    $dockerService = new DockerService();
    $availability = $dockerService->checkDockerAvailability();
    echo "Disponibilidad de Docker: " . ($availability ? "✓ disponible" : "✗ no disponible") . "\n";
} catch (\Exception $e) {
    echo "✗ Error al verificar DockerService: " . $e->getMessage() . "\n";
}

// 7. Verificar permisos
echo "\n7. Verificando permisos del directorio de storage...\n";
$storagePath = storage_path('app/public');
echo "Ruta de storage: " . $storagePath . "\n";
echo "Existe: " . (is_dir($storagePath) ? "✓ sí" : "✗ no") . "\n";
echo "Escribible: " . (is_writable($storagePath) ? "✓ sí" : "✗ no") . "\n";

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";