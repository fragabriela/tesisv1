<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

// Crear o verificar permisos
$permissions = [
    'desplegar proyectos',
    'gestionar proyectos',
    'ver proyectos'
];

foreach ($permissions as $permissionName) {
    $permission = Permission::firstOrCreate(['name' => $permissionName]);
    echo "✅ Permiso: {$permissionName}\n";
}

// Asegurar que admin tiene todos los permisos
$admin = User::where('email', 'admin@example.com')->first();
if ($admin) {
    echo "✅ Usuario admin encontrado: {$admin->email}\n";
    
    // Verificar si tiene rol admin
    if (!$admin->hasRole('admin')) {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $admin->assignRole($adminRole);
        echo "✅ Rol admin asignado\n";
    }
    
    // Asignar todos los permisos al rol admin
    $adminRole = Role::where('name', 'admin')->first();
    if ($adminRole) {
        foreach ($permissions as $permissionName) {
            $permission = Permission::where('name', $permissionName)->first();
            if ($permission && !$adminRole->hasPermissionTo($permission)) {
                $adminRole->givePermissionTo($permission);
                echo "✅ Permiso '{$permissionName}' asignado al rol admin\n";
            }
        }
    }
    
    // Verificar permisos finales
    echo "\n🔑 Permisos del usuario:\n";
    foreach ($admin->getAllPermissions() as $permission) {
        echo "  - {$permission->name}\n";
    }
    
} else {
    echo "❌ Usuario admin no encontrado\n";
}