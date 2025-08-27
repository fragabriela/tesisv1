<?php
// Script para verificar permisos de usuario
$dbPath = __DIR__ . '/database/database.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== VERIFICACIÓN DE PERMISOS ===\n";
    
    // Verificar usuarios
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = 'admin@example.com'");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "✅ Usuario admin encontrado: ID {$admin['id']}\n";
        
        // Verificar roles
        $stmt = $pdo->prepare("
            SELECT r.name as role_name 
            FROM model_has_roles mhr 
            JOIN roles r ON mhr.role_id = r.id 
            WHERE mhr.model_id = ? AND mhr.model_type = 'App\\\\Models\\\\User'
        ");
        $stmt->execute([$admin['id']]);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "🎭 Roles asignados:\n";
        foreach ($roles as $role) {
            echo "  - {$role['role_name']}\n";
        }
        
        // Verificar permisos específicos
        $stmt = $pdo->prepare("SELECT name FROM permissions WHERE name LIKE '%proyectos%'");
        $stmt->execute();
        $permissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n🔑 Permisos relacionados con proyectos:\n";
        foreach ($permissions as $perm) {
            echo "  - {$perm['name']}\n";
        }
        
        // Verificar si tiene permiso específico
        $stmt = $pdo->prepare("SELECT p.name FROM permissions p WHERE p.name = 'desplegar proyectos'");
        $stmt->execute();
        $deployPermission = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($deployPermission) {
            echo "\n✅ Permiso 'desplegar proyectos' existe\n";
        } else {
            echo "\n❌ Permiso 'desplegar proyectos' NO existe\n";
        }
        
    } else {
        echo "❌ Usuario admin no encontrado\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN VERIFICACIÓN ===\n";