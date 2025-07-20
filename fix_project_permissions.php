<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

echo "Agregando permisos de Proyectos...\n";

// Crear permisos adicionales para proyectos
$projectPermissions = [
    'ver proyectos',
    'crear proyectos',
    'editar proyectos',
    'eliminar proyectos',
    'monitorear proyectos',
    'configurar proyectos',
    'desplegar proyectos',
    'exportar proyectos'
];

foreach ($projectPermissions as $permission) {
    Permission::firstOrCreate(['name' => $permission]);
    echo "✓ Permiso creado: {$permission}\n";
}

// Obtener el rol administrador
$adminRole = Role::where('name', 'administrador')->first();

if ($adminRole) {
    // Asignar nuevos permisos al rol administrador
    $adminRole->givePermissionTo($projectPermissions);
    echo "✓ Permisos de proyectos asignados al rol administrador\n";
    
    // Verificar que todos los usuarios administradores tengan estos permisos
    $users = User::role('administrador')->get();
    foreach ($users as $user) {
        echo "✓ Usuario {$user->email} tiene acceso a proyectos\n";
    }
} else {
    echo "❌ No se encontró el rol administrador\n";
}

echo "\n🎉 ¡Proceso completado! Ahora deberías ver las opciones de Proyectos en el menú.\n";
echo "Recarga la página del dashboard para ver los cambios.\n";
