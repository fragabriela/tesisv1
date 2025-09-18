<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class CreateSampleRoles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'roles:create-samples';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create sample roles with specific permissions';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creando roles de ejemplo...');

        // Crear rol Editor
        $editorRole = Role::firstOrCreate(['name' => 'editor']);
        $editorRole->syncPermissions([
            'ver dashboard',
            'ver tesis',
            'editar tesis',
            'ver documentos',
            'editar documentos',
            'crear documentos',
            'ver proyectos'
        ]);
        $this->info('✓ Rol "editor" creado con permisos básicos de edición');

        // Crear rol Supervisor
        $supervisorRole = Role::firstOrCreate(['name' => 'supervisor']);
        $supervisorRole->syncPermissions([
            'ver dashboard',
            'ver alumnos',
            'ver carreras',
            'ver tutores',
            'ver tesis',
            'crear tesis',
            'editar tesis',
            'ver documentos',
            'crear documentos',
            'editar documentos',
            'ver proyectos',
            'crear proyectos',
            'editar proyectos'
        ]);
        $this->info('✓ Rol "supervisor" creado con permisos de supervisión');

        // Crear rol Asistente
        $asistenteRole = Role::firstOrCreate(['name' => 'asistente']);
        $asistenteRole->syncPermissions([
            'ver dashboard',
            'ver alumnos',
            'ver carreras',
            'ver tutores',
            'exportar alumnos',
            'exportar carreras',
            'exportar tutores',
            'exportar tesis'
        ]);
        $this->info('✓ Rol "asistente" creado con permisos de consulta y exportación');

        // Crear rol Auditor (solo lectura)
        $auditorRole = Role::firstOrCreate(['name' => 'auditor']);
        $auditorRole->syncPermissions([
            'ver dashboard',
            'ver alumnos',
            'ver carreras',
            'ver tutores',
            'ver tesis',
            'ver documentos',
            'ver proyectos'
        ]);
        $this->info('✓ Rol "auditor" creado con permisos de solo lectura');

        $this->info("\n🎉 ¡Roles de ejemplo creados exitosamente!");
        $this->info("\nRoles disponibles:");
        $this->table(['Rol', 'Permisos'], [
            ['administrador', 'Todos los permisos'],
            ['coordinador', 'Gestión académica completa'],
            ['tutor', 'Gestión de tesis y documentos asignados'],
            ['alumno', 'Gestión de tesis y proyectos propios'],
            ['editor', 'Edición de tesis y documentos'],
            ['supervisor', 'Supervisión de procesos académicos'],
            ['asistente', 'Consulta y exportación de datos'],
            ['auditor', 'Solo lectura de toda la información']
        ]);
    }
}
