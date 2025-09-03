<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Documento;
use App\Models\Tesis;

echo "=== PRUEBA DE CREACIÓN DE DOCUMENTO ===\n";

// Verificar que existan tesis
$tesis = Tesis::all();
echo "Total de tesis disponibles: " . $tesis->count() . "\n";

if ($tesis->count() == 0) {
    echo "❌ ERROR: No hay tesis disponibles para asociar el documento\n";
    exit(1);
}

// Mostrar las tesis disponibles
echo "\nTesis disponibles:\n";
foreach ($tesis as $t) {
    echo "ID: {$t->id} - Título: {$t->titulo}\n";
}

// Simular la creación de un documento
$primeraTeesis = $tesis->first();
echo "\nUsando la primera tesis: ID {$primeraTeesis->id}\n";

try {
    $documento = Documento::create([
        'titulo' => 'Test Documento desde script',
        'descripcion' => 'Descripción de prueba',
        'tesis_id' => $primeraTeesis->id,
        'alumno_id' => $primeraTeesis->alumno_id,
        'tutor_id' => $primeraTeesis->tutor_id,
        'editado_por' => 1, // Usuario ID 1 (admin)
        'archivo_original' => null,
        'contenido_html' => '<h1>Test Documento desde script</h1><p>Descripción de prueba</p>',
        'contenido_texto' => 'Test Documento desde script Descripción de prueba',
        'version' => 1,
        'estado' => 'borrador'
    ]);

    echo "✅ ÉXITO: Documento creado con ID: {$documento->id}\n";
    echo "Título: {$documento->titulo}\n";
    echo "Tesis asociada: {$documento->tesis_id}\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR al crear documento: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== FIN DE PRUEBA ===\n";