<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Tesis;
use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== ACTUALIZANDO INFORMACIÓN DEL CONTENEDOR ===\n\n";

// Obtener la tesis
$tesis = Tesis::find(21);

if (!$tesis) {
    echo "❌ No se encontró la tesis con ID 21\n";
    exit(1);
}

// Obtener información del contenedor actual
exec('docker ps --filter "name=eudptmou2s-gt1oB" --format "{{.ID}}|{{.Ports}}"', $containerInfo);

if (empty($containerInfo)) {
    echo "❌ No se encontró el contenedor\n";
    exit(1);
}

$infoParts = explode('|', $containerInfo[0]);
$containerId = $infoParts[0];
$ports = $infoParts[1];

// Extraer el puerto
$port = null;
if (preg_match('/0.0.0.0:(\d+)/', $ports, $matches)) {
    $port = $matches[1];
}

if (!$port) {
    echo "❌ No se pudo extraer el puerto del contenedor\n";
    exit(1);
}

echo "📋 Información del contenedor:\n";
echo "   Container ID: $containerId\n";
echo "   Puerto: $port\n";
echo "   Puertos completos: $ports\n\n";

// Actualizar la base de datos
$tesis->container_id = $containerId;
$tesis->container_status = 'running';
$tesis->project_config = [
    'container_name' => 'eudptmou2s-gt1oB',
    'internal_port' => 80,
    'external_port' => $port,
];
$tesis->deployment_error = null;
$tesis->last_deployed = now();
$tesis->save();

echo "✅ Base de datos actualizada correctamente\n";
echo "   Nuevo puerto: $port\n";
echo "   Container ID: $containerId\n";
echo "\n🌐 Accesos disponibles:\n";
echo "   • Directo: http://localhost:$port\n";
echo "   • A través de la app: http://tesisv1.test/proyectos/21\n";