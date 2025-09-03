<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Documento;
use PhpOffice\PhpWord\IOFactory;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

echo "=== TODOS LOS DOCUMENTOS ===\n";
$documentos = Documento::all();
foreach ($documentos as $doc) {
    echo "ID: {$doc->id} - Título: {$doc->titulo} - Archivo: " . ($doc->archivo_original ?? 'Sin archivo') . "\n";
}
echo "\n";

$docId = 7;
$documento = Documento::find($docId);

if (!$documento) {
    echo "Documento $docId no encontrado\n";
    exit(1);
}

echo "=== DIAGNÓSTICO DOCUMENTO #$docId ===\n";
echo "Título: " . $documento->titulo . "\n";
echo "Descripción: " . $documento->descripcion . "\n";
echo "Ruta archivo: " . ($documento->archivo_original ?? 'Sin archivo') . "\n";
echo "Longitud del contenido actual: " . strlen($documento->contenido_html ?? '') . "\n";
echo "Contenido actual (primeros 500 chars):\n";
echo substr($documento->contenido_html ?? '', 0, 500) . "\n";
echo "\n";

if ($documento->archivo_original) {
    $rutaCompleta = storage_path('app/public/' . $documento->archivo_original);
    echo "Ruta completa del archivo: $rutaCompleta\n";
    echo "Archivo existe: " . (file_exists($rutaCompleta) ? 'SÍ' : 'NO') . "\n";
    
    if (file_exists($rutaCompleta)) {
        echo "Tamaño del archivo: " . filesize($rutaCompleta) . " bytes\n";
        echo "Tipo de archivo detectado: " . mime_content_type($rutaCompleta) . "\n";
        
        echo "\n=== PROBANDO CONVERSIÓN ===\n";
        try {
            $phpWord = IOFactory::load($rutaCompleta);
            echo "✓ PhpWord puede cargar el archivo\n";
            
            $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');
            echo "✓ HTML Writer creado\n";
            
            $tempHtml = tempnam(sys_get_temp_dir(), 'word_to_html_test_') . '.html';
            $htmlWriter->save($tempHtml);
            echo "✓ HTML generado en: $tempHtml\n";
            
            $htmlContent = file_get_contents($tempHtml);
            echo "Tamaño HTML generado: " . strlen($htmlContent) . " chars\n";
            echo "HTML generado (primeros 1000 chars):\n";
            echo substr($htmlContent, 0, 1000) . "\n\n";
            
            // Procesar el HTML como lo hace el controller
            if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $htmlContent, $matches)) {
                $bodyContent = $matches[1];
                echo "Contenido del body (primeros 500 chars):\n";
                echo substr($bodyContent, 0, 500) . "\n\n";
                
                $textoPlano = strip_tags($bodyContent);
                $textoLimpio = trim(preg_replace('/\s+/', ' ', $textoPlano));
                echo "Texto plano extraído (primeros 500 chars):\n";
                echo substr($textoLimpio, 0, 500) . "\n\n";
                echo "Longitud del texto plano: " . strlen($textoLimpio) . " chars\n";
            }
            
            unlink($tempHtml);
            
        } catch (Exception $e) {
            echo "❌ Error en la conversión: " . $e->getMessage() . "\n";
            echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
        }
    }
}

echo "\n=== FIN DIAGNÓSTICO ===\n";