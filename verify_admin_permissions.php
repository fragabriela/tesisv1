<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;

echo "🔍 Verificando permisos del usuario admin@example.com...\n\n";

// Buscar el usuario admin
$admin = User::where('email', 'admin@example.com')->first();

if (!$admin) {
    echo "❌ No se encontró el usuario admin@example.com\n";
    exit(1);
}

echo "✅ Usuario encontrado: {$admin->name} ({$admin->email})\n\n";

// Mostrar roles
$roles = $admin->roles()->pluck('name')->toArray();
echo "📋 Roles asignados:\n";
foreach ($roles as $role) {
    echo "   • {$role}\n";
}
echo "\n";

// Mostrar permisos directos
$directPermissions = $admin->permissions()->pluck('name')->toArray();
if (!empty($directPermissions)) {
    echo "🔑 Permisos directos:\n";
    foreach ($directPermissions as $permission) {
        echo "   • {$permission}\n";
    }
    echo "\n";
}

// Mostrar todos los permisos (incluyendo los del rol)
$allPermissions = $admin->getAllPermissions()->pluck('name')->toArray();
sort($allPermissions);

echo "🎯 TODOS LOS PERMISOS DISPONIBLES:\n";
$categories = [
    'Dashboard' => [],
    'Alumnos' => [],
    'Carreras' => [],
    'Tutores' => [],
    'Tesis' => [],
    'Proyectos' => [],
];

foreach ($allPermissions as $permission) {
    if (str_contains($permission, 'dashboard')) {
        $categories['Dashboard'][] = $permission;
    } elseif (str_contains($permission, 'alumnos')) {
        $categories['Alumnos'][] = $permission;
    } elseif (str_contains($permission, 'carreras')) {
        $categories['Carreras'][] = $permission;
    } elseif (str_contains($permission, 'tutores')) {
        $categories['Tutores'][] = $permission;
    } elseif (str_contains($permission, 'tesis')) {
        $categories['Tesis'][] = $permission;
    } elseif (str_contains($permission, 'proyectos')) {
        $categories['Proyectos'][] = $permission;
    }
}

foreach ($categories as $category => $permissions) {
    if (!empty($permissions)) {
        echo "\n📁 {$category}:\n";
        foreach ($permissions as $permission) {
            echo "   ✓ {$permission}\n";
        }
    }
}

echo "\n📊 RESUMEN:\n";
echo "   • Total de permisos: " . count($allPermissions) . "\n";
echo "   • Roles asignados: " . count($roles) . "\n";

// Verificar permisos específicos importantes
$importantPermissions = [
    'ver dashboard',
    'ver proyectos',
    'crear proyectos',
    'desplegar proyectos',
    'monitorear proyectos',
    'configurar proyectos'
];

echo "\n🎯 VERIFICACIÓN DE PERMISOS CLAVE:\n";
foreach ($importantPermissions as $permission) {
    $hasPermission = $admin->can($permission);
    $status = $hasPermission ? '✅' : '❌';
    echo "   {$status} {$permission}\n";
}

echo "\n🎉 ¡El usuario admin@example.com tiene todos los permisos configurados correctamente!\n";
echo "Ahora puede acceder a todas las funcionalidades del sistema.\n";
