<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Tesis;
use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== VERIFICACIÓN FINAL DEL ESTADO ===\n\n";

// Obtener la tesis actualizada
$tesis = Tesis::find(21);

if (!$tesis) {
    echo "❌ No se encontró la tesis con ID 21\n";
    exit(1);
}

echo "📋 Estado actual de la tesis:\n";
echo "   ID: {$tesis->id}\n";
echo "   Título: {$tesis->titulo}\n";
echo "   Estado del contenedor: {$tesis->container_status}\n";
echo "   ID del contenedor: {$tesis->container_id}\n";
echo "   URL del proyecto: {$tesis->project_url}\n";
echo "   Error de despliegue: " . ($tesis->deployment_error ?: 'ninguno') . "\n";
echo "   Último despliegue: " . ($tesis->last_deployed ? $tesis->last_deployed->format('Y-m-d H:i:s') : 'nunca') . "\n";

if (!empty($tesis->project_config)) {
    echo "\n🔧 Configuración del proyecto:\n";
    $config = $tesis->project_config;
    if (isset($config['container_name'])) {
        echo "   Nombre del contenedor: {$config['container_name']}\n";
    }
    if (isset($config['internal_port'])) {
        echo "   Puerto interno: {$config['internal_port']}\n";
    }
    if (isset($config['external_port'])) {
        echo "   Puerto externo: {$config['external_port']}\n";
    }
}

// Verificar que el contenedor siga ejecutándose
echo "\n🐳 Estado del contenedor en Docker:\n";
if (!empty($tesis->container_id)) {
    exec("docker ps --filter \"id={$tesis->container_id}\" --format \"{{.Status}}\"", $statusOutput);
    if (!empty($statusOutput)) {
        echo "   ✅ Contenedor activo: {$statusOutput[0]}\n";
    } else {
        echo "   ❌ El contenedor no está ejecutándose\n";
    }
    
    // Obtener información detallada del contenedor
    exec("docker ps --filter \"id={$tesis->container_id}\" --format \"{{.Image}}|{{.Ports}}|{{.Names}}\"", $detailOutput);
    if (!empty($detailOutput)) {
        $details = explode('|', $detailOutput[0]);
        echo "   Imagen: {$details[0]}\n";
        echo "   Puertos: {$details[1]}\n";
        echo "   Nombre: {$details[2]}\n";
    }
} else {
    echo "   ❌ No hay ID de contenedor asignado\n";
}

echo "\n📊 Resumen:\n";
$allGood = true;

if ($tesis->container_status === 'running') {
    echo "   ✅ Estado del contenedor: correcto\n";
} else {
    echo "   ❌ Estado del contenedor: {$tesis->container_status}\n";
    $allGood = false;
}

if (!empty($tesis->container_id)) {
    echo "   ✅ ID del contenedor: asignado\n";
} else {
    echo "   ❌ ID del contenedor: no asignado\n";
    $allGood = false;
}

if (empty($tesis->deployment_error)) {
    echo "   ✅ Sin errores de despliegue\n";
} else {
    echo "   ❌ Error de despliegue: {$tesis->deployment_error}\n";
    $allGood = false;
}

if ($tesis->last_deployed && $tesis->last_deployed->diffInMinutes(now()) < 5) {
    echo "   ✅ Desplegado recientemente\n";
} else {
    echo "   ⚠️  Despliegue no reciente\n";
}

echo "\n" . ($allGood ? "🎉 ¡TODO ESTÁ FUNCIONANDO CORRECTAMENTE!" : "⚠️  Hay algunos problemas que revisar") . "\n";

if ($allGood && !empty($tesis->project_config['external_port'])) {
    echo "\n🌐 Accesos disponibles:\n";
    echo "   • Directo: http://localhost:{$tesis->project_config['external_port']}\n";
    echo "   • A través de la app: {$tesis->project_url}\n";
    echo "   • Proxy de la app: http://tesisv1.test/proyectos/{$tesis->id}\n";
}