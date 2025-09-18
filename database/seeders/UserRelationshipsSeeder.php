<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Alumno;
use App\Models\Tutor;
use Spatie\Permission\Models\Role;

class UserRelationshipsSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Asignando relaciones usuario-alumno y usuario-tutor...');

        // Obtener roles
        $tutorRole = Role::where('name', 'tutor')->first();
        $alumnoRole = Role::where('name', 'alumno')->first();

        // Obtener algunos alumnos y tutores
        $alumnos = Alumno::take(3)->get();
        $tutores = Tutor::take(2)->get();

        // Crear usuarios para algunos alumnos
        foreach ($alumnos as $index => $alumno) {
            $user = User::create([
                'name' => $alumno->nombre . ' ' . $alumno->apellido,
                'email' => 'alumno' . ($index + 1) . '@test.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);

            $user->assignRole($alumnoRole);
            
            // Actualizar el alumno con el user_id
            $alumno->update(['user_id' => $user->id]);

            $this->command->info("Usuario creado para alumno: {$alumno->nombre} {$alumno->apellido}");
        }

        // Crear usuarios para algunos tutores
        foreach ($tutores as $index => $tutor) {
            $user = User::create([
                'name' => $tutor->nombre . ' ' . $tutor->apellido,
                'email' => 'tutor' . ($index + 1) . '@test.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);

            $user->assignRole($tutorRole);
            
            // Actualizar el tutor con el user_id
            $tutor->update(['user_id' => $user->id]);

            $this->command->info("Usuario creado para tutor: {$tutor->nombre} {$tutor->apellido}");
        }

        $this->command->info('Relaciones usuario-alumno y usuario-tutor asignadas correctamente.');
    }
}