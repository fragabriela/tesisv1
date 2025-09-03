<?php
require_once 'vendor/autoload.php';

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\DocumentoController;
use App\Models\Tesis;

// Simular datos de formulario
$_POST = [
    'tesis_id' => '1', 
    'descripcion' => 'Test desde simulación directa',
    '_token' => 'test-token'
];

// Simular REQUEST_METHOD
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "===== SIMULACIÓN DE ENVÍO DE FORMULARIO =====\n";
echo "Datos simulados:\n";
print_r($_POST);

// Verificar si existe la tesis
$tesis = DB::table('tesis')->where('id', 1)->first();
if (!$tesis) {
    echo "\n❌ ERROR: No existe la tesis con ID 1\n";
    exit;
}

echo "\n✅ Tesis encontrada: " . $tesis->titulo . "\n";

// Verificar campos requeridos
$required_fields = ['tesis_id', 'descripcion'];
$missing_fields = [];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        $missing_fields[] = $field;
    }
}

if (!empty($missing_fields)) {
    echo "\n❌ Campos faltantes: " . implode(', ', $missing_fields) . "\n";
    exit;
}

echo "\n✅ Todos los campos requeridos están presentes\n";

// Simular la creación del documento
try {
    $data = [
        'titulo' => 'Documento de ' . $tesis->titulo,
        'descripcion' => $_POST['descripcion'],
        'tesis_id' => $_POST['tesis_id'],
        'contenido_html' => '<p>' . $_POST['descripcion'] . '</p>',
        'version' => 1,
        'estado' => 'borrador',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    echo "\n===== DATOS A INSERTAR =====\n";
    print_r($data);
    
    $id = DB::table('documentos')->insertGetId($data);
    
    echo "\n✅ ÉXITO: Documento creado con ID: $id\n";
    echo "Título: " . $data['titulo'] . "\n";
    echo "Estado: " . $data['estado'] . "\n";
    echo "Tesis: " . $tesis->titulo . "\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR al crear documento: " . $e->getMessage() . "\n";
}

echo "\n===== FIN DE SIMULACIÓN =====\n";
?>