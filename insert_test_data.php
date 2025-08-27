<?php
// Simple script to insert test data
$dbPath = __DIR__ . '/database/database.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== INSERTANDO DATOS DE PRUEBA ===\n";
    
    // Check if we already have data
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM tesis");
    $stmt->execute();
    $tesisCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($tesisCount == 0) {
        echo "📚 Insertando tesis de prueba...\n";
        
        // First, we need to insert dependencies (carreras, alumnos, tutores)
        
        // Insert carrera
        $pdo->exec("INSERT OR IGNORE INTO carreras (id, nombre, descripcion, created_at, updated_at) VALUES 
            (1, 'Ingeniería en Sistemas', 'Ingeniería en Sistemas Computacionales', datetime('now'), datetime('now'))");
        
        // Insert alumno  
        $pdo->exec("INSERT OR IGNORE INTO alumnos (id, nombres, apellidos, email, telefono, carrera_id, created_at, updated_at) VALUES 
            (1, 'Juan Carlos', 'Pérez López', 'juan.perez@student.edu', '555-0123', 1, datetime('now'), datetime('now'))");
        
        // Insert tutor
        $pdo->exec("INSERT OR IGNORE INTO tutores (id, nombres, apellidos, email, telefono, especialidad, created_at, updated_at) VALUES 
            (1, 'Dr. María Elena', 'González Ruiz', 'maria.gonzalez@university.edu', '555-0456', 'Desarrollo Web', datetime('now'), datetime('now'))");
        
        // Insert tesis
        $pdo->exec("INSERT OR IGNORE INTO tesis (id, titulo, descripcion, estado, fecha_inicio, alumno_id, tutor_id, carrera_id, github_repo, project_type, created_at, updated_at) VALUES 
            (1, 'Sistema de Gestión de Pizzería', 'Aplicación web para gestión completa de pizzería con React y Node.js', 'En desarrollo', '2025-01-15', 1, 1, 1, 'https://github.com/example/pizzeria-project', 'react', datetime('now'), datetime('now'))");
        
        echo "✅ Datos insertados correctamente\n";
    } else {
        echo "📚 Ya existen $tesisCount tesis en la base de datos\n";
    }
    
    // Show current data
    $stmt = $pdo->prepare("SELECT t.id, t.titulo, t.estado, a.nombres || ' ' || a.apellidos as alumno
                           FROM tesis t
                           LEFT JOIN alumnos a ON t.alumno_id = a.id
                           LIMIT 5");
    $stmt->execute();
    $tesis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n🔍 Tesis disponibles:\n";
    foreach ($tesis as $t) {
        echo "  - ID: {$t['id']} | {$t['titulo']} | Alumno: {$t['alumno']} | Estado: {$t['estado']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== LISTO PARA PROBAR ===\n";
echo "🌐 Abre http://tesisv1.test en tu navegador\n";
echo "👤 Usuario: admin@example.com\n";
echo "🔑 Password: password\n";
echo "📋 Ve a la sección de Tesis/Proyectos para probar backups\n";