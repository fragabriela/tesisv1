<?php

require_once 'vendor/autoload.php';

// Inicializar Laravel
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Spatie\Permission\Models\Role;

// Obtener el primer usuario
$user = User::first();

if ($user) {
    echo "Usuario encontrado: {$user->email}\n";
    
    // Asignar rol de administrador
    $adminRole = Role::where('name', 'administrador')->first();
    if ($adminRole) {
        if (!$user->hasRole('administrador')) {
            $user->assignRole($adminRole);
            echo "Rol administrador asignado\n";
        } else {
            echo "El usuario ya tiene el rol administrador\n";
        }
        
        // Verificar permiso específico
        if ($user->can('ver documentos')) {
            echo "✅ El usuario tiene permiso para 'ver documentos'\n";
        } else {
            echo "❌ El usuario NO tiene permiso para 'ver documentos'\n";
        }
        
        echo "\nTodos los permisos del usuario:\n";
        foreach ($user->getAllPermissions() as $permiso) {
            if (strpos($permiso->name, 'documento') !== false) {
                echo "- {$permiso->name} ✅\n";
            }
        }
    } else {
        echo "❌ No se encontró el rol administrador\n";
    }
} else {
    echo "❌ No hay usuarios en la base de datos\n";
}

echo "\nPuedes intentar acceder a: http://tesisv1.test/documento\n";