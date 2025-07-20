<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Tesis;

echo "Docker Diagnostics Tool\n";
echo "=====================\n\n";

// Verificar si Docker está instalado
echo "1. Verificando instalación de Docker:\n";
exec('docker --version 2>&1', $dockerOutput, $dockerReturnVar);
if ($dockerReturnVar === 0) {
    echo "   ✓ Docker está instalado: " . $dockerOutput[0] . "\n";
} else {
    echo "   ✗ Docker no está instalado o no es accesible. Error: " . implode("\n", $dockerOutput) . "\n";
    echo "\n   Solución: Instale Docker Desktop desde https://www.docker.com/products/docker-desktop/\n";
    exit(1);
}

// Verificar si Docker está en ejecución
echo "\n2. Verificando si Docker está en ejecución:\n";
exec('docker info 2>&1', $infoOutput, $infoReturnVar);
if ($infoReturnVar === 0) {
    echo "   ✓ Docker está en ejecución correctamente\n";
} else {
    echo "   ✗ Docker no está en ejecución. Error: " . implode("\n", $infoOutput) . "\n";
    echo "\n   Solución: Inicie Docker Desktop desde el menú de inicio o la bandeja del sistema\n";
    exit(1);
}

// Verificar Docker Compose
echo "\n3. Verificando Docker Compose:\n";
// Probar con el nuevo comando (con espacio)
exec('docker compose version 2>&1', $composeOutput, $composeReturnVar);
$newComposeAvailable = $composeReturnVar === 0;

// Si no está disponible, probar con el comando antiguo (con guión)
if (!$newComposeAvailable) {
    exec('docker-compose --version 2>&1', $composeOutput, $composeReturnVar);
}

if ($composeReturnVar === 0) {
    echo "   ✓ Docker Compose está disponible: " . $composeOutput[0] . "\n";
    if ($newComposeAvailable) {
        echo "     (Usando el formato nuevo 'docker compose')\n";
    } else {
        echo "     (Usando el formato antiguo 'docker-compose')\n";
    }
} else {
    echo "   ✗ Docker Compose no está disponible. Error: " . implode("\n", $composeOutput) . "\n";
    echo "\n   Solución: Reinstale Docker Desktop que incluye Docker Compose\n";
    exit(1);
}

// Crear un Dockerfile y docker-compose.yml de prueba
echo "\n4. Probando creación de archivos Docker:\n";
$testDir = storage_path('app/docker_test');
if (!file_exists($testDir)) {
    mkdir($testDir, 0777, true);
}

// Crear un Dockerfile de prueba
$testDockerfile = <<<EOT
FROM php:8.1-apache

RUN echo "Test Dockerfile working" > /var/www/html/index.html
EXPOSE 80
EOT;

file_put_contents($testDir . '/Dockerfile', $testDockerfile);

// Crear un docker-compose.yml de prueba
$testCompose = <<<EOT
version: '3'
services:
  app:
    build: .
    container_name: test-container
    ports:
      - "8081:80"
EOT;

file_put_contents($testDir . '/docker-compose.yml', $testCompose);

if (file_exists($testDir . '/Dockerfile') && file_exists($testDir . '/docker-compose.yml')) {
    echo "   ✓ Archivos Docker creados correctamente en: $testDir\n";
} else {
    echo "   ✗ No se pudieron crear los archivos de prueba\n";
    exit(1);
}

// Probar la construcción y ejecución de un contenedor
echo "\n5. Probando construcción y ejecución de un contenedor:\n";
echo "   Intentando construir y ejecutar un contenedor de prueba...\n";

$cmd = "cd $testDir && " . ($newComposeAvailable ? 'docker compose' : 'docker-compose') . " up -d 2>&1";
exec($cmd, $buildOutput, $buildReturnVar);

if ($buildReturnVar === 0) {
    echo "   ✓ Contenedor de prueba construido y ejecutado correctamente\n";
    
    // Verificar que el contenedor está en ejecución
    exec("docker ps --filter name=test-container --format '{{.ID}}'", $containerIdOutput);
    if (!empty($containerIdOutput)) {
        echo "   ✓ Contenedor en ejecución con ID: " . $containerIdOutput[0] . "\n";
        
        // Detener y eliminar el contenedor de prueba
        exec("docker stop test-container && docker rm test-container", $cleanupOutput);
        echo "   ✓ Contenedor de prueba detenido y eliminado\n";
    } else {
        echo "   ✗ El contenedor no aparece en la lista de contenedores en ejecución\n";
    }
} else {
    echo "   ✗ Error al construir y ejecutar el contenedor: " . implode("\n", $buildOutput) . "\n";
    echo "\n   Esto puede indicar un problema con la instalación de Docker o permisos insuficientes\n";
}

// Limpiar archivos de prueba
echo "\n6. Limpiando archivos de prueba...\n";
unlink($testDir . '/Dockerfile');
unlink($testDir . '/docker-compose.yml');
rmdir($testDir);
echo "   ✓ Archivos de prueba eliminados\n";

echo "\nDiagnóstico completado.\n";
echo "Si todos los pasos están marcados con ✓, Docker está configurado correctamente.\n";
echo "Ahora debería poder desplegar proyectos sin problemas.\n";
