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
     * Detectar el tipo de base de datos de un archivo de backup
     */
    public function detectDatabaseType(string $filePath): array
    {
        $result = [
            'type' => 'unknown',
            'version' => null,
            'engine' => null,
            'charset' => null,
            'confidence' => 0
        ];

        if (!file_exists($filePath)) {
            return $result;
        }

        // Leer las primeras líneas del archivo para analizar
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return $result;
        }

        $lines = [];
        $lineCount = 0;
        while (($line = fgets($handle)) !== false && $lineCount < 50) {
            $lines[] = strtolower(trim($line));
            $lineCount++;
        }
        fclose($handle);

        $content = implode("\n", $lines);

        // Detectar MySQL
        if (preg_match('/mysql dump|mysqldump|mysql server version/i', $content)) {
            $result['type'] = 'mysql';
            $result['confidence'] = 90;
            
            // Detectar versión MySQL
            if (preg_match('/mysql server version (\d+\.\d+\.\d+)/i', $content, $matches)) {
                $result['version'] = $matches[1];
            }
            
            // Detectar charset
            if (preg_match('/charset=([a-z0-9_]+)/i', $content, $matches)) {
                $result['charset'] = $matches[1];
            }
            
            // Detectar engine
            if (preg_match('/engine=([a-z]+)/i', $content, $matches)) {
                $result['engine'] = $matches[1];
            }
        }
        // Detectar PostgreSQL
        elseif (preg_match('/postgresql|pg_dump|pgdump/i', $content)) {
            $result['type'] = 'postgresql';
            $result['confidence'] = 90;
            
            if (preg_match('/postgresql (\d+\.\d+)/i', $content, $matches)) {
                $result['version'] = $matches[1];
            }
        }
        // Detectar SQLite
        elseif (preg_match('/sqlite|pragma foreign_keys/i', $content)) {
            $result['type'] = 'sqlite';
            $result['confidence'] = 85;
        }
        // Detectar por comandos SQL comunes
        elseif (preg_match('/create table|insert into|drop table/i', $content)) {
            $result['type'] = 'sql';
            $result['confidence'] = 60;
            
            // Intentar detectar específicos por sintaxis
            if (preg_match('/auto_increment|tinyint|mediumtext/i', $content)) {
                $result['type'] = 'mysql';
                $result['confidence'] = 75;
            } elseif (preg_match('/serial|boolean|text\[\]/i', $content)) {
                $result['type'] = 'postgresql';
                $result['confidence'] = 75;
            }
        }

        return $result;
    }

    /**
     * Configurar base de datos en el contenedor según el tipo detectado
     */
    public function setupDatabaseInContainer(string $containerId, array $dbInfo, string $databaseName = 'project_db'): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'connection_config' => []
        ];

        try {
            Log::info('Configurando base de datos en contenedor', [
                'container_id' => $containerId,
                'db_type' => $dbInfo['type'],
                'db_version' => $dbInfo['version'] ?? 'latest'
            ]);

            switch ($dbInfo['type']) {
                case 'mysql':
                    return $this->setupMySQLInContainer($containerId, $dbInfo, $databaseName);
                    
                case 'postgresql':
                    return $this->setupPostgreSQLInContainer($containerId, $dbInfo, $databaseName);
                    
                case 'sqlite':
                    return $this->setupSQLiteInContainer($containerId, $databaseName);
                    
                default:
                    // Por defecto, usar MySQL
                    Log::info('Tipo de BD desconocido, usando MySQL por defecto');
                    $dbInfo['type'] = 'mysql';
                    return $this->setupMySQLInContainer($containerId, $dbInfo, $databaseName);
            }
            
        } catch (Exception $e) {
            Log::error('Error configurando base de datos en contenedor', [
                'error' => $e->getMessage(),
                'container_id' => $containerId
            ]);
            
            $result['message'] = 'Error configurando base de datos: ' . $e->getMessage();
            return $result;
        }
    }

    /**
     * Configurar MySQL en el contenedor
     */
    protected function setupMySQLInContainer(string $containerId, array $dbInfo, string $databaseName): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'connection_config' => []
        ];

        try {
            Log::info('Configurando MySQL con Docker Compose');
            
            // Para proyectos desplegados con Docker Compose, la configuración ya debe estar en el .env
            // Solo necesitamos asegurar que la conexión esté configurada correctamente
            $connectionConfig = [
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => 'mysql', // Nombre del servicio en docker-compose
                'DB_PORT' => '3306',
                'DB_DATABASE' => $databaseName,
                'DB_USERNAME' => 'root',
                'DB_PASSWORD' => 'secret'
            ];

            // Actualizar archivo .env del contenedor
            $envUpdates = [];
            foreach ($connectionConfig as $key => $value) {
                $envUpdates[] = "sed -i 's/^$key=.*/$key=$value/' /var/www/html/.env";
            }
            
            $envCommand = implode(' && ', $envUpdates);
            exec("docker exec $containerId bash -c \"$envCommand\"", $envOutput, $envReturn);

            // Limpiar cache de configuración
            exec("docker exec $containerId bash -c 'cd /var/www/html && php artisan config:clear'", $cacheOutput);

            $result['success'] = true;
            $result['message'] = 'MySQL configurado correctamente en el contenedor con Docker Compose';
            $result['connection_config'] = $connectionConfig;
            
            Log::info('MySQL configurado exitosamente en contenedor', $connectionConfig);
            
        } catch (Exception $e) {
            $result['message'] = 'Error configurando MySQL: ' . $e->getMessage();
            Log::error('Error en setupMySQLInContainer', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Configurar PostgreSQL en el contenedor
     */
    protected function setupPostgreSQLInContainer(string $containerId, array $dbInfo, string $databaseName): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'connection_config' => []
        ];

        try {
            // 1. Instalar PostgreSQL client
            exec("docker exec $containerId bash -c 'apt-get update && apt-get install -y postgresql-client'", $output, $returnVar);

            // 2. Instalar extensión PDO PostgreSQL
            exec("docker exec $containerId bash -c 'apt-get install -y libpq-dev && docker-php-ext-install pdo_pgsql pgsql'", $output2);

            // 3. Configurar conexión
            $connectionConfig = [
                'DB_CONNECTION' => 'pgsql',
                'DB_HOST' => 'host.docker.internal',
                'DB_PORT' => '5432',
                'DB_DATABASE' => $databaseName,
                'DB_USERNAME' => 'postgres',
                'DB_PASSWORD' => ''
            ];

            // 4. Actualizar .env
            $envUpdates = [];
            foreach ($connectionConfig as $key => $value) {
                $envUpdates[] = "sed -i 's/^$key=.*/$key=$value/' /var/www/html/.env";
            }
            
            $envCommand = implode(' && ', $envUpdates);
            exec("docker exec $containerId bash -c \"$envCommand\"", $envOutput);

            // 5. Limpiar cache
            exec("docker exec $containerId bash -c 'cd /var/www/html && php artisan config:clear'", $cacheOutput);

            $result['success'] = true;
            $result['message'] = 'PostgreSQL configurado correctamente';
            $result['connection_config'] = $connectionConfig;
            
        } catch (Exception $e) {
            $result['message'] = 'Error configurando PostgreSQL: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Configurar SQLite en el contenedor
     */
    protected function setupSQLiteInContainer(string $containerId, string $databaseName): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'connection_config' => []
        ];

        try {
            // 1. Crear directorio para base de datos SQLite
            exec("docker exec $containerId bash -c 'mkdir -p /var/www/html/database'", $output);

            // 2. Configurar conexión SQLite
            $connectionConfig = [
                'DB_CONNECTION' => 'sqlite',
                'DB_DATABASE' => "/var/www/html/database/{$databaseName}.sqlite"
            ];

            // 3. Crear archivo SQLite vacío
            exec("docker exec $containerId bash -c 'touch /var/www/html/database/{$databaseName}.sqlite'", $touchOutput);
            exec("docker exec $containerId bash -c 'chmod 664 /var/www/html/database/{$databaseName}.sqlite'", $chmodOutput);

            // 4. Actualizar .env
            exec("docker exec $containerId bash -c \"sed -i 's/^DB_CONNECTION=.*/DB_CONNECTION=sqlite/' /var/www/html/.env\"", $envOutput1);
            exec("docker exec $containerId bash -c \"sed -i 's|^DB_DATABASE=.*|DB_DATABASE=/var/www/html/database/{$databaseName}.sqlite|' /var/www/html/.env\"", $envOutput2);

            // 5. Limpiar cache
            exec("docker exec $containerId bash -c 'cd /var/www/html && php artisan config:clear'", $cacheOutput);

            $result['success'] = true;
            $result['message'] = 'SQLite configurado correctamente';
            $result['connection_config'] = $connectionConfig;
            
        } catch (Exception $e) {
            $result['message'] = 'Error configurando SQLite: ' . $e->getMessage();
        }

        return $result;
    }

    /**
     * Restaurar backup con detección automática de tipo de BD
     */
    public function restoreBackupWithAutoDetection(string $containerId, string $backupFilePath, string $databaseName = 'project_db'): array
    {
        $result = [
            'success' => false,
            'message' => '',
            'db_info' => [],
            'connection_config' => []
        ];

        try {
            // 1. Detectar tipo de base de datos
            Log::info('Detectando tipo de base de datos del backup', ['file' => $backupFilePath]);
            $dbInfo = $this->detectDatabaseType($backupFilePath);
            
            if ($dbInfo['confidence'] < 50) {
                $result['message'] = 'No se pudo detectar el tipo de base de datos del backup';
                return $result;
            }

            Log::info('Tipo de base de datos detectado', $dbInfo);
            $result['db_info'] = $dbInfo;

            // 2. Configurar base de datos en el contenedor
            $setupResult = $this->setupDatabaseInContainer($containerId, $dbInfo, $databaseName);
            
            if (!$setupResult['success']) {
                $result['message'] = 'Error configurando base de datos: ' . $setupResult['message'];
                return $result;
            }

            $result['connection_config'] = $setupResult['connection_config'];

            // 3. Crear base de datos si no existe (para MySQL/PostgreSQL)
            if (in_array($dbInfo['type'], ['mysql', 'postgresql'])) {
                $this->createDatabaseIfNotExists($containerId, $dbInfo['type'], $databaseName);
            }

            // 4. Restaurar el backup
            $restoreResult = $this->executeBackupRestore($containerId, $backupFilePath, $dbInfo['type'], $databaseName);
            
            if (!$restoreResult['success']) {
                $result['message'] = 'Error restaurando backup: ' . $restoreResult['message'];
                return $result;
            }

            // 5. Ejecutar migraciones de Laravel si existen
            Log::info('Ejecutando migraciones de Laravel...');
            exec("docker exec $containerId bash -c 'cd /var/www/html && php artisan migrate --force'", $migrateOutput, $migrateReturn);
            
            if ($migrateReturn === 0) {
                Log::info('Migraciones ejecutadas exitosamente');
            } else {
                Log::warning('Las migraciones fallaron o no eran necesarias', ['output' => implode("\n", $migrateOutput)]);
            }

            $result['success'] = true;
            $result['message'] = "Base de datos {$dbInfo['type']} configurada y backup restaurado exitosamente";
            
            Log::info('Backup restaurado exitosamente', [
                'container_id' => $containerId,
                'db_type' => $dbInfo['type'],
                'database_name' => $databaseName
            ]);
            
        } catch (Exception $e) {
            $result['message'] = 'Error en la restauración automática: ' . $e->getMessage();
            Log::error('Error en restoreBackupWithAutoDetection', [
                'error' => $e->getMessage(),
                'container_id' => $containerId,
                'file' => $backupFilePath
            ]);
        }

        return $result;
    }

    /**
     * Crear base de datos si no existe
     */
    protected function createDatabaseIfNotExists(string $containerId, string $dbType, string $databaseName): void
    {
        try {
            if ($dbType === 'mysql') {
                $createCmd = "mysql -h host.docker.internal -u root -e 'CREATE DATABASE IF NOT EXISTS `$databaseName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;'";
                exec("docker exec $containerId bash -c \"$createCmd\"", $output, $returnVar);
                
                if ($returnVar === 0) {
                    Log::info("Base de datos MySQL '$databaseName' creada o ya existe");
                } else {
                    Log::warning("No se pudo crear la base de datos MySQL", ['output' => implode("\n", $output)]);
                }
            } elseif ($dbType === 'postgresql') {
                $createCmd = "psql -h host.docker.internal -U postgres -c 'CREATE DATABASE \"$databaseName\";'";
                exec("docker exec $containerId bash -c \"$createCmd\"", $output, $returnVar);
                
                if ($returnVar === 0) {
                    Log::info("Base de datos PostgreSQL '$databaseName' creada o ya existe");
                } else {
                    Log::warning("No se pudo crear la base de datos PostgreSQL", ['output' => implode("\n", $output)]);
                }
            }
        } catch (Exception $e) {
            Log::error('Error creando base de datos', ['error' => $e->getMessage(), 'db_type' => $dbType]);
        }
    }

    /**
     * Ejecutar la restauración del backup según el tipo de BD
     */
    protected function executeBackupRestore(string $containerId, string $backupFilePath, string $dbType, string $databaseName): array
    {
        $result = ['success' => false, 'message' => ''];

        try {
            Log::info('Iniciando restauración de backup con Docker Compose', [
                'container' => $containerId,
                'db_type' => $dbType,
                'database' => $databaseName
            ]);

            // Ejecutar restauración según el tipo de BD usando Docker Compose
            switch ($dbType) {
                case 'mysql':
                    return $this->restoreMySQLBackupWithCompose($containerId, $backupFilePath, $databaseName);
                    
                case 'postgresql':
                    return $this->restorePostgreSQLBackupWithCompose($containerId, $backupFilePath, $databaseName);
                    
                case 'sqlite':
                    return $this->restoreSQLiteBackupWithCompose($containerId, $backupFilePath, $databaseName);
                    
                default:
                    $result['message'] = "Tipo de base de datos no soportado: $dbType";
                    return $result;
            }

        } catch (Exception $e) {
            $result['message'] = 'Error ejecutando restauración: ' . $e->getMessage();
            Log::error('Error en executeBackupRestore', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Restaurar backup MySQL usando Docker Compose
     */
    protected function restoreMySQLBackupWithCompose(string $containerId, string $backupFilePath, string $databaseName): array
    {
        $result = ['success' => false, 'message' => ''];

        try {
            $mysqlContainer = $containerId . '-mysql';
            
            Log::info('Restaurando MySQL con Docker Compose', [
                'mysql_container' => $mysqlContainer,
                'backup_file' => $backupFilePath,
                'database' => $databaseName
            ]);

            // 1. Verificar que el contenedor MySQL esté corriendo
            exec("docker ps --filter name=$mysqlContainer --format '{{.Names}}'", $containerCheck, $checkReturn);
            
            if (empty($containerCheck) || $checkReturn !== 0) {
                $result['message'] = "Contenedor MySQL no encontrado: $mysqlContainer";
                return $result;
            }

            // 2. Crear la base de datos si no existe
            $createDbCommand = "docker exec $mysqlContainer mysql -uroot -psecret -e \"CREATE DATABASE IF NOT EXISTS $databaseName CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\"";
            exec($createDbCommand, $createOutput, $createReturn);
            
            if ($createReturn !== 0) {
                $result['message'] = 'Error creando base de datos: ' . implode(', ', $createOutput);
                Log::error('Error creando base de datos', ['output' => $createOutput]);
                return $result;
            }

            // 3. Copiar el archivo de backup al contenedor MySQL
            $containerBackupPath = "/tmp/backup_restore.sql";
            $copyCommand = "docker cp \"$backupFilePath\" $mysqlContainer:$containerBackupPath";
            exec($copyCommand, $copyOutput, $copyReturn);
            
            if ($copyReturn !== 0) {
                $result['message'] = 'Error copiando backup al contenedor: ' . implode(', ', $copyOutput);
                return $result;
            }

            // 4. Restaurar el backup
            $restoreCommand = "docker exec $mysqlContainer bash -c \"mysql -uroot -psecret $databaseName < $containerBackupPath\"";
            exec($restoreCommand, $restoreOutput, $restoreReturn);
            
            // 5. Limpiar archivo temporal
            exec("docker exec $mysqlContainer rm -f $containerBackupPath", $cleanOutput);

            if ($restoreReturn !== 0) {
                $result['message'] = 'Error restaurando backup: ' . implode(', ', $restoreOutput);
                Log::error('Error en restauración MySQL', ['output' => $restoreOutput]);
                return $result;
            }

            // 6. Ejecutar migraciones de Laravel si existen
            exec("docker exec $containerId bash -c 'cd /var/www/html && php artisan migrate --force'", $migrateOutput, $migrateReturn);

            $result['success'] = true;
            $result['message'] = "Base de datos MySQL '$databaseName' restaurada exitosamente usando Docker Compose";
            
            Log::info('Backup MySQL restaurado exitosamente con Docker Compose', [
                'database' => $databaseName,
                'migrations_run' => $migrateReturn === 0
            ]);
            
        } catch (Exception $e) {
            $result['message'] = 'Error restaurando MySQL: ' . $e->getMessage();
            Log::error('Error en restoreMySQLBackupWithCompose', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Restaurar backup PostgreSQL usando Docker Compose
     */
    protected function restorePostgreSQLBackupWithCompose(string $containerId, string $backupFilePath, string $databaseName): array
    {
        $result = ['success' => false, 'message' => ''];

        try {
            // Para PostgreSQL, asumir que se usa un contenedor separado similar a MySQL
            $postgresContainer = $containerId . '-postgres';
            
            // Verificar contenedor PostgreSQL
            exec("docker ps --filter name=$postgresContainer --format '{{.Names}}'", $containerCheck, $checkReturn);
            
            if (empty($containerCheck)) {
                $result['message'] = "Contenedor PostgreSQL no encontrado: $postgresContainer";
                return $result;
            }

            // Crear base de datos
            $createDbCommand = "docker exec $postgresContainer psql -U postgres -c \"CREATE DATABASE $databaseName;\"";
            exec($createDbCommand, $createOutput, $createReturn);

            // Copiar y restaurar backup
            $containerBackupPath = "/tmp/backup_restore.sql";
            exec("docker cp \"$backupFilePath\" $postgresContainer:$containerBackupPath", $copyOutput, $copyReturn);
            
            if ($copyReturn === 0) {
                $restoreCommand = "docker exec $postgresContainer bash -c \"psql -U postgres -d $databaseName -f $containerBackupPath\"";
                exec($restoreCommand, $restoreOutput, $restoreReturn);
                
                exec("docker exec $postgresContainer rm -f $containerBackupPath", $cleanOutput);
                
                if ($restoreReturn === 0) {
                    $result['success'] = true;
                    $result['message'] = "Base de datos PostgreSQL '$databaseName' restaurada exitosamente";
                } else {
                    $result['message'] = 'Error restaurando PostgreSQL: ' . implode(', ', $restoreOutput);
                }
            } else {
                $result['message'] = 'Error copiando backup PostgreSQL al contenedor';
            }
            
        } catch (Exception $e) {
            $result['message'] = 'Error restaurando PostgreSQL: ' . $e->getMessage();
            Log::error('Error en restorePostgreSQLBackupWithCompose', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Restaurar backup SQLite usando Docker Compose
     */
    protected function restoreSQLiteBackupWithCompose(string $containerId, string $backupFilePath, string $databaseName): array
    {
        $result = ['success' => false, 'message' => ''];

        try {
            // Para SQLite, copiar directamente al contenedor de la aplicación
            $sqlitePath = "/var/www/html/database/$databaseName.sqlite";
            
            // Crear directorio si no existe
            exec("docker exec $containerId mkdir -p /var/www/html/database", $mkdirOutput);
            
            // Copiar archivo SQLite
            exec("docker cp \"$backupFilePath\" $containerId:$sqlitePath", $copyOutput, $copyReturn);
            
            if ($copyReturn === 0) {
                // Establecer permisos correctos
                exec("docker exec $containerId chown www-data:www-data $sqlitePath", $chownOutput);
                exec("docker exec $containerId chmod 664 $sqlitePath", $chmodOutput);
                
                $result['success'] = true;
                $result['message'] = "Base de datos SQLite '$databaseName' restaurada exitosamente";
                
                Log::info('Backup SQLite restaurado exitosamente', ['database' => $databaseName]);
            } else {
                $result['message'] = 'Error copiando archivo SQLite al contenedor';
            }
            
        } catch (Exception $e) {
            $result['message'] = 'Error restaurando SQLite: ' . $e->getMessage();
            Log::error('Error en restoreSQLiteBackupWithCompose', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * Restaurar backup en un contenedor Docker - VERSIÓN ACTUALIZADA
     */
    public function restoreBackupToContainer(Tesis $tesis, ProjectBackup $backup): bool
    {
        if (($tesis->project_config['deployment_type'] ?? null) === 'laragon') {
            $database = $tesis->project_config['database_name'] ??
                str_replace('-', '_', $tesis->project_config['project_name'] ?? '');
            $databaseService = app(ProjectDatabaseService::class);
            if ($tesis->project_config['custom_env'] ?? false) {
                $databaseService = $databaseService->forEnvironment(file_get_contents($tesis->project_config['project_path'].'/.env'));
            }
            $databaseService->restoreBackup(
                storage_path('app/'.$backup->file_path), $database
            );
            return true;
        }
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

        if (($tesis->project_config['deployment_type'] ?? null) === 'docker') {
            app(DockerProjectService::class)->restoreSql($tesis->project_config, $sqlFile);
            return true;
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
