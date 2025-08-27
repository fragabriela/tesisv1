<?php
// Script simple para configurar permisos sin usar Storage
require_once __DIR__ . '/vendor/autoload.php';

// Configurar Laravel manualmente
$app = require_once __DIR__ . '/bootstrap/app.php';

// Configurar base de datos manualmente
$dbPath = __DIR__ . '/database/database.sqlite';

try {
    // Conectar a la base de datos directamente
    $dsn = 'sqlite:' . $dbPath;
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Conectado a la base de datos\n";
    
    // Crear permisos si no existen
    $permissions = [
        'desplegar proyectos',
        'gestionar proyectos', 
        'ver proyectos',
        'crear proyectos',
        'configurar proyectos',
        'eliminar proyectos',
        'monitorear proyectos'
    ];
    
    foreach ($permissions as $permName) {
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO permissions (name, guard_name, created_at, updated_at) VALUES (?, 'web', datetime('now'), datetime('now'))");
        $stmt->execute([$permName]);
        echo "✅ Permiso: $permName\n";
    }
    
    // Crear rol admin si no existe
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO roles (name, guard_name, created_at, updated_at) VALUES ('admin', 'web', datetime('now'), datetime('now'))");
    $stmt->execute();
    echo "✅ Rol: admin\n";
    
    // Obtener IDs
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = 'admin@example.com'");
    $stmt->execute();
    $adminUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $stmt = $pdo->prepare("SELECT id FROM roles WHERE name = 'admin'");
    $stmt->execute();
    $adminRole = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($adminUser && $adminRole) {
        // Asignar rol al usuario
        $stmt = $pdo->prepare("INSERT OR IGNORE INTO model_has_roles (role_id, model_type, model_id) VALUES (?, 'App\\Models\\User', ?)");
        $stmt->execute([$adminRole['id'], $adminUser['id']]);
        echo "✅ Rol asignado al usuario admin\n";
        
        // Asignar todos los permisos al rol admin
        foreach ($permissions as $permName) {
            $stmt = $pdo->prepare("SELECT id FROM permissions WHERE name = ?");
            $stmt->execute([$permName]);
            $permission = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($permission) {
                $stmt = $pdo->prepare("INSERT OR IGNORE INTO role_has_permissions (permission_id, role_id) VALUES (?, ?)");
                $stmt->execute([$permission['id'], $adminRole['id']]);
                echo "✅ Permiso '$permName' asignado al rol admin\n";
            }
        }
        
        echo "\n🎉 Configuración completada!\n";
        echo "Usuario admin@example.com ahora tiene todos los permisos necesarios.\n";
        
    } else {
        echo "❌ No se encontró el usuario admin o el rol admin\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}