<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Documento;

echo "=== Verificando Documento 16 ===\n";

$doc = Documento::find(16);

if ($doc) {
    echo "ID: " . $doc->id . "\n";
    echo "Título: " . $doc->titulo . "\n";
    echo "Contenido HTML (longitud): " . strlen($doc->contenido_html ?? '') . " caracteres\n";
    echo "Archivo original: " . ($doc->archivo_original ?? 'NULL') . "\n";
    echo "Contenido HTML (primeros 300 chars):\n";
    echo substr($doc->contenido_html ?? 'VACÍO', 0, 300) . "\n";
    echo "---\n";
} else {
    echo "❌ Documento 16 no encontrado\n";
}

// Verificar últimos documentos
echo "\n=== Últimos 3 documentos ===\n";
$ultimosDoc = Documento::orderBy('id', 'desc')->take(3)->get();

foreach ($ultimosDoc as $d) {
    echo "ID {$d->id}: {$d->titulo} - HTML: " . strlen($d->contenido_html ?? '') . " chars\n";
}