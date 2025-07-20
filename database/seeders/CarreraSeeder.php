<?php

namespace Database\Seeders;

use App\Models\Carrera;
use Illuminate\Database\Seeder;

class CarreraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $carreras = [
            [
                'nombre' => 'Ingeniería en Sistemas Computacionales',
                'descripcion' => 'Carrera enfocada en el desarrollo de software, bases de datos y redes de computadoras.',
                'activo' => true,
            ],
            [
                'nombre' => 'Ingeniería Civil',
                'descripcion' => 'Carrera orientada al diseño, construcción y mantenimiento de infraestructuras.',
                'activo' => true,
            ],
            [
                'nombre' => 'Licenciatura en Administración de Empresas',
                'descripcion' => 'Programa académico para formar profesionales en la gestión y dirección de organizaciones.',
                'activo' => true,
            ],
            [
                'nombre' => 'Medicina',
                'descripcion' => 'Formación de profesionales de la salud con conocimientos científicos y éticos.',
                'activo' => true,
            ],
            [
                'nombre' => 'Derecho',
                'descripcion' => 'Carrera orientada al estudio de las leyes y el sistema jurídico.',
                'activo' => true,
            ],
            [
                'nombre' => 'Psicología',
                'descripcion' => 'Estudio del comportamiento humano y los procesos mentales.',
                'activo' => true,
            ],
            [
                'nombre' => 'Arquitectura',
                'descripcion' => 'Formación en diseño, planificación y construcción de espacios habitables.',
                'activo' => true,
            ],
            [
                'nombre' => 'Contaduría Pública',
                'descripcion' => 'Programa enfocado en la contabilidad y auditoría de organizaciones.',
                'activo' => false,
            ]
        ];

        foreach ($carreras as $carrera) {
            Carrera::firstOrCreate(
                ['nombre' => $carrera['nombre']],
                [
                    'descripcion' => $carrera['descripcion'],
                    'activo' => $carrera['activo'],
                ]
            );
        }
    }
}
