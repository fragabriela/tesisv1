<?php

echo "=== Test de verificación de archivo ===\n\n";

$testPath = 'C:\laragon\www\tesisv1\storage\app\public\repos\HigfxI9k01\Dockerfile';

echo "Ruta a verificar: $testPath\n\n";

echo "file_exists(): " . (file_exists($testPath) ? "✅ TRUE" : "❌ FALSE") . "\n";
echo "is_file(): " . (is_file($testPath) ? "✅ TRUE" : "❌ FALSE") . "\n";
echo "is_readable(): " . (is_readable($testPath) ? "✅ TRUE" : "❌ FALSE") . "\n";
echo "filesize(): " . (file_exists($testPath) ? filesize($testPath) . " bytes" : "N/A") . "\n";

// Verificar el directorio padre
$parentDir = dirname($testPath);
echo "\nDirectorio padre: $parentDir\n";
echo "is_dir(): " . (is_dir($parentDir) ? "✅ TRUE" : "❌ FALSE") . "\n";

// Listar archivos en el directorio
if (is_dir($parentDir)) {
    echo "\nArchivos en el directorio:\n";
    $files = scandir($parentDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "- $file\n";
        }
    }
}

// Verificar rutas con barras diferentes
echo "\n=== Verificación con diferentes tipos de barras ===\n";
$pathForward = str_replace('\\', '/', $testPath);
$pathBackward = str_replace('/', '\\', $testPath);

echo "Ruta con barras hacia adelante: $pathForward\n";
echo "file_exists(): " . (file_exists($pathForward) ? "✅ TRUE" : "❌ FALSE") . "\n";

echo "\nRuta con barras hacia atrás: $pathBackward\n";
echo "file_exists(): " . (file_exists($pathBackward) ? "✅ TRUE" : "❌ FALSE") . "\n";

?>