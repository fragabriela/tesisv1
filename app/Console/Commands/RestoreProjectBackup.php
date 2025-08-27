<?php

namespace App\Console\Commands;

use App\Models\ProjectBackup;
use App\Services\ProjectBackupService;
use Illuminate\Console\Command;

class RestoreProjectBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore 
                            {backup_id : ID del backup a restaurar}
                            {--container= : ID del contenedor Docker de destino}
                            {--port= : Puerto para acceder al proyecto restaurado}
                            {--force : Forzar restauración sin confirmación}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restaurar un backup de proyecto en un contenedor Docker';

    protected $projectBackupService;

    public function __construct(ProjectBackupService $projectBackupService)
    {
        parent::__construct();
        $this->projectBackupService = $projectBackupService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $backupId = $this->argument('backup_id');
        $containerId = $this->option('container');
        $port = $this->option('port');
        $force = $this->option('force');

        // Buscar el backup
        $backup = ProjectBackup::with('tesis')->find($backupId);
        
        if (!$backup) {
            $this->error("Backup con ID {$backupId} no encontrado");
            return 1;
        }

        // Verificar que el archivo existe
        if (!$backup->fileExists()) {
            $this->error("Archivo de backup no encontrado: {$backup->file_path}");
            return 1;
        }

        $this->info("Backup encontrado:");
        $this->table(['Campo', 'Valor'], [
            ['ID', $backup->id],
            ['Tesis', $backup->tesis->titulo ?? 'N/A'],
            ['Tipo', $backup->backup_type],
            ['Tamaño', $backup->formatted_size],
            ['Fecha', $backup->created_at->format('d/m/Y H:i:s')],
            ['Descripción', $backup->description ?? 'Sin descripción']
        ]);

        // Confirmar restauración si no se usa --force
        if (!$force) {
            if (!$this->confirm('¿Deseas continuar con la restauración?')) {
                $this->info('Restauración cancelada');
                return 0;
            }
        }

        // Mostrar información del contenedor si se proporciona
        if ($containerId) {
            $this->info("Contenedor destino: {$containerId}");
        }

        if ($port) {
            $this->info("Puerto de acceso: {$port}");
        }

        $this->info('Iniciando restauración...');
        
        try {
            // Crear barra de progreso
            $bar = $this->output->createProgressBar(4);
            
            $bar->setFormat('verbose');
            $bar->start();

            // Paso 1: Extraer backup
            $this->line("\nExtrayendo backup...");
            $bar->advance();

            // Paso 2: Preparar contenedor
            $this->line("Preparando contenedor Docker...");
            $bar->advance();

            // Paso 3: Restaurar archivos
            $this->line("Restaurando archivos y base de datos...");
            $result = $this->projectBackupService->restoreBackupToContainer(
                $backup,
                $containerId,
                $port
            );
            $bar->advance();

            // Paso 4: Finalizar
            $this->line("Finalizando restauración...");
            $bar->advance();
            $bar->finish();

            if ($result['success']) {
                $this->newLine();
                $this->info('✅ Restauración completada exitosamente!');
                
                if (isset($result['container_id'])) {
                    $this->info("Contenedor creado: {$result['container_id']}");
                }
                
                if (isset($result['access_url'])) {
                    $this->info("URL de acceso: {$result['access_url']}");
                }

                if (isset($result['credentials'])) {
                    $this->info('Credenciales por defecto:');
                    foreach ($result['credentials'] as $role => $creds) {
                        $this->line("  {$role}: {$creds['email']} / {$creds['password']}");
                    }
                }

            } else {
                $this->error('❌ Error durante la restauración: ' . ($result['message'] ?? 'Error desconocido'));
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Error durante la restauración: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
