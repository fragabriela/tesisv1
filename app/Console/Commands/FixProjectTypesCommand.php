<?php

namespace App\Console\Commands;

use App\Models\Tesis;
use App\Services\DockerService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FixProjectTypesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'projects:fix-types {--force : Force re-detection of all project types}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix incorrectly detected project types';

    /**
     * The Docker service instance.
     *
     * @var \App\Services\DockerService
     */
    protected $dockerService;

    /**
     * Create a new command instance.
     */
    public function __construct(DockerService $dockerService)
    {
        parent::__construct();
        $this->dockerService = $dockerService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        
        $this->info('Buscando proyectos con tipos incorrectos...');
        
        // Buscar proyectos con repositorios
        $query = Tesis::whereNotNull('project_repo_path');
        
        // Si force=false, solo buscar los que son 'node' y pueden ser Laravel
        if (!$force) {
            $query->where('project_type', 'node');
        }
        
        $projects = $query->get();
        
        if ($projects->isEmpty()) {
            $this->info('No se encontraron proyectos para procesar.');
            return 0;
        }
        
        $this->info("Se encontraron {$projects->count()} proyectos para procesar.");
        
        $fixedCount = 0;
        $errorCount = 0;
        
        foreach ($projects as $tesis) {
            try {
                $this->info("Procesando proyecto ID: {$tesis->id}, Tipo actual: {$tesis->project_type}");
                
                // Verificar que la ruta exista
                $fullPath = storage_path('app/public/' . $tesis->project_repo_path);
                if (!file_exists($fullPath)) {
                    $this->warn("  - Ruta no encontrada: {$fullPath}");
                    continue;
                }
                
                // Detectar tipo de proyecto nuevamente
                $newType = $this->dockerService->detectProjectType($tesis->project_repo_path);
                
                if ($newType !== $tesis->project_type) {
                    $this->info("  - Cambiando tipo de '{$tesis->project_type}' a '{$newType}'");
                    
                    // Actualizar el registro
                    $tesis->project_type = $newType;
                    $tesis->save();
                    
                    $fixedCount++;
                } else {
                    $this->info("  - El tipo detectado '{$newType}' es correcto, no se requiere cambio.");
                }
                
            } catch (\Exception $e) {
                $this->error("Error procesando proyecto ID {$tesis->id}: {$e->getMessage()}");
                Log::error("Error fixing project type for tesis ID {$tesis->id}: {$e->getMessage()}");
                $errorCount++;
            }
        }
        
        $this->info("Proceso completado:");
        $this->info("  - Proyectos corregidos: {$fixedCount}");
        $this->info("  - Errores encontrados: {$errorCount}");
        
        return 0;
    }
}
