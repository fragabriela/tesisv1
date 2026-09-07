<?php

namespace App\Http\Controllers;

use App\Models\Tesis;
use App\Models\ProjectBackup;
use App\Services\ProjectBackupService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Exception;

class ProjectBackupController extends Controller
{
    protected ProjectBackupService $backupService;

    public function __construct(ProjectBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Display a listing of backups for a specific tesis.
     */
    public function index(Request $request, Tesis $tesis)
    {
        $backups = $tesis->backups()
            ->when($request->type, function ($query, $type) {
                return $query->where('backup_type', $type);
            })
            ->orderBy('backed_up_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $backups,
            'tesis' => [
                'id' => $tesis->id,
                'titulo' => $tesis->titulo,
                'container_id' => $tesis->container_id,
                'project_type' => $tesis->project_type,
            ]
        ]);
    }

    /**
     * Store a newly created backup.
     */
    public function store(Request $request, Tesis $tesis)
    {
        $request->validate([
            'type' => 'required|in:database,full',
            'description' => 'nullable|string|max:255',
        ]);

        try {
            if ($request->type === 'full') {
                $backup = $this->backupService->createFullBackup($tesis, $request->description);
            } else {
                $databaseBackup = $this->backupService->createDatabaseBackup($tesis, $request->description);
                
                // Crear registro de backup de BD
                $backup = ProjectBackup::create([
                    'tesis_id' => $tesis->id,
                    'backup_name' => 'db_' . now()->format('Y_m_d_H_i_s'),
                    'backup_type' => 'database',
                    'file_path' => 'project-backups',
                    'file_name' => 'database_backup.sql',
                    'file_size' => strlen($databaseBackup),
                    'metadata' => ['project_type' => $tesis->project_type],
                    'description' => $request->description ?? "Backup de base de datos",
                    'is_automatic' => false,
                    'backed_up_at' => now(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Backup creado exitosamente',
                'data' => $backup->load('tesis'),
            ], 201);

        } catch (Exception $e) {
            Log::error('Error creando backup', [
                'tesis_id' => $tesis->id,
                'type' => $request->type,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified backup.
     */
    public function show(Tesis $tesis, ProjectBackup $backup)
    {
        if ($backup->tesis_id !== $tesis->id) {
            return response()->json([
                'success' => false,
                'message' => 'Backup no encontrado para esta tesis',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $backup->load('tesis'),
            'file_exists' => $backup->fileExists(),
        ]);
    }

    /**
     * Download the specified backup.
     */
    public function download(Tesis $tesis, ProjectBackup $backup)
    {
        if ($backup->tesis_id !== $tesis->id) {
            return response()->json([
                'success' => false,
                'message' => 'Backup no encontrado para esta tesis',
            ], 404);
        }

        if (!$backup->fileExists()) {
            return response()->json([
                'success' => false,
                'message' => 'El archivo de backup no existe',
            ], 404);
        }

        return response()->download(
            $backup->full_path,
            $backup->file_name,
            [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $backup->file_name . '"',
            ]
        );
    }

    /**
     * Restore backup to container.
     */
    public function restore(Request $request, Tesis $tesis, ProjectBackup $backup)
    {
        $request->validate([
            'container_id' => 'required|string',
        ]);

        if ($backup->tesis_id !== $tesis->id) {
            return response()->json([
                'success' => false,
                'message' => 'Backup no encontrado para esta tesis',
            ], 404);
        }

        try {
            $containerId = $request->container_id ?: $tesis->container_id;
            
            if (!$containerId) {
                throw new Exception('No se especificó container_id');
            }

            if ($containerId !== $tesis->container_id) {
                throw new Exception('El destino debe ser el proyecto al que pertenece el backup.');
            }
            $success = $this->backupService->restoreBackupToContainer($tesis, $backup);
            
            if ($success) {
                // Actualizar información del contenedor en la tesis
                $tesis->update([
                    'container_id' => $containerId,
                    'container_status' => 'running',
                    'last_deployed' => now(),
                    'backup_restored' => true,
                    'env_configured' => true, // Backup restoration configures environment
                    'backup_restored_at' => now(),
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Backup restaurado exitosamente en el contenedor',
                    'container_id' => $containerId,
                ]);
            } else {
                throw new Exception('Error durante la restauración');
            }

        } catch (Exception $e) {
            Log::error('Error restaurando backup', [
                'backup_id' => $backup->id,
                'container_id' => $request->container_id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified backup.
     */
    public function destroy(Tesis $tesis, ProjectBackup $backup)
    {
        if ($backup->tesis_id !== $tesis->id) {
            return response()->json([
                'success' => false,
                'message' => 'Backup no encontrado para esta tesis',
            ], 404);
        }

        try {
            $success = $this->backupService->deleteBackup($backup);
            
            if ($success) {
                return response()->json([
                    'success' => true,
                    'message' => 'Backup eliminado exitosamente',
                ]);
            } else {
                throw new Exception('Error eliminando el backup');
            }

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar backup: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create automatic backup for deployment.
     */
    public function createForDeployment(Request $request, Tesis $tesis)
    {
        try {
            $backup = $this->backupService->createFullBackup(
                $tesis,
                'Backup automático antes del despliegue'
            );

            return response()->json([
                'success' => true,
                'message' => 'Backup pre-despliegue creado',
                'data' => $backup,
                'restore_url' => route('proyectos.backups.restore', [$tesis, $backup]),
            ]);

        } catch (Exception $e) {
            Log::error('Error creando backup pre-despliegue', [
                'tesis_id' => $tesis->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear backup: ' . $e->getMessage(),
            ], 500);
        }
    }
}
