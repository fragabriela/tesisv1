<?php

require_once __DIR__ . '/vendor/autoload.php';

// Configurar entorno Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Tesis;
use App\Services\DockerService;

echo "=== PRUEBA DE DESPLIEGUE ===\n\n";

// Obtener la última tesis
$tesis = Tesis::latest()->first();

if (!$tesis) {
    echo "❌ No se encontró ninguna tesis para probar\n";
    exit(1);
}

echo "📋 Probando despliegue de:\n";
echo "   ID: {$tesis->id}\n";
echo "   Título: {$tesis->titulo}\n";
echo "   Estado actual: {$tesis->container_status}\n";
echo "   Ruta: {$tesis->project_repo_path}\n\n";

// Crear instancia del servicio Docker
$dockerService = new DockerService();

echo "🔍 Verificando disponibilidad de Docker...\n";
if (!$dockerService->checkDockerAvailability()) {
    echo "❌ Docker no está disponible\n";
    exit(1);
}
echo "✅ Docker está disponible\n\n";

echo "🚀 Iniciando despliegue del proyecto...\n";
try {
    $result = $dockerService->buildAndRunProject($tesis);
    
    if ($result) {
        echo "✅ ¡Despliegue exitoso!\n\n";
        echo "📊 Resultados:\n";
        echo "   Container ID: " . substr($result['container_id'], 0, 12) . "\n";
        echo "   Estado: {$result['container_status']}\n";
        echo "   URL del proyecto: {$result['project_url']}\n";
        echo "   Puerto externo: {$result['project_config']['external_port']}\n";
        echo "   Nombre del contenedor: {$result['project_config']['container_name']}\n\n";
        
        // Actualizar la tesis con la información del resultado
        $tesis->container_id = $result['container_id'];
        $tesis->container_status = $result['container_status'];
        $tesis->project_url = $result['project_url'];
        $tesis->project_config = $result['project_config'];
        $tesis->deployment_error = null;
        $tesis->last_deployed = now();
        $tesis->save();
        
        echo "💾 Información guardada en la base de datos\n";
        echo "🌐 El proyecto debería estar accesible en: http://localhost:{$result['project_config']['external_port']}\n";
        
    } else {
        echo "❌ Error en el despliegue: No se obtuvo resultado\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Error en el despliegue: " . $e->getMessage() . "\n";
}

echo "\n=== FIN DE LA PRUEBA ===\n";