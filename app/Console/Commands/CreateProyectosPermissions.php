<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CreateProyectosPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'proyectos:permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear permisos para el módulo de proyectos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Creando permisos para proyectos...');
        
        $this->call('db:seed', [
            '--class' => 'Database\\Seeders\\ProyectosPermissionSeeder'
        ]);
        
        $this->info('¡Permisos creados con éxito!');
        
        return Command::SUCCESS;
    }
}
