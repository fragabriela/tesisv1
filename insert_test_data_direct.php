<?php
// Simple script to insert test data directly using SQLite
$dbPath = __DIR__ . '/database/database.sqlite';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== INSERTANDO DATOS DE PRUEBA ===\n";
    
    $now = date('Y-m-d H:i:s');
    
    // Insert carreras
    $pdo->exec("INSERT OR IGNORE INTO carreras (id, nombre, descripcion, created_at, updated_at) VALUES 
        (1, 'Ingeniería en Sistemas', 'Ingeniería en Sistemas Computacionales', '$now', '$now'),
        (2, 'Ingeniería Industrial', 'Ingeniería Industrial y de Sistemas', '$now', '$now')");
    echo "✅ Carreras insertadas\n";

    // Insert alumnos
    $pdo->exec("INSERT OR IGNORE INTO alumnos (id, nombres, apellidos, email, telefono, carrera_id, created_at, updated_at) VALUES 
        (1, 'Juan Carlos', 'Pérez López', 'juan.perez@student.edu', '555-0123', 1, '$now', '$now'),
        (2, 'María Fernanda', 'Rodríguez Silva', 'maria.rodriguez@student.edu', '555-0789', 1, '$now', '$now')");
    echo "✅ Alumnos insertados\n";

    // Insert tutores
    $pdo->exec("INSERT OR IGNORE INTO tutores (id, nombres, apellidos, email, telefono, especialidad, created_at, updated_at) VALUES 
        (1, 'Dr. María Elena', 'González Ruiz', 'maria.gonzalez@university.edu', '555-0456', 'Desarrollo Web', '$now', '$now'),
        (2, 'Ing. Carlos Alberto', 'Mendoza Torres', 'carlos.mendoza@university.edu', '555-0789', 'Base de Datos', '$now', '$now')");
    echo "✅ Tutores insertados\n";

    // Insert tesis
    $pdo->exec("INSERT OR IGNORE INTO tesis (id, titulo, descripcion, estado, fecha_inicio, alumno_id, tutor_id, carrera_id, github_repo, project_type, container_status, created_at, updated_at) VALUES 
        (1, 'Sistema de Gestión de Pizzería', 'Aplicación web para gestión completa de pizzería con React y Node.js. Incluye gestión de inventarios, pedidos, clientes y reportes.', 'En desarrollo', '2025-01-15', 1, 1, 1, 'https://github.com/guidoDomingo/pizzapp', 'react', 'stopped', '$now', '$now'),
        (2, 'Sistema de Inventarios para Tienda', 'Sistema web para control de inventarios con Laravel y Vue.js. Manejo de productos, proveedores y reportes.', 'En revisión', '2025-02-01', 2, 2, 1, 'https://github.com/example/inventory-system', 'laravel', 'stopped', '$now', '$now')");
    echo "✅ Tesis insertadas\n";

    // Insert some fake backups
    $pdo->exec("INSERT OR IGNORE INTO project_backups (id, tesis_id, description, type, version, file_path, file_size, backed_up_at, created_at, updated_at) VALUES 
        (1, 1, 'Backup inicial del proyecto pizzería', 'full', '1.0.0', 'project-backups/pizzeria_backup_2025-08-24_001.zip', 1024000, '$now', '$now', '$now'),
        (2, 1, 'Backup con funcionalidades básicas completadas', 'full', '1.1.0', 'project-backups/pizzeria_backup_2025-08-24_002.zip', 1536000, '$now', '$now', '$now'),
        (3, 2, 'Backup inicial sistema de inventarios', 'full', '1.0.0', 'project-backups/inventory_backup_2025-08-24_001.zip', 2048000, '$now', '$now', '$now')");
    echo "✅ Backups de prueba insertados\n";

    // Verify data
    $stmt = $pdo->prepare("SELECT t.id, t.titulo, COUNT(pb.id) as backup_count 
                           FROM tesis t 
                           LEFT JOIN project_backups pb ON t.id = pb.tesis_id 
                           GROUP BY t.id, t.titulo");
    $stmt->execute();
    $tesis = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\n🔍 Verificación:\n";
    foreach ($tesis as $t) {
        echo "  - Tesis ID {$t['id']}: {$t['titulo']} ({$t['backup_count']} backups)\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=== DATOS INSERTADOS EXITOSAMENTE ===\n";
echo "🌐 Accede a: http://tesisv1.test\n";
echo "👤 Usuario: admin@example.com\n";
echo "🔑 Password: password\n";
echo "📋 Ve a Proyectos → Tesis ID 1 → Desplegar para probar backups\n";