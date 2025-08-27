<?php

namespace App\Services;

use App\Models\Tesis;
use App\Models\ProjectBackup;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;
use ZipArchive;

class ProjectBackupService
{
    protected string $backupPath;

    public function __construct()
    {
        $this->backupPath = 'project-backups';
        // Crear directorio manualmente sin usar Storage
        $fullPath = storage_path('app/' . $this->backupPath);
        if (!file_exists($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
    }

    /**
     * Restaurar backup en un contenedor Docker - VERSIÓN ACTUALIZADA
     */
    public function restoreBackupToContainer(Tesis $tesis, ProjectBackup $backup): bool
    {
        try {
            // Para archivos temporales, usar ruta directa
            if ($backup->is_temporary ?? false) {
                $filePath = storage_path('app/' . $backup->file_path);
            } else {
                $filePath = storage_path('app/' . $backup->file_path);
            }
            
            if (!file_exists($filePath)) {
                Log::error('Archivo de backup no existe', ['file_path' => $filePath]);
                return false;
            }

            Log::info('Iniciando restauración de backup', [
                'tesis_id' => $tesis->id,
                'backup_id' => $backup->id ?? 'temporal',
                'file_path' => $filePath,
                'backup_type' => $backup->backup_type,
                'is_temporary' => $backup->is_temporary ?? false
            ]);

            $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            
            if ($fileExtension === 'sql') {
                Log::info('Procesando archivo SQL directo');
                
                // Para archivos SQL, necesitamos un contenedor activo para restaurar
                $dockerService = app(\App\Services\DockerService::class);
                
                // Si no hay contenedor, crear uno primero
                if (empty($tesis->container_id)) {
                    Log::info('No hay contenedor, desplegando proyecto primero');
                    $result = $dockerService->buildAndRunProject($tesis);
                    
                    if (!$result) {
                        Log::error('No se pudo desplegar el proyecto');
                        return false;
                    }
                }
                
                // Ahora restaurar la base de datos
                return $this->restoreDatabase($filePath, $tesis);
                
            } else {
                // Para archivos comprimidos, extraer primero
                Log::info('Procesando archivo comprimido', ['extension' => $fileExtension]);
                
                $extractPath = storage_path('app/temp/extract-' . Str::random(8));
                
                if (!is_dir($extractPath)) {
                    mkdir($extractPath, 0755, true);
                }

                // Extraer archivo
                if ($fileExtension === 'zip') {
                    $zip = new \ZipArchive();
                    if ($zip->open($filePath) === TRUE) {
                        $zip->extractTo($extractPath);
                        $zip->close();
                        Log::info('Archivo ZIP extraído', ['extract_path' => $extractPath]);
                    } else {
                        Log::error('No se pudo extraer el archivo ZIP');
                        return false;
                    }
                } elseif (in_array($fileExtension, ['tar', 'gz'])) {
                    // Usar comando tar
                    $command = "tar -xf \"{$filePath}\" -C \"{$extractPath}\"";
                    $output = [];
                    $returnCode = 0;
                    exec($command, $output, $returnCode);
                    
                    if ($returnCode === 0) {
                        Log::info('Archivo TAR extraído', ['extract_path' => $extractPath]);
                    } else {
                        Log::error('Error extrayendo archivo TAR', ['command' => $command, 'return_code' => $returnCode]);
                        return false;
                    }
                }
                
                // Buscar archivos SQL en la extracción
                $sqlFiles = [];
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($extractPath)
                );
                
                foreach ($iterator as $file) {
                    if ($file->isFile() && strtolower($file->getExtension()) === 'sql') {
                        $sqlFiles[] = $file->getPathname();
                    }
                }
                
                if (!empty($sqlFiles)) {
                    Log::info('Archivos SQL encontrados', ['files' => $sqlFiles]);
                    
                    // Procesar cada archivo SQL
                    foreach ($sqlFiles as $sqlFile) {
                        if (!$this->restoreDatabase($sqlFile, $tesis)) {
                            Log::error('Error restaurando archivo SQL', ['sql_file' => $sqlFile]);
                            return false;
                        }
                    }
                }
                
                // Limpiar directorio temporal
                $this->removeDirectory($extractPath);
                
                return true;
            }

        } catch (\Exception $e) {
            Log::error('Error en restoreBackupToContainer', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }

    /**
     * Restaurar base de datos desde archivo SQL
     */
    private function restoreDatabase(string $sqlFile, Tesis $tesis): bool
    {
        Log::info('Restaurando base de datos desde archivo SQL', [
            'sql_file' => $sqlFile,
            'tesis_id' => $tesis->id,
            'container_id' => $tesis->container_id
        ]);
        
        if (empty($tesis->container_id)) {
            Log::error('No hay container_id para restaurar la base de datos');
            return false;
        }
        
        try {
            // Copiar archivo SQL al contenedor
            $containerSqlPath = '/tmp/restore_backup.sql';
            $copyCommand = "docker cp \"{$sqlFile}\" {$tesis->container_id}:{$containerSqlPath}";
            
            Log::info('Copiando archivo SQL al contenedor', ['command' => $copyCommand]);
            $copyOutput = shell_exec($copyCommand);
            
            // Leer contenido para detectar tipo de base de datos
            $sqlContent = file_get_contents($sqlFile);
            
            if (stripos($sqlContent, 'CREATE TABLE') !== false || stripos($sqlContent, 'INSERT INTO') !== false) {
                
                // Intentar con MySQL/MariaDB primero
                $mysqlCommand = "docker exec {$tesis->container_id} bash -c \"mysql -u root -p\\\${MYSQL_ROOT_PASSWORD:-root} \\\${MYSQL_DATABASE:-app} < {$containerSqlPath} 2>/dev/null || mysql -u root \\\${MYSQL_DATABASE:-app} < {$containerSqlPath}\"";
                
                Log::info('Ejecutando comando MySQL', ['command' => $mysqlCommand]);
                $mysqlOutput = shell_exec($mysqlCommand);
                
                // Si MySQL falla, intentar con SQLite
                if (empty($mysqlOutput) || strpos($mysqlOutput, 'ERROR') !== false) {
                    Log::info('MySQL falló, intentando SQLite');
                    $sqliteCommand = "docker exec {$tesis->container_id} bash -c \"sqlite3 /app/database/database.sqlite < {$containerSqlPath} 2>/dev/null || sqlite3 /var/www/html/database/database.sqlite < {$containerSqlPath}\"";
                    
                    Log::info('Ejecutando comando SQLite', ['command' => $sqliteCommand]);
                    $sqliteOutput = shell_exec($sqliteCommand);
                    
                    if (empty($sqliteOutput) || strpos($sqliteOutput, 'Error') !== false) {
                        Log::error('Ambos comandos de base de datos fallaron', [
                            'mysql_output' => $mysqlOutput,
                            'sqlite_output' => $sqliteOutput
                        ]);
                        return false;
                    }
                }
                
                // Limpiar archivo temporal del contenedor
                $cleanCommand = "docker exec {$tesis->container_id} rm -f {$containerSqlPath}";
                shell_exec($cleanCommand);
                
                Log::info('✅ Base de datos restaurada exitosamente');
                return true;
                
            } else {
                Log::warning('El archivo SQL no contiene comandos de base de datos reconocibles');
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error('Error restaurando base de datos', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return false;
        }
    }
    
    /**
     * Agregar método para limpiar directorios
     */
    private function removeDirectory($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $object) && !is_link($dir."/".$object))
                        $this->removeDirectory($dir . DIRECTORY_SEPARATOR . $object);
                    else
                        unlink($dir . DIRECTORY_SEPARATOR . $object);
                }
            }
            rmdir($dir);
        }
    }
}