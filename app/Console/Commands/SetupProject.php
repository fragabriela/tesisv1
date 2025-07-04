<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupProject extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'project:setup {--fresh : Force fresh migration}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Setup the project with migrations, seeders and storage links';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Iniciando la configuración del proyecto...');
        
        // Clear caches
        $this->info('Limpiando cachés...');
        Artisan::call('cache:clear');
        Artisan::call('config:clear');
        Artisan::call('view:clear');
        Artisan::call('route:clear');
        
        // Run migrations
        $this->info('Ejecutando migraciones...');
        if ($this->option('fresh')) {
            Artisan::call('migrate:fresh', ['--force' => true]);
            $this->info('Base de datos recreada desde cero.');
        } else {
            Artisan::call('migrate', ['--force' => true]);
        }
        
        // Run seeders
        $this->info('Insertando datos iniciales...');
        Artisan::call('db:seed', ['--force' => true]);
        
        // Create storage link
        $this->info('Creando enlace simbólico para almacenamiento...');
        Artisan::call('storage:link');
        
        // Optimize
        $this->info('Optimizando la aplicación...');
        Artisan::call('optimize');
        
        $this->info('✅ Configuración completada con éxito!');
        $this->info('');
        $this->info('Puede acceder al sistema con los siguientes usuarios:');
        $this->info('');
        $this->info('👤 Administrador');
        $this->info('   Email: admin@example.com');
        $this->info('   Password: password');
        $this->info('');
        $this->info('👤 Coordinador');
        $this->info('   Email: coordinador@example.com');
        $this->info('   Password: password');
        $this->info('');
        $this->info('👤 Tutor');
        $this->info('   Email: tutor@example.com');
        $this->info('   Password: password');
        $this->info('');
        $this->info('Para iniciar el servidor de desarrollo ejecute:');
        $this->info('php artisan serve');
        
        return Command::SUCCESS;
    }
}
