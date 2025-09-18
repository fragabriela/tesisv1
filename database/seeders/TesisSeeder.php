<?php

namespace Database\Seeders;

use App\Models\Alumno;
use App\Models\Tesis;
use App\Models\Tutor;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TesisSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get alumnos and tutores that have user accounts
        $alumnos = Alumno::whereNotNull('user_id')->get();
        $tutores = Tutor::whereNotNull('user_id')->get();
        
        if ($alumnos->isEmpty()) {
            $this->command->info('No hay alumnos con usuarios asignados. Ejecuta UserRelationshipsSeeder primero.');
            return;
        }
        
        if ($tutores->isEmpty()) {
            $this->command->info('No hay tutores con usuarios asignados. Ejecuta UserRelationshipsSeeder primero.');
            return;
        }

        $tesis = [
            [
                'titulo' => 'Implementación de un sistema de gestión académica usando Laravel',
                'descripcion' => 'Este proyecto consiste en desarrollar un sistema completo para la gestión de información académica incluyendo estudiantes, profesores, cursos y calificaciones utilizando el framework Laravel.',
                'fecha_inicio' => Carbon::now()->subMonths(6),
                'fecha_fin' => Carbon::now()->addMonths(2),
                'estado' => 'en_progreso',
                'observaciones' => 'El proyecto avanza según lo planeado. Se ha completado el módulo de gestión de estudiantes.',
            ],
            [
                'titulo' => 'Análisis de algoritmos de machine learning para la predicción del rendimiento académico',
                'descripcion' => 'Investigación sobre la aplicación de diversos algoritmos de machine learning para predecir el rendimiento académico de los estudiantes basado en datos históricos.',
                'fecha_inicio' => Carbon::now()->subMonths(8),
                'fecha_fin' => Carbon::now()->subMonths(1),
                'estado' => 'completado',
                'calificacion' => 95,
                'observaciones' => 'Proyecto finalizado con excelentes resultados. Se logró una precisión del 87% en las predicciones.',
            ],
            [
                'titulo' => 'Desarrollo de una aplicación móvil para el aprendizaje de matemáticas',
                'descripcion' => 'Diseño e implementación de una aplicación móvil interactiva para facilitar el aprendizaje de conceptos matemáticos a nivel universitario.',
                'fecha_inicio' => Carbon::now()->subMonths(3),
                'estado' => 'en_progreso',
                'observaciones' => 'Se ha completado la fase de diseño y se está avanzando con la implementación.',
            ],
            [
                'titulo' => 'Sistema de reconocimiento facial para control de asistencia',
                'descripcion' => 'Implementación de un sistema que utiliza reconocimiento facial para automatizar el registro de asistencia en clases universitarias.',
                'fecha_inicio' => Carbon::now()->subMonths(5),
                'estado' => 'pendiente',
                'observaciones' => 'Pendiente de revisión del comité académico para iniciar desarrollo.',
            ],
            [
                'titulo' => 'Estudio comparativo de frameworks JavaScript para el desarrollo de aplicaciones web',
                'descripcion' => 'Análisis detallado de los principales frameworks JavaScript como React, Angular y Vue, evaluando su rendimiento, curva de aprendizaje y aplicabilidad en proyectos académicos.',
                'fecha_inicio' => Carbon::now()->subMonths(10),
                'fecha_fin' => Carbon::now()->subMonths(2),
                'estado' => 'completado',
                'calificacion' => 88,
                'observaciones' => 'Proyecto concluido satisfactoriamente. Destacan los análisis de rendimiento realizados.',
            ],
            [
                'titulo' => 'Implementación de un chatbot para servicios universitarios',
                'descripcion' => 'Desarrollo de un asistente virtual basado en NLP para responder consultas frecuentes sobre servicios universitarios como inscripciones, horarios y trámites.',
                'fecha_inicio' => Carbon::now()->subMonths(4),
                'estado' => 'en_progreso',
                'observaciones' => 'Se ha completado la recopilación de datos y se está trabajando en el entrenamiento del modelo.',
            ],
        ];

        // Assign each tesis to specific alumnos and tutores with user accounts
        $tesisAsignadas = 0;
        foreach ($tesis as $index => $t) {
            if ($tesisAsignadas >= $alumnos->count()) break;
            
            $alumno = $alumnos[$tesisAsignadas];
            $tutor = $tutores->random(); // Random tutor assignment
            
            $t['alumno_id'] = $alumno->id;
            $t['tutor_id'] = $tutor->id;
            
            Tesis::create($t);
            
            $this->command->info("Tesis '{$t['titulo']}' asignada a {$alumno->nombre} {$alumno->apellido}");
            $tesisAsignadas++;
        }
    }
}
