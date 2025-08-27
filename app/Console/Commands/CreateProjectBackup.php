<?php

namespace App\Console\Commands;

use App\Models\Tesis;
use App\Services\ProjectBackupService;
use Illuminate\Console\Command;

class CreateProjectBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:create 
                            {tesis_id : ID de la tesis} 
                            {--type=full : Tipo de backup (full, database)} 
                            {--description= : Descripción del backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Crear backup de un proyecto específico';

    /**
     * Execute the console command.
     */
    public function handle(ProjectBackupService $backupService)
    {
        $tesisId = $this->argument('tesis_id');
        $type = $this->option('type');
        $description = $this->option('description');

        $tesis = Tesis::find($tesisId);
        
        if (!$tesis) {
            $this->error("Tesis con ID {$tesisId} no encontrada");
            return 1;
        }

        $this->info("Creando backup {$type} para: {$tesis->titulo}");
        
        try {
            if ($type === 'full') {
                $backup = $backupService->createFullBackup($tesis, $description);
            } else {
                $this->error("Tipo de backup '{$type}' no soportado aún");
                return 1;
            }

            $this->info("✅ Backup creado exitosamente!");
            $this->table(
                ['Campo', 'Valor'],
                [
                    ['ID', $backup->id],
                    ['Nombre', $backup->backup_name],
                    ['Archivo', $backup->file_name],
                    ['Tamaño', $backup->formatted_size],
                    ['Tipo', $backup->backup_type],
                    ['Fecha', $backup->backed_up_at->format('Y-m-d H:i:s')],
                ]
            );

            if ($backup->fileExists()) {
                $this->info("📁 Archivo ubicado en: {$backup->full_path}");
            } else {
                $this->warn("⚠️ El archivo de backup no se encontró en el disco");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error creando backup: {$e->getMessage()}");
            return 1;
        }

        return 0;
    }
}
