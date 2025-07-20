<?php

namespace Database\Seeders;

use App\Models\Tutor;
use Illuminate\Database\Seeder;

class TutorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tutores = [
            [
                'nombre' => 'Carlos',
                'apellido' => 'Rodríguez',
                'email' => 'carlos.rodriguez@universidad.edu',
                'telefono' => '555-123-4567',
                'especialidad' => 'Desarrollo de Software',
                'biografia' => 'Doctor en Ciencias Computacionales con más de 15 años de experiencia en desarrollo de software y sistemas distribuidos.',
                'activo' => true,
            ],
            [
                'nombre' => 'María',
                'apellido' => 'Gómez',
                'email' => 'maria.gomez@universidad.edu',
                'telefono' => '555-234-5678',
                'especialidad' => 'Inteligencia Artificial',
                'biografia' => 'Investigadora en el campo de la Inteligencia Artificial y Machine Learning con diversas publicaciones internacionales.',
                'activo' => true,
            ],
            [
                'nombre' => 'Luis',
                'apellido' => 'Fernández',
                'email' => 'luis.fernandez@universidad.edu',
                'telefono' => '555-345-6789',
                'especialidad' => 'Redes de Computadoras',
                'biografia' => 'Especialista en diseño e implementación de redes de computadoras y seguridad informática.',
                'activo' => true,
            ],
            [
                'nombre' => 'Ana',
                'apellido' => 'Martínez',
                'email' => 'ana.martinez@universidad.edu',
                'telefono' => '555-456-7890',
                'especialidad' => 'Ingeniería de Software',
                'biografia' => 'Máster en Ingeniería de Software con amplia experiencia en metodologías ágiles y gestión de proyectos.',
                'activo' => true,
            ],
            [
                'nombre' => 'Roberto',
                'apellido' => 'López',
                'email' => 'roberto.lopez@universidad.edu',
                'telefono' => '555-567-8901',
                'especialidad' => 'Bases de Datos',
                'biografia' => 'Experto en diseño, optimización y administración de bases de datos relacionales y NoSQL.',
                'activo' => true,
            ],
            [
                'nombre' => 'Elena',
                'apellido' => 'Torres',
                'email' => 'elena.torres@universidad.edu',
                'telefono' => '555-678-9012',
                'especialidad' => 'Computación Gráfica',
                'biografia' => 'Doctora en Ciencias Computacionales especializada en computación gráfica y desarrollo de videojuegos.',
                'activo' => false,
            ],
        ];

        foreach ($tutores as $tutor) {
            Tutor::firstOrCreate(
                ['email' => $tutor['email']],
                [
                    'nombre' => $tutor['nombre'],
                    'apellido' => $tutor['apellido'],
                    'telefono' => $tutor['telefono'],
                    'especialidad' => $tutor['especialidad'],
                    'biografia' => $tutor['biografia'],
                    'activo' => $tutor['activo'],
                ]
            );
        }
    }
}
