<?php
// Simple script to check database without Storage/finfo
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

echo "=== VERIFICACIÓN DE DATOS TESISV1 ===\n";

try {
    // Get database connection
    $pdo = new PDO('sqlite:' . __DIR__ . '/database/database.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check tesis
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tesis");
    $stmt->execute();
    $tesisCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "📚 Tesis registradas: $tesisCount\n";
    
    if ($tesisCount > 0) {
        $stmt = $pdo->prepare("SELECT id, titulo, estado FROM tesis LIMIT 5");
        $stmt->execute();
        $tesis = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\n🔍 Primeras tesis:\n";
        foreach ($tesis as $t) {
            echo "  - ID: {$t['id']} | {$t['titulo']} | Estado: {$t['estado']}\n";
        }
    }
    
    // Check users
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $userCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "\n👥 Usuarios registrados: $userCount\n";
    
    if ($userCount > 0) {
        $stmt = $pdo->prepare("SELECT id, name, email FROM users LIMIT 3");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\n🔍 Usuarios disponibles:\n";
        foreach ($users as $u) {
            echo "  - {$u['name']} ({$u['email']})\n";
        }
    }
    
    // Check project_backups table
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM project_backups");
        $stmt->execute();
        $backupCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "\n💾 Backups existentes: $backupCount\n";
    } catch (Exception $e) {
        echo "\n⚠️  Tabla project_backups no encontrada o error: " . $e->getMessage() . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== FIN VERIFICACIÓN ===\n";