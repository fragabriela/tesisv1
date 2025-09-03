<?php

require_once 'vendor/autoload.php';

// Inicializar Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Documento;
use App\Models\Tesis;
use App\Models\User;

echo "Creando documento de prueba...\n";

// Obtener la primera tesis
$tesis = Tesis::first();
if (!$tesis) {
    echo "❌ No hay tesis disponibles. Necesitas crear una tesis primero.\n";
    exit;
}

// Obtener el usuario admin
$user = User::first();
if (!$user) {
    echo "❌ No hay usuarios disponibles.\n";
    exit;
}

// Crear documento de prueba
$documento = Documento::create([
    'titulo' => 'Documento de Prueba - ' . date('Y-m-d H:i:s'),
    'tesis_id' => $tesis->id,
    'alumno_id' => $tesis->id_alumno,
    'tutor_id' => $tesis->id_tutor,
    'editado_por' => $user->id,
    'contenido_html' => '<p>Este es un documento de prueba para verificar que DataTables funciona correctamente.</p>',
    'version' => 1,
    'estado' => 'borrador'
]);

echo "✅ Documento de prueba creado con ID: {$documento->id}\n";
echo "Título: {$documento->titulo}\n";
echo "Tesis: {$tesis->titulo}\n";
echo "\nAhora puedes ir a: http://tesisv1.test/documento\n";