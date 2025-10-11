<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Services\DockerService;
use App\Models\Tesis;
use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== PRUEBA DE DESPLIEGUE REAL ===\n\n";

// Crear instancia del servicio Docker
$dockerService = new DockerService();

// Obtener la tesis con ID 21 (la que estamos probando)
$tesis = Tesis::find(21);

if (!$tesis) {
    echo "❌ No se encontró la tesis con ID 21\n";
    exit(1);
}

echo "📋 Información de la tesis:\n";
echo "   ID: {$tesis->id}\n";
echo "   Título: {$tesis->titulo}\n";
echo "   Estado actual: {$tesis->container_status}\n";
echo "   Ruta del repo: {$tesis->project_repo_path}\n\n";

// Verificar que Docker esté disponible
echo "1. Verificando Docker Desktop...\n";
if (!$dockerService->checkDockerAvailability()) {
    echo "❌ Docker no está disponible\n";
    exit(1);
}
echo "✅ Docker está disponible\n\n";

// Verificar que los archivos necesarios existan
$repoPath = storage_path('app/public/' . $tesis->project_repo_path);
echo "2. Verificando archivos del proyecto...\n";
echo "   Ruta completa: $repoPath\n";

if (!file_exists($repoPath)) {
    echo "❌ El directorio del proyecto no existe\n";
    exit(1);
}
echo "   ✅ Directorio existe\n";

if (!file_exists($repoPath . '/Dockerfile')) {
    echo "   ❌ Dockerfile no existe\n";
    exit(1);
}
echo "   ✅ Dockerfile existe\n";

if (!file_exists($repoPath . '/docker-compose.yml')) {
    echo "   ❌ docker-compose.yml no existe\n";
    exit(1);
}
echo "   ✅ docker-compose.yml existe\n\n";

// Detener contenedor anterior si existe
if (!empty($tesis->container_id)) {
    echo "3. Deteniendo contenedor anterior...\n";
    $dockerService->stopContainer($tesis->container_id);
    echo "   ✅ Contenedor anterior detenido\n\n";
}

// Intentar el despliegue
echo "4. Ejecutando despliegue...\n";
try {
    $result = $dockerService->buildAndRunProject($tesis);
    
    if ($result) {
        echo "✅ ¡Despliegue exitoso!\n\n";
        echo "📊 Resultados del despliegue:\n";
        echo "   Container ID: {$result['container_id']}\n";
        echo "   Estado: {$result['container_status']}\n";
        echo "   Puerto: {$result['port']}\n";
        echo "   URL del proyecto: {$result['project_url']}\n";
        
        if (isset($result['project_config'])) {
            echo "   Configuración:\n";
            echo "     - Nombre contenedor: {$result['project_config']['container_name']}\n";
            echo "     - Puerto interno: {$result['project_config']['internal_port']}\n";
            echo "     - Puerto externo: {$result['project_config']['external_port']}\n";
        }
        
        // Actualizar la tesis con los resultados
        $tesis->container_id = $result['container_id'];
        $tesis->container_status = $result['container_status'];
        $tesis->project_url = $result['project_url'];
        $tesis->project_config = $result['project_config'];
        $tesis->deployment_error = null;
        $tesis->last_deployed = now();
        $tesis->save();
        
        echo "\n✅ Base de datos actualizada con la información del contenedor\n";
        
        // Verificar que el contenedor esté realmente corriendo
        echo "\n5. Verificando estado del contenedor...\n";
        exec("docker ps --filter \"id={$result['container_id']}\" --format \"{{.Status}}\"", $statusOutput);
        if (!empty($statusOutput)) {
            echo "   ✅ Contenedor está corriendo: {$statusOutput[0]}\n";
        } else {
            echo "   ❌ El contenedor no aparece en la lista de contenedores activos\n";
        }
        
        // Intentar una petición HTTP al proyecto
        echo "\n6. Probando conectividad al proyecto...\n";
        $testUrl = "http://localhost:{$result['port']}";
        echo "   Probando URL: $testUrl\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 400) {
            echo "   ✅ Proyecto responde correctamente (HTTP $httpCode)\n";
            echo "   📄 Primeros 200 caracteres de la respuesta:\n";
            echo "   " . substr(strip_tags($response), 0, 200) . "...\n";
        } else {
            echo "   ⚠️  Proyecto no responde correctamente (HTTP $httpCode)\n";
            echo "   Esto puede ser normal si el proyecto está aún iniciando\n";
        }
        
    } else {
        echo "❌ El despliegue falló - buildAndRunProject retornó null\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "❌ Error durante el despliegue: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n🎉 ¡PRUEBA DE DESPLIEGUE COMPLETADA EXITOSAMENTE!\n";
echo "\nPuede acceder al proyecto en: http://localhost:{$result['port']}\n";
echo "O a través de la aplicación web en: {$result['project_url']}\n";