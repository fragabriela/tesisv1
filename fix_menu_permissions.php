<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

echo "Creando permisos y roles para el menú...\n";

// Crear todos los permisos necesarios
$permissions = [
    'ver dashboard', 
    'ver carreras', 'crear carreras', 'editar carreras', 'eliminar carreras', 'exportar carreras',
    'ver alumnos', 'crear alumnos', 'editar alumnos', 'eliminar alumnos', 'exportar alumnos',
    'ver tutores', 'crear tutores', 'editar tutores', 'eliminar tutores', 'exportar tutores',
    'ver tesis', 'crear tesis', 'editar tesis', 'eliminar tesis', 'exportar tesis'
];

foreach ($permissions as $permission) {
    Permission::firstOrCreate(['name' => $permission]);
    echo "✓ Permiso creado: {$permission}\n";
}

// Crear rol administrador
$adminRole = Role::firstOrCreate(['name' => 'administrador']);
echo "✓ Rol 'administrador' creado\n";

// Asignar todos los permisos al rol administrador
$adminRole->syncPermissions($permissions);
echo "✓ Todos los permisos asignados al rol administrador\n";

// Obtener todos los usuarios y darles el rol de administrador
$users = User::all();
foreach ($users as $user) {
    $user->assignRole('administrador');
    echo "✓ Usuario {$user->email} ahora tiene rol de administrador\n";
}

echo "\n🎉 ¡Proceso completado! Ahora deberías ver todas las opciones del menú.\n";
echo "Recarga la página del dashboard para ver los cambios.\n";
