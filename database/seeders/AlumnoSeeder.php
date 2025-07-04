<?php

namespace Database\Seeders;

use App\Models\Alumno;
use App\Models\Carrera;
use Illuminate\Database\Seeder;

class AlumnoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get all carreras
        $carreras = Carrera::where('activo', true)->get();
        
        if ($carreras->isEmpty()) {
            $this->command->info('No hay carreras activas en la base de datos. Ejecuta CarreraSeeder primero.');
            return;
        }

        $alumnos = [
            [
                'nombre' => 'Juan',
                'apellido' => 'Pérez',
                'email' => 'juan.perez@estudiante.edu',
                'telefono' => '555-111-2222',
                'cedula' => '123456789',
                'matricula' => 'A20210001',
                'fecha_nacimiento' => '1998-05-15',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Ana',
                'apellido' => 'González',
                'email' => 'ana.gonzalez@estudiante.edu',
                'telefono' => '555-222-3333',
                'cedula' => '234567890',
                'matricula' => 'A20210002',
                'fecha_nacimiento' => '1997-08-22',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Pedro',
                'apellido' => 'Ramírez',
                'email' => 'pedro.ramirez@estudiante.edu',
                'telefono' => '555-333-4444',
                'cedula' => '345678901',
                'matricula' => 'A20210003',
                'fecha_nacimiento' => '1999-02-10',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Laura',
                'apellido' => 'Sánchez',
                'email' => 'laura.sanchez@estudiante.edu',
                'telefono' => '555-444-5555',
                'cedula' => '456789012',
                'matricula' => 'A20210004',
                'fecha_nacimiento' => '1998-11-30',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Carlos',
                'apellido' => 'Mendoza',
                'email' => 'carlos.mendoza@estudiante.edu',
                'telefono' => '555-555-6666',
                'cedula' => '567890123',
                'matricula' => 'A20210005',
                'fecha_nacimiento' => '1997-07-18',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'María',
                'apellido' => 'López',
                'email' => 'maria.lopez@estudiante.edu',
                'telefono' => '555-666-7777',
                'cedula' => '678901234',
                'matricula' => 'A20210006',
                'fecha_nacimiento' => '1999-03-25',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Jorge',
                'apellido' => 'Díaz',
                'email' => 'jorge.diaz@estudiante.edu',
                'telefono' => '555-777-8888',
                'cedula' => '789012345',
                'matricula' => 'A20210007',
                'fecha_nacimiento' => '1998-01-05',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Sofía',
                'apellido' => 'Torres',
                'email' => 'sofia.torres@estudiante.edu',
                'telefono' => '555-888-9999',
                'cedula' => '890123456',
                'matricula' => 'A20210008',
                'fecha_nacimiento' => '1997-10-12',
                'estado' => 'activo',
            ],
            [
                'nombre' => 'Luis',
                'apellido' => 'Hernández',
                'email' => 'luis.hernandez@estudiante.edu',
                'telefono' => '555-999-0000',
                'cedula' => '901234567',
                'matricula' => 'A20210009',
                'fecha_nacimiento' => '1999-06-20',
                'estado' => 'inactivo',
            ],
            [
                'nombre' => 'Gabriela',
                'apellido' => 'Castro',
                'email' => 'gabriela.castro@estudiante.edu',
                'telefono' => '555-000-1111',
                'cedula' => '012345678',
                'matricula' => 'A20210010',
                'fecha_nacimiento' => '1998-09-08',
                'estado' => 'egresado',
            ],
        ];

        foreach ($alumnos as $alumno) {
            // Assign a random carrera to each alumno
            $alumno['id_carrera'] = $carreras->random()->id;
            Alumno::create($alumno);
        }
    }
}
