<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ForcePermissionRefresh extends Command
{
    protected $signature = 'permissions:force-refresh';
    protected $description = 'Force refresh all user permissions by clearing sessions';

    public function handle()
    {
        // Clear session files (for file-based sessions)
        $sessionPath = storage_path('framework/sessions');
        if (is_dir($sessionPath)) {
            $files = glob($sessionPath . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            $this->info('🗑️ Archivos de sesión eliminados: ' . count($files) . ' archivos');
        }
        
        // Clear permission cache
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        
        // Clear application cache
        \Illuminate\Support\Facades\Artisan::call('cache:clear');
        
        $this->info('✅ Todos los caches y sesiones han sido limpiados.');
        $this->info('📝 Los usuarios deberán iniciar sesión nuevamente para que los cambios de permisos se apliquen.');
        
        return 0;
    }
}