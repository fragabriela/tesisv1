<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

use App\Models\Tesis;
use App\Models\Documento;
use Illuminate\Support\Facades\Storage;

echo "===== TEST DE CREACIÓN DE DOCUMENTOS CON WORD =====\n";

// Verificar que existe al menos una tesis
$tesis = Tesis::first();
if (!$tesis) {
    echo "❌ ERROR: No hay tesis disponibles\n";
    exit;
}

echo "✅ Tesis disponible: {$tesis->titulo}\n";

// Crear un archivo de prueba (simulando un Word)
$testContent = "Este es un contenido de prueba para simular un documento Word.";
$testFile = storage_path('app/public/test_document.txt');
file_put_contents($testFile, $testContent);

echo "✅ Archivo de prueba creado: $testFile\n";

// Simular la creación de documento con archivo
try {
    $documento = Documento::create([
        'titulo' => 'Test con archivo Word',
        'descripcion' => 'Documento de prueba subido desde archivo',
        'tesis_id' => $tesis->id,
        'alumno_id' => $tesis->alumno_id ?? null,
        'tutor_id' => $tesis->tutor_id ?? null,
        'editado_por' => 1, // Usuario ID 1
        'archivo_original' => 'test_document.txt',
        'contenido_html' => '<p>' . $testContent . '</p>',
        'contenido_texto' => $testContent,
        'version' => 1,
        'estado' => 'borrador'
    ]);

    echo "✅ ÉXITO: Documento creado con ID: {$documento->id}\n";
    echo "Título: {$documento->titulo}\n";
    echo "Archivo: {$documento->archivo_original}\n";
    echo "Estado: {$documento->estado}\n";
    
    // Limpiar archivo de prueba
    unlink($testFile);
    echo "✅ Archivo de prueba eliminado\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    if (file_exists($testFile)) {
        unlink($testFile);
    }
}

// Verificar total de documentos
$totalDocumentos = Documento::count();
echo "\n📊 Total de documentos en base de datos: $totalDocumentos\n";

echo "\n===== FIN DEL TEST =====\n";
?>