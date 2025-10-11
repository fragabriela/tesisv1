<?php
require_once __DIR__ . '/vendor/autoload.php';

use App\Services\ProjectBackupService;
use Illuminate\Foundation\Application;

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== PRUEBA DE RESTAURACIÓN AUTOMÁTICA DE BASE DE DATOS ===\n\n";

// Crear instancia del servicio
$backupService = new ProjectBackupService();

// ID del contenedor actual
$containerId = '49a61a9b6212';

// Vamos a crear un backup de muestra de la base de datos tesisv1 existente
echo "1. Creando backup de la base de datos tesisv1 existente...\n";

// Crear backup de la base de datos actual
$backupPath = storage_path('app/temp/tesisv1_backup_test.sql');
if (!file_exists(dirname($backupPath))) {
    mkdir(dirname($backupPath), 0755, true);
}

// Exportar la base de datos tesisv1 usando mysqldump
$dumpCommand = "\"C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe\" -h 127.0.0.1 -u root tesisv1 > \"$backupPath\"";
exec($dumpCommand, $dumpOutput, $dumpReturn);

if ($dumpReturn !== 0) {
    echo "❌ Error creando backup de tesisv1\n";
    echo "Comando: $dumpCommand\n";
    echo "Output: " . implode("\n", $dumpOutput) . "\n";
    exit(1);
}

if (!file_exists($backupPath) || filesize($backupPath) < 100) {
    echo "❌ El backup no se creó correctamente o está vacío\n";
    exit(1);
}

echo "✅ Backup creado: $backupPath (" . round(filesize($backupPath)/1024, 2) . " KB)\n\n";

// 2. Detectar tipo de base de datos
echo "2. Detectando tipo de base de datos del backup...\n";
$dbInfo = $backupService->detectDatabaseType($backupPath);
print_r($dbInfo);
echo "\n";

if ($dbInfo['confidence'] < 50) {
    echo "❌ No se pudo detectar el tipo de base de datos\n";
    exit(1);
}

echo "✅ Tipo detectado: {$dbInfo['type']} (confianza: {$dbInfo['confidence']}%)\n\n";

// 3. Probar restauración automática
echo "3. Ejecutando restauración automática...\n";
$result = $backupService->restoreBackupWithAutoDetection($containerId, $backupPath, 'tesisv1');

echo "📊 Resultado de la restauración:\n";
echo "   Éxito: " . ($result['success'] ? 'SÍ' : 'NO') . "\n";
echo "   Mensaje: {$result['message']}\n";

if (isset($result['db_info'])) {
    echo "   Tipo de BD detectado: {$result['db_info']['type']}\n";
}

if (isset($result['connection_config'])) {
    echo "   Configuración de conexión:\n";
    foreach ($result['connection_config'] as $key => $value) {
        echo "     $key: $value\n";
    }
}

// 4. Verificar que la conexión funcione
echo "\n4. Verificando conexión a la base de datos en el contenedor...\n";
exec("docker exec $containerId bash -c 'cd /var/www/html && php artisan tinker --execute=\"DB::connection()->getPdo(); echo \\\"Conexión exitosa\\\";\"'", $testOutput, $testReturn);

if ($testReturn === 0) {
    echo "✅ Conexión a la base de datos funciona correctamente\n";
    echo "Output: " . implode("\n", $testOutput) . "\n";
} else {
    echo "❌ Error en la conexión a la base de datos\n";
    echo "Output: " . implode("\n", $testOutput) . "\n";
}

// 5. Verificar que hay datos en la base de datos
echo "\n5. Verificando datos en la base de datos...\n";
exec("docker exec $containerId bash -c 'cd /var/www/html && php artisan tinker --execute=\"echo \\\"Usuarios: \\\" . DB::table(\\\"users\\\")->count();\"'", $countOutput, $countReturn);

if ($countReturn === 0) {
    echo "✅ Consulta a la base de datos exitosa\n";
    echo "Output: " . implode("\n", $countOutput) . "\n";
} else {
    echo "⚠️  No se pudo consultar la tabla users (normal si no existe)\n";
}

echo "\n🎉 PRUEBA COMPLETADA\n";

// Limpiar archivo temporal
if (file_exists($backupPath)) {
    unlink($backupPath);
    echo "🧹 Archivo temporal eliminado\n";
}