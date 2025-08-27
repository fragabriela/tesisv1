<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::now();
        
        // Insert carreras
        DB::table('carreras')->insertOrIgnore([
            [
                'id' => 1,
                'nombre' => 'Ingeniería en Sistemas',
                'descripcion' => 'Ingeniería en Sistemas Computacionales',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'nombre' => 'Ingeniería Industrial',
                'descripcion' => 'Ingeniería Industrial y de Sistemas',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);

        // Insert alumnos
        DB::table('alumnos')->insertOrIgnore([
            [
                'id' => 1,
                'nombres' => 'Juan Carlos',
                'apellidos' => 'Pérez López',
                'email' => 'juan.perez@student.edu',
                'telefono' => '555-0123',
                'carrera_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'nombres' => 'María Fernanda',
                'apellidos' => 'Rodríguez Silva',
                'email' => 'maria.rodriguez@student.edu',
                'telefono' => '555-0789',
                'carrera_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);

        // Insert tutores
        DB::table('tutores')->insertOrIgnore([
            [
                'id' => 1,
                'nombres' => 'Dr. María Elena',
                'apellidos' => 'González Ruiz',
                'email' => 'maria.gonzalez@university.edu',
                'telefono' => '555-0456',
                'especialidad' => 'Desarrollo Web',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'nombres' => 'Ing. Carlos Alberto',
                'apellidos' => 'Mendoza Torres',
                'email' => 'carlos.mendoza@university.edu',
                'telefono' => '555-0789',
                'especialidad' => 'Base de Datos',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);

        // Insert tesis
        DB::table('tesis')->insertOrIgnore([
            [
                'id' => 1,
                'titulo' => 'Sistema de Gestión de Pizzería',
                'descripcion' => 'Aplicación web para gestión completa de pizzería con React y Node.js. Incluye gestión de inventarios, pedidos, clientes y reportes.',
                'estado' => 'En desarrollo',
                'fecha_inicio' => '2025-01-15',
                'alumno_id' => 1,
                'tutor_id' => 1,
                'carrera_id' => 1,
                'github_repo' => 'https://github.com/example/pizzeria-project',
                'project_type' => 'react',
                'container_status' => 'stopped',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'titulo' => 'Sistema de Inventarios para Tienda',
                'descripcion' => 'Sistema web para control de inventarios con Laravel y Vue.js. Manejo de productos, proveedores y reportes.',
                'estado' => 'En revisión',
                'fecha_inicio' => '2025-02-01',
                'alumno_id' => 2,
                'tutor_id' => 2,
                'carrera_id' => 1,
                'github_repo' => 'https://github.com/example/inventory-system',
                'project_type' => 'laravel',
                'container_status' => 'stopped',
                'created_at' => $now,
                'updated_at' => $now,
            ]
        ]);

        $this->command->info('✅ Datos de prueba insertados correctamente');
        $this->command->info('📚 Se crearon 2 tesis, 2 alumnos, 2 tutores y 2 carreras');
    }
}