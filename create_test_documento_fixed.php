<?php

require_once 'vendor/autoload.php';

// Inicializar Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Documento;
use App\Models\Tesis;
use App\Models\User;
use App\Models\Alumno;
use App\Models\Tutor;

echo "Verificando datos disponibles...\n";

// Listar tesis disponibles
$tesis = Tesis::all();
echo "Tesis disponibles:\n";
foreach ($tesis as $t) {
    echo "ID: {$t->id}, Título: {$t->titulo}, Alumno ID: {$t->id_alumno}, Tutor ID: {$t->id_tutor}\n";
}

// Listar alumnos
$alumnos = Alumno::take(3)->get();
echo "\nAlumnos disponibles:\n";
foreach ($alumnos as $alumno) {
    echo "ID: {$alumno->id}, Nombre: {$alumno->nombre} {$alumno->apellido}\n";
}

// Listar tutores
$tutores = Tutor::take(3)->get();
echo "\nTutores disponibles:\n";
foreach ($tutores as $tutor) {
    echo "ID: {$tutor->id}, Nombre: {$tutor->nombre} {$tutor->apellido}\n";
}

// Obtener el primer usuario
$user = User::first();

if ($alumnos->count() > 0 && $tutores->count() > 0) {
    // Crear documento de prueba con datos válidos
    $documento = Documento::create([
        'titulo' => 'Documento de Prueba - ' . date('Y-m-d H:i:s'),
        'tesis_id' => $tesis->first() ? $tesis->first()->id : null,
        'alumno_id' => $alumnos->first()->id,
        'tutor_id' => $tutores->first()->id,
        'editado_por' => $user->id,
        'contenido_html' => '<p>Este es un documento de prueba para verificar que DataTables funciona correctamente.</p>',
        'version' => 1,
        'estado' => 'borrador'
    ]);

    echo "\n✅ Documento de prueba creado con ID: {$documento->id}\n";
    echo "Título: {$documento->titulo}\n";
    echo "\nAhora puedes ir a: http://tesisv1.test/documento\n";
} else {
    echo "\n❌ No hay suficientes alumnos o tutores para crear un documento de prueba.\n";
}