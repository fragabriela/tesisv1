<?php

namespace App\Services;

use App\Models\Tesis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class LaragonService
{
    private $laragonPath;
    private $wwwPath;
    private $mysqlHost;
    private $mysqlUser;
    private $mysqlPassword;
    
    public function __construct()
    {
        // Configuración de Laragon
        $this->laragonPath = 'C:\laragon';
        $this->wwwPath = $this->laragonPath . '\www';
        $this->mysqlHost = '127.0.0.1';
        $this->mysqlUser = 'root';
        $this->mysqlPassword = ''; // Laragon usa contraseña vacía por defecto
    }
    
    /**
     * Verificar si Laragon está disponible
     */
    public function checkLaragonAvailability(): bool
    {
        try {
            // Verificar que existe la carpeta www de Laragon
            if (!is_dir($this->wwwPath)) {
                Log::error('Laragon www directory not found: ' . $this->wwwPath);
                return false;
            }
            
            // Verificar que MySQL está corriendo
            $mysqlCheck = shell_exec('tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL | find /I "mysqld.exe"');
            if (empty($mysqlCheck)) {
                Log::error('MySQL is not running in Laragon');
                return false;
            }
            
            Log::info('Laragon is available and MySQL is running');
            return true;
        } catch (\Exception $e) {
            Log::error('Error checking Laragon availability: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Desplegar proyecto en Laragon
     */
    public function deployProject(Tesis $tesis): array
    {
        try {
            Log::info("LARAGON SERVICE: Starting deployment for tesis {$tesis->id}");
            
            // Verificar disponibilidad de Laragon
            if (!$this->checkLaragonAvailability()) {
                throw new \Exception('Laragon no está disponible o MySQL no está corriendo');
            }
            
            // Generar nombre de proyecto
            $projectName = $this->generateProjectName($tesis);
            $projectPath = $this->wwwPath . '\\' . $projectName;
            
            Log::info("Project name: $projectName");
            Log::info("Project path: $projectPath");
            
            // Copiar archivos del repositorio
            if (!$this->copyProjectFiles($tesis, $projectPath)) {
                throw new \Exception('Error copiando archivos del proyecto');
            }
            
            // Configurar .env para Laragon
            if (!$this->configureLaragonEnv($projectPath, $projectName)) {
                throw new \Exception('Error configurando archivo .env');
            }
            
            // Instalar dependencias
            if (!$this->installDependencies($projectPath)) {
                Log::warning('Advertencia: No se pudieron instalar todas las dependencias');
            }
            
            // Ejecutar migraciones
            if (!$this->runMigrations($projectPath)) {
                Log::warning('Advertencia: No se pudieron ejecutar las migraciones');
            }
            
            // Generar URL del proyecto
            // Configurar automáticamente el virtual host y hosts file
            $this->configureLaragonProject($projectName, $projectPath);
            
            $projectUrl = "http://{$projectName}.test";
            
            Log::info("Project deployed successfully at: $projectUrl");
            
            return [
                'container_id' => $projectName,
                'container_status' => 'running',
                'project_url' => $projectUrl,
                'project_config' => [
                    'external_port' => '80', // Laragon siempre usa puerto 80
                    'project_name' => $projectName,
                    'project_path' => $projectPath,
                    'deployment_type' => 'laragon'
                ],
                'status' => 'success',
                'message' => 'Proyecto desplegado exitosamente en Laragon'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error en deployProject: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Generar nombre único para el proyecto
     */
    private function generateProjectName(Tesis $tesis): string
    {
        $baseName = Str::slug($tesis->titulo, '-');
        if (empty($baseName)) {
            $baseName = 'proyecto-' . $tesis->id;
        }
        
        // Asegurar nombre único
        $projectName = $baseName;
        $counter = 1;
        while (is_dir($this->wwwPath . '\\' . $projectName)) {
            $projectName = $baseName . '-' . $counter;
            $counter++;
        }
        
        return $projectName;
    }
    
    /**
     * Copiar archivos del repositorio al directorio de Laragon
     */
    private function copyProjectFiles(Tesis $tesis, string $targetPath): bool
    {
        try {
            $sourcePath = storage_path('app/public/' . $tesis->project_repo_path);
            
            if (!is_dir($sourcePath)) {
                Log::error("Source directory not found: $sourcePath");
                return false;
            }
            
            Log::info("Copying files from $sourcePath to $targetPath");
            
            // Crear directorio de destino
            if (!File::makeDirectory($targetPath, 0755, true, true)) {
                Log::error("Failed to create target directory: $targetPath");
                return false;
            }
            
            // Copiar archivos recursivamente
            $this->copyDirectory($sourcePath, $targetPath);
            
            Log::info("Files copied successfully");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Error copying files: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Configurar archivo .env para Laragon
     */
    private function configureLaragonEnv(string $projectPath, string $projectName): bool
    {
        try {
            $envPath = $projectPath . '\\.env';
            $envExamplePath = $projectPath . '\\.env.example';
            
            // Copiar .env.example si .env no existe
            if (!file_exists($envPath) && file_exists($envExamplePath)) {
                copy($envExamplePath, $envPath);
            }
            
            // Configuración base para Laragon
            $envConfig = [
                'APP_NAME' => $projectName,
                'APP_ENV' => 'local',
                'APP_KEY' => 'base64:' . base64_encode(Str::random(32)),
                'APP_DEBUG' => 'true',
                'APP_URL' => "http://{$projectName}.test",
                
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $this->mysqlHost,
                'DB_PORT' => '3306',
                'DB_DATABASE' => str_replace('-', '_', $projectName),
                'DB_USERNAME' => $this->mysqlUser,
                'DB_PASSWORD' => $this->mysqlPassword,
            ];
            
            // Crear base de datos
            $this->createDatabase($envConfig['DB_DATABASE']);
            
            // Actualizar archivo .env
            $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';
            
            foreach ($envConfig as $key => $value) {
                if (preg_match("/^{$key}=.*/m", $envContent)) {
                    $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
                } else {
                    $envContent .= "\n{$key}={$value}";
                }
            }
            
            file_put_contents($envPath, $envContent);
            
            Log::info("Environment configured successfully");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Error configuring environment: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear base de datos MySQL
     */
    private function createDatabase(string $databaseName): bool
    {
        try {
            // Usar la ruta completa de MySQL en Laragon
            $mysqlPath = 'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysql.exe';
            
            $command = "\"{$mysqlPath}\" -h{$this->mysqlHost} -u{$this->mysqlUser}";
            if (!empty($this->mysqlPassword)) {
                $command .= " -p{$this->mysqlPassword}";
            }
            $command .= " -e \"CREATE DATABASE IF NOT EXISTS `{$databaseName}`;\"";
            
            exec($command . ' 2>&1', $output, $returnCode);
            
            if ($returnCode === 0) {
                Log::info("Database '{$databaseName}' created successfully");
                return true;
            } else {
                Log::warning("Failed to create database: " . implode("\n", $output));
                return false;
            }
            
        } catch (\Exception $e) {
            Log::error("Error creating database: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Instalar dependencias de Composer
     */
    private function installDependencies(string $projectPath): bool
    {
        try {
            $composerPath = $projectPath . '\\composer.json';
            if (!file_exists($composerPath)) {
                Log::info("No composer.json found, skipping dependency installation");
                return true;
            }
            
            Log::info("Installing Composer dependencies");
            
            // Verificar si vendor ya existe
            if (is_dir($projectPath . '\\vendor')) {
                Log::info("Vendor directory already exists, skipping composer install");
                return true;
            }
            
            // Configurar Composer para evitar problemas de SSL y timeout
            $composerConfig = [
                "cd /d \"{$projectPath}\" && composer config --global disable-tls true",
                "cd /d \"{$projectPath}\" && composer config --global secure-http false",
                "cd /d \"{$projectPath}\" && composer config --global process-timeout 600"
            ];
            
            foreach ($composerConfig as $config) {
                exec($config . ' 2>&1', $output, $returnCode);
            }
            
            // Usar comandos más robustos con timeouts y sin plataforma específica
            $commands = [
                "cd /d \"{$projectPath}\" && composer install --no-interaction --ignore-platform-reqs --no-dev --prefer-dist",
                "cd /d \"{$projectPath}\" && composer install --no-interaction --ignore-platform-reqs --prefer-dist",
                "cd /d \"{$projectPath}\" && composer install --no-interaction --no-scripts --ignore-platform-reqs"
            ];
            
            foreach ($commands as $command) {
                Log::info("Executing: $command");
                
                // Ejecutar comando con timeout limitado
                $descriptorspec = [
                    0 => ["pipe", "r"],
                    1 => ["pipe", "w"],
                    2 => ["pipe", "w"]
                ];
                
                $process = proc_open($command, $descriptorspec, $pipes);
                
                if (is_resource($process)) {
                    // Dar tiempo limitado para la instalación
                    stream_set_timeout($pipes[1], 300); // 5 minutos
                    
                    $output = stream_get_contents($pipes[1]);
                    $error = stream_get_contents($pipes[2]);
                    
                    fclose($pipes[0]);
                    fclose($pipes[1]);
                    fclose($pipes[2]);
                    
                    $returnCode = proc_close($process);
                    
                    if ($returnCode === 0) {
                        Log::info("Dependencies installed successfully");
                        return true;
                    }
                    
                    Log::warning("Command failed with code $returnCode: $error");
                } else {
                    Log::error("Failed to start process: $command");
                }
            }
            
            Log::warning("All composer commands failed, but continuing deployment");
            return true; // Cambiar a true para no bloquear el deployment
            
        } catch (\Exception $e) {
            Log::error("Error installing dependencies: " . $e->getMessage());
            return true; // Continuar incluso si falla
        }
    }
    
    /**
     * Ejecutar migraciones de Laravel
     */
    private function runMigrations(string $projectPath): bool
    {
        try {
            $artisanPath = $projectPath . '\\artisan';
            if (!file_exists($artisanPath)) {
                Log::info("No artisan found, skipping migrations");
                return true;
            }
            
            Log::info("Ejecutando migraciones de Laravel");
            
            // Comando para ejecutar migraciones
            $command = "cd /d \"{$projectPath}\" && php artisan migrate --force";
            
            // Ejecutar comando con timeout
            $descriptorspec = [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"], 
                2 => ["pipe", "w"]
            ];
            
            $process = proc_open($command, $descriptorspec, $pipes);
            
            if (is_resource($process)) {
                stream_set_timeout($pipes[1], 60); // 1 minuto para migraciones
                
                $output = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
                
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                
                $returnCode = proc_close($process);
                
                if ($returnCode === 0) {
                    Log::info("Migraciones ejecutadas exitosamente");
                    Log::info("Output de migraciones: " . $output);
                    
                    // Ejecutar seeders si existen
                    $this->runSeeders($projectPath);
                    
                    return true;
                } else {
                    Log::warning("Error ejecutando migraciones: " . $error);
                    Log::warning("Output: " . $output);
                    return false;
                }
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error("Error ejecutando migraciones: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ejecutar seeders básicos si existen
     */
    private function runSeeders(string $projectPath): void
    {
        try {
            $seederPath = $projectPath . '\\database\\seeders\\DatabaseSeeder.php';
            if (file_exists($seederPath)) {
                Log::info("Ejecutando seeders básicos");
                
                $command = "cd /d \"{$projectPath}\" && php artisan db:seed --force";
                exec($command . ' 2>&1', $output, $returnCode);
                
                if ($returnCode === 0) {
                    Log::info("Seeders ejecutados exitosamente");
                } else {
                    Log::warning("Error ejecutando seeders: " . implode("\n", $output));
                }
            }
        } catch (\Exception $e) {
            Log::warning("Error ejecutando seeders: " . $e->getMessage());
        }
    }
    
    /**
     * Copiar directorio recursivamente
     */
    private function copyDirectory(string $source, string $destination): void
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $files = scandir($source);
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') {
                continue;
            }
            
            $sourcePath = $source . '\\' . $file;
            $destPath = $destination . '\\' . $file;
            
            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath);
            } else {
                copy($sourcePath, $destPath);
            }
        }
    }
    
    /**
     * Detener proyecto (eliminar directorio)
     */
    public function stopProject(string $projectName): bool
    {
        try {
            $projectPath = $this->wwwPath . '\\' . $projectName;
            
            if (is_dir($projectPath)) {
                File::deleteDirectory($projectPath);
                Log::info("Project '{$projectName}' stopped and directory removed");
                return true;
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::error("Error stopping project: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtener estado del proyecto
     */
    public function getProjectStatus(string $projectName): string
    {
        try {
            $projectPath = $this->wwwPath . '\\' . $projectName;
            
            if (is_dir($projectPath)) {
                // Verificar si curl está disponible
                if (function_exists('curl_init')) {
                    // Verificar si la URL responde
                    $projectUrl = "http://{$projectName}.test";
                    
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $projectUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($httpCode === 200 || $httpCode === 302) {
                        return 'running';
                    } else {
                        return 'error';
                    }
                } else {
                    // Fallback: solo verificar si el directorio existe
                    Log::warning("curl not available, using directory check fallback");
                    return file_exists($projectPath . '\\public\\index.php') ? 'running' : 'error';
                }
            } else {
                return 'stopped';
            }
            
        } catch (\Exception $e) {
            Log::error("Error checking project status: " . $e->getMessage());
            return 'error';
        }
    }
    
    /**
     * Verificar si el proyecto existe
     */
    public function projectExists(string $projectName): bool
    {
        $projectPath = $this->wwwPath . '\\' . $projectName;
        return is_dir($projectPath);
    }
    
    /**
     * Clonar repositorio de GitHub y prepararlo automáticamente en Laragon
     */
    public function cloneRepository(string $githubUrl): string
    {
        try {
            $tempPath = 'repos/' . Str::random(10);
            $fullPath = storage_path('app/public/' . $tempPath);
            
            // Crear directorio temporal
            if (!File::makeDirectory($fullPath, 0755, true, true)) {
                throw new \Exception("No se pudo crear el directorio temporal: $fullPath");
            }
            
            // Clonar repositorio
            $command = "git clone \"{$githubUrl}\" \"{$fullPath}\" 2>&1";
            exec($command, $output, $returnCode);
            
            if ($returnCode !== 0) {
                throw new \Exception('Error clonando repositorio: ' . implode("\n", $output));
            }
            
            Log::info("Repository cloned successfully to: $tempPath");
            
            // ¡NUEVO! Preparar automáticamente el proyecto en Laragon
            $this->prepareProjectInLaragon($tempPath, $githubUrl);
            
            return $tempPath;
            
        } catch (\Exception $e) {
            Log::error("Error cloning repository: " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Preparar el proyecto clonado automáticamente en Laragon
     */
    private function prepareProjectInLaragon(string $repoPath, string $githubUrl): void
    {
        try {
            Log::info("Preparando proyecto automáticamente en Laragon");
            
            // 1. Detectar tipo de proyecto
            $projectType = $this->detectProjectType($repoPath);
            Log::info("Tipo de proyecto detectado: $projectType");
            
            // 2. Generar nombre único para el proyecto basado en la URL del repositorio
            $projectName = $this->generateProjectNameFromUrl($githubUrl);
            Log::info("Nombre del proyecto generado: $projectName");
            
            // 3. Copiar proyecto a la carpeta www de Laragon
            $projectPath = $this->wwwPath . '\\' . $projectName;
            $sourcePath = storage_path('app/public/' . $repoPath);
            
            Log::info("Copiando proyecto de $sourcePath a $projectPath");
            $this->copyDirectory($sourcePath, $projectPath);
            
            // 4. Configurar automáticamente en Laragon
            $this->configureLaragonProject($projectName, $projectPath);
            
            // 5. NUEVO: Configurar base de datos y entorno básico
            $this->setupProjectDatabase($projectName, $projectPath, $projectType);
            
            Log::info("Proyecto preparado exitosamente en Laragon: http://{$projectName}.test");
            
        } catch (\Exception $e) {
            Log::warning("Error preparando proyecto en Laragon: " . $e->getMessage());
            // No lanzamos excepción para no interrumpir el flujo principal
        }
    }
    
    /**
     * Generar nombre de proyecto basado en la URL del repositorio
     */
    private function generateProjectNameFromUrl(string $githubUrl): string
    {
        try {
            // Extraer nombre del repositorio de la URL
            $urlParts = parse_url($githubUrl);
            $pathParts = explode('/', trim($urlParts['path'], '/'));
            $repoName = end($pathParts);
            
            // Remover .git si existe
            $repoName = str_replace('.git', '', $repoName);
            
            // Convertir a formato válido para URLs
            $projectName = Str::slug($repoName, '-');
            
            // Asegurar nombre único
            $counter = 1;
            $originalName = $projectName;
            while (is_dir($this->wwwPath . '\\' . $projectName)) {
                $projectName = $originalName . '-' . $counter;
                $counter++;
            }
            
            return $projectName;
            
        } catch (\Exception $e) {
            Log::warning("Error generando nombre de proyecto: " . $e->getMessage());
            // Fallback: usar timestamp
            return 'proyecto-' . time();
        }
    }
    
    /**
     * Detectar tipo de proyecto
     */
    public function detectProjectType(string $repoPath): string
    {
        try {
            $fullPath = storage_path('app/public/' . $repoPath);
            
            // Laravel
            if (file_exists($fullPath . '/artisan') && file_exists($fullPath . '/composer.json')) {
                $composerContent = file_get_contents($fullPath . '/composer.json');
                if (strpos($composerContent, 'laravel/framework') !== false) {
                    return 'laravel';
                }
            }
            
            // PHP genérico
            if (file_exists($fullPath . '/composer.json') || 
                file_exists($fullPath . '/index.php') ||
                count(glob($fullPath . '/*.php')) > 0) {
                return 'php';
            }
            
            // Node.js
            if (file_exists($fullPath . '/package.json')) {
                return 'nodejs';
            }
            
            // Por defecto
            return 'php';
            
        } catch (\Exception $e) {
            Log::error("Error detecting project type: " . $e->getMessage());
            return 'php';
        }
    }
    
    /**
     * Configurar el proyecto en Laragon (virtual host y hosts file)
     */
    private function configureLaragonProject(string $projectName, string $projectPath): bool
    {
        try {
            Log::info("Configurando proyecto en Laragon: $projectName");
            
            // 1. Crear virtual host
            $this->createVirtualHost($projectName, $projectPath);
            
            // 2. Agregar entrada al archivo hosts
            $this->addHostsEntry($projectName);
            
            // 3. Recargar Apache de forma suave (sin reinicio completo)
            $this->reloadApache();
            
            Log::info("Proyecto configurado exitosamente en Laragon");
            return true;
            
        } catch (\Exception $e) {
            Log::error("Error configurando proyecto en Laragon: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear virtual host para el proyecto
     */
    private function createVirtualHost(string $projectName, string $projectPath): void
    {
        $publicPath = $projectPath . '/public';
        $vhostPath = 'C:\laragon\etc\apache2\sites-enabled\auto.' . $projectName . '.test.conf';
        
        $vhostContent = 'define ROOT "' . str_replace('\\', '/', $publicPath) . '"' . "\n";
        $vhostContent .= 'define SITE "' . $projectName . '.test"' . "\n\n";
        $vhostContent .= '<VirtualHost *:80>' . "\n";
        $vhostContent .= '    DocumentRoot "${ROOT}"' . "\n";
        $vhostContent .= '    ServerName ${SITE}' . "\n";
        $vhostContent .= '    ServerAlias *.${SITE}' . "\n";
        $vhostContent .= '    <Directory "${ROOT}">' . "\n";
        $vhostContent .= '        AllowOverride All' . "\n";
        $vhostContent .= '        Require all granted' . "\n";
        $vhostContent .= '    </Directory>' . "\n";
        $vhostContent .= '</VirtualHost>' . "\n\n";
        $vhostContent .= '<VirtualHost *:443>' . "\n";
        $vhostContent .= '    DocumentRoot "${ROOT}"' . "\n";
        $vhostContent .= '    ServerName ${SITE}' . "\n";
        $vhostContent .= '    ServerAlias *.${SITE}' . "\n";
        $vhostContent .= '    <Directory "${ROOT}">' . "\n";
        $vhostContent .= '        AllowOverride All' . "\n";
        $vhostContent .= '        Require all granted' . "\n";
        $vhostContent .= '    </Directory>' . "\n\n";
        $vhostContent .= '    SSLEngine on' . "\n";
        $vhostContent .= '    SSLCertificateFile      C:/laragon/etc/ssl/laragon.crt' . "\n";
        $vhostContent .= '    SSLCertificateKeyFile   C:/laragon/etc/ssl/laragon.key' . "\n\n";
        $vhostContent .= '</VirtualHost>';
        
        file_put_contents($vhostPath, $vhostContent);
        Log::info("Virtual host creado: $vhostPath");
    }
    
    /**
     * Agregar entrada al archivo hosts
     */
    private function addHostsEntry(string $projectName): void
    {
        try {
            $hostsFile = 'C:\Windows\System32\drivers\etc\hosts';
            $domain = $projectName . '.test';
            $entry = "127.0.0.1      {$domain}          #laragon magic!";
            
            // Verificar si ya existe la entrada
            if (file_exists($hostsFile)) {
                $hostsContent = file_get_contents($hostsFile);
                if (strpos($hostsContent, $domain) !== false) {
                    Log::info("Entrada ya existe en hosts: $domain");
                    return;
                }
                
                // Método 1: Intentar agregar directamente
                $result = file_put_contents($hostsFile, "\n" . $entry . "\n", FILE_APPEND | LOCK_EX);
                if ($result !== false) {
                    Log::info("Entrada agregada al archivo hosts: $entry");
                    return;
                }
                
                // Método 2: Usar PowerShell con permisos elevados
                $this->addHostsEntryWithPowerShell($domain, $entry);
            }
        } catch (\Exception $e) {
            Log::warning("Error al agregar entrada al archivo hosts: " . $e->getMessage());
            $this->logHostsInstructions($projectName);
        }
    }
    
    /**
     * Agregar entrada usando PowerShell con permisos elevados
     */
    private function addHostsEntryWithPowerShell(string $domain, string $entry): void
    {
        try {
            // Crear script PowerShell temporal
            $scriptContent = "
                \$hostsPath = 'C:\\Windows\\System32\\drivers\\etc\\hosts'
                \$entry = '{$entry}'
                \$domain = '{$domain}'
                
                \$content = Get-Content \$hostsPath -ErrorAction SilentlyContinue
                if (\$content -notcontains \$entry -and \$content -notmatch \$domain) {
                    Add-Content -Path \$hostsPath -Value \$entry -Encoding ASCII
                    Write-Host 'Entrada agregada exitosamente'
                } else {
                    Write-Host 'Entrada ya existe'
                }
            ";
            
            $tempScript = sys_get_temp_dir() . '\\add_hosts_' . time() . '.ps1';
            file_put_contents($tempScript, $scriptContent);
            
            // Ejecutar con permisos elevados
            $command = "powershell -Command \"Start-Process powershell -ArgumentList '-ExecutionPolicy Bypass -File \\\"{$tempScript}\\\"' -Verb RunAs -WindowStyle Hidden -Wait\"";
            exec($command, $output, $returnCode);
            
            // Limpiar script temporal
            if (file_exists($tempScript)) {
                unlink($tempScript);
            }
            
            if ($returnCode === 0) {
                Log::info("Entrada agregada con PowerShell elevado: {$domain}");
            } else {
                Log::warning("PowerShell elevado falló: " . implode("\n", $output));
                $this->logHostsInstructions($domain);
            }
            
        } catch (\Exception $e) {
            Log::warning("Error usando PowerShell elevado: " . $e->getMessage());
        }
    }
    
    /**
     * Registrar instrucciones manuales para agregar al hosts
     */
    private function logHostsInstructions(string $projectName): void
    {
        $instruction = "INSTRUCCIÓN MANUAL: Agregar al archivo hosts: 127.0.0.1      {$projectName}.test          #laragon magic!";
        Log::warning($instruction);
    }
    
    /**
     * Configurar base de datos y entorno para el proyecto
     */
    private function setupProjectDatabase(string $projectName, string $projectPath, string $projectType): void
    {
        try {
            Log::info("Configurando base de datos para el proyecto: $projectName");
            
            // Solo configurar para proyectos Laravel/PHP que necesiten base de datos
            if ($projectType === 'laravel' || $projectType === 'php') {
                
                // 1. Crear base de datos
                $databaseName = $projectName . '_db';
                $this->createDatabase($databaseName);
                
                // 2. Configurar archivo .env básico
                $this->setupBasicEnvironment($projectPath, $projectName, $databaseName);
                
                // 3. Instalar dependencias básicas si es Laravel
                if ($projectType === 'laravel') {
                    $this->installBasicDependencies($projectPath);
                    
                    // 4. Ejecutar migraciones automáticamente
                    $this->runMigrations($projectPath);
                }
                
                Log::info("Base de datos y entorno configurados para: $projectName");
            }
            
        } catch (\Exception $e) {
            Log::warning("Error configurando base de datos: " . $e->getMessage());
        }
    }
    
    /**
     * Configurar entorno básico (.env) para el proyecto
     */
    private function setupBasicEnvironment(string $projectPath, string $projectName, string $databaseName): void
    {
        try {
            $envPath = $projectPath . '\.env';
            $envExamplePath = $projectPath . '\.env.example';
            
            // Usar .env.example como base si existe
            if (file_exists($envExamplePath)) {
                copy($envExamplePath, $envPath);
                Log::info("Copiado .env.example a .env");
            }
            
            // Configuración básica para Laragon
            $envConfig = [
                'APP_NAME' => $projectName,
                'APP_ENV' => 'local',
                'APP_DEBUG' => 'true',
                'APP_URL' => "http://{$projectName}.test",
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $this->mysqlHost,
                'DB_PORT' => '3306',
                'DB_DATABASE' => $databaseName,
                'DB_USERNAME' => $this->mysqlUser,
                'DB_PASSWORD' => $this->mysqlPassword,
            ];
            
            // Actualizar o crear archivo .env
            $envContent = file_exists($envPath) ? file_get_contents($envPath) : '';
            
            foreach ($envConfig as $key => $value) {
                if (preg_match("/^{$key}=.*/m", $envContent)) {
                    $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
                } else {
                    $envContent .= "\n{$key}={$value}";
                }
            }
            
            file_put_contents($envPath, $envContent);
            Log::info("Archivo .env configurado correctamente");
            
        } catch (\Exception $e) {
            Log::warning("Error configurando .env: " . $e->getMessage());
        }
    }
    
    /**
     * Instalar dependencias básicas para Laravel
     */
    private function installBasicDependencies(string $projectPath): void
    {
        try {
            $composerPath = $projectPath . '\\composer.json';
            if (!file_exists($composerPath)) {
                return;
            }
            
            Log::info("Instalando dependencias básicas de Composer");
            
            // Comando básico sin dependencias pesadas
            $command = "cd /d \"{$projectPath}\" && composer install --no-interaction --ignore-platform-reqs --no-dev --prefer-dist --no-scripts";
            
            // Ejecutar con timeout
            $descriptorspec = [
                0 => ["pipe", "r"],
                1 => ["pipe", "w"],
                2 => ["pipe", "w"]
            ];
            
            $process = proc_open($command, $descriptorspec, $pipes);
            
            if (is_resource($process)) {
                stream_set_timeout($pipes[1], 120); // 2 minutos para instalación básica
                
                $output = stream_get_contents($pipes[1]);
                $error = stream_get_contents($pipes[2]);
                
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                
                $returnCode = proc_close($process);
                
                if ($returnCode === 0) {
                    Log::info("Dependencias básicas instaladas exitosamente");
                    
                    // Generar key si es Laravel
                    $this->generateAppKey($projectPath);
                } else {
                    Log::warning("Error instalando dependencias básicas: $error");
                }
            }
            
        } catch (\Exception $e) {
            Log::warning("Error en instalación de dependencias: " . $e->getMessage());
        }
    }
    
    /**
     * Generar APP_KEY para Laravel
     */
    private function generateAppKey(string $projectPath): void
    {
        try {
            $artisanPath = $projectPath . '\\artisan';
            if (file_exists($artisanPath)) {
                $command = "cd /d \"{$projectPath}\" && php artisan key:generate --force";
                exec($command . ' 2>&1', $output, $returnCode);
                
                if ($returnCode === 0) {
                    Log::info("APP_KEY generado exitosamente");
                } else {
                    Log::warning("Error generando APP_KEY: " . implode("\n", $output));
                }
            }
        } catch (\Exception $e) {
            Log::warning("Error generando APP_KEY: " . $e->getMessage());
        }
    }
    
    /**
     * Recargar Apache
     */
    private function reloadApache(): void
    {
        try {
            // Intentar recargar Apache
            $apachePath = 'C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\httpd.exe';
            if (file_exists($apachePath)) {
                exec("\"$apachePath\" -k restart", $output, $returnCode);
                if ($returnCode === 0) {
                    Log::info("Apache recargado exitosamente");
                } else {
                    Log::warning("Error al recargar Apache: " . implode("\n", $output));
                }
            }
        } catch (\Exception $e) {
            Log::warning("No se pudo recargar Apache automáticamente: " . $e->getMessage());
        }
    }
    
    /**
     * Reiniciar Laragon completamente para que tome los cambios
     */
    private function restartLaragon(): void
    {
        try {
            Log::info("Reiniciando Laragon para aplicar cambios...");
            
            // 1. Detener Apache y MySQL
            $this->stopLaragonServices();
            
            // 2. Esperar un momento
            sleep(2);
            
            // 3. Iniciar Apache y MySQL nuevamente
            $this->startLaragonServices();
            
            Log::info("Laragon reiniciado exitosamente");
            
        } catch (\Exception $e) {
            Log::warning("Error reiniciando Laragon: " . $e->getMessage());
            // Intentar reinicio alternativo
            $this->alternativeRestart();
        }
    }
    
    /**
     * Detener servicios de Laragon
     */
    private function stopLaragonServices(): void
    {
        try {
            Log::info("Deteniendo todos los servicios de Laragon...");
            
            // Detener Apache completamente
            exec('taskkill /F /IM httpd.exe 2>NUL', $apacheOutput, $apacheReturn);
            if ($apacheReturn === 0) {
                Log::info("Apache detenido exitosamente");
            }
            
            // Detener procesos secundarios de MySQL (no el servicio principal)
            exec('taskkill /F /IM mysqld.exe /FI "WINDOWTITLE ne MySQL*" 2>NUL', $mysqlOutput, $mysqlReturn);
            Log::info("Procesos MySQL secundarios detenidos");
            
            Log::info("Servicios de Laragon detenidos");
            
        } catch (\Exception $e) {
            Log::warning("Error deteniendo servicios: " . $e->getMessage());
        }
    }
    
    /**
     * Iniciar servicios de Laragon
     */
    private function startLaragonServices(): void
    {
        try {
            Log::info("Iniciando todos los servicios de Laragon...");
            
            // Iniciar Apache
            $apachePath = 'C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\httpd.exe';
            if (file_exists($apachePath)) {
                // Iniciar Apache en segundo plano
                $command = "start /B \"Apache\" \"$apachePath\"";
                exec($command, $output, $returnCode);
                Log::info("Apache iniciado");
                
                // Esperar a que Apache se estabilice
                sleep(2);
                
                // Verificar que Apache esté corriendo
                $apacheCheck = shell_exec('tasklist /FI "IMAGENAME eq httpd.exe" 2>NUL | find /I "httpd.exe"');
                if (!empty($apacheCheck)) {
                    Log::info("Apache confirmado corriendo");
                } else {
                    Log::warning("Apache puede no estar corriendo correctamente");
                }
            }
            
            Log::info("Servicios de Laragon iniciados - Laragon debería detectar proyectos automáticamente");
            
        } catch (\Exception $e) {
            Log::warning("Error iniciando servicios: " . $e->getMessage());
        }
    }
    
    /**
     * Reinicio alternativo más suave
     */
    private function alternativeRestart(): void
    {
        try {
            Log::info("Intentando reinicio alternativo...");
            
            // Solo recargar Apache de forma suave
            $apachePath = 'C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\httpd.exe';
            if (file_exists($apachePath)) {
                // Graceful restart
                exec("\"$apachePath\" -k graceful", $output, $returnCode);
                Log::info("Apache reiniciado de forma suave");
            }
            
        } catch (\Exception $e) {
            Log::warning("Error en reinicio alternativo: " . $e->getMessage());
        }
    }
    
    /**
     * Reiniciar Laragon completamente para activar detección automática de proyectos
     */
    public function restartLaragonForProject(string $projectName): void
    {
        try {
            Log::info("Iniciando reinicio completo de Laragon para activar detección de proyectos...");
            
            // 1. Detener servicios de Laragon
            $this->stopLaragonServices();
            
            // 2. Esperar a que los procesos terminen completamente
            sleep(4);
            
            // 3. Limpiar configuraciones en memoria si es posible
            Log::info("Limpiando configuraciones temporales...");
            
            // 4. Iniciar servicios de Laragon
            $this->startLaragonServices();
            
            // 5. Esperar a que se estabilicen los servicios
            sleep(3);
            
            // 6. Verificar que el proyecto sea accesible
            $projectUrl = "http://{$projectName}.test";
            Log::info("Proyecto debería estar disponible en: $projectUrl");
            
            // 7. Intentar trigger de la detección automática
            $this->triggerLaragonProjectDetection();
            
            // 8. Verificar si todos los proyectos están en hosts
            sleep(2);
            $this->ensureAllProjectsInHosts();
            
            // 9. Crear virtual hosts automáticamente para proyectos faltantes
            $this->createMissingVirtualHosts();
            
            Log::info("Reinicio completo de Laragon completado. Verificar acceso a todos los proyectos.");
            
        } catch (\Exception $e) {
            Log::error("Error en reinicio completo de Laragon: " . $e->getMessage());
            throw new \Exception("No se pudo reiniciar Laragon para activar detección de proyectos: " . $e->getMessage());
        }
    }
    
    /**
     * Intentar activar la detección automática de proyectos en Laragon
     */
    private function triggerLaragonProjectDetection(): void
    {
        try {
            Log::info("Activando detección automática de proyectos...");
            
            // 1. Asegurar que Laragon.exe esté ejecutándose
            $this->ensureLaragonGUIRunning();
            
            // 2. Buscar el ejecutable de Laragon para trigger automático
            $laragonExe = 'C:\laragon\laragon.exe';
            if (file_exists($laragonExe)) {
                // Intentar enviar señal de refresh a Laragon
                exec("\"$laragonExe\" --refresh 2>NUL", $output, $returnCode);
                Log::info("Señal de refresh enviada a Laragon");
                
                // Esperar un momento para que procese
                sleep(2);
                
                // Intentar forzar actualización de virtual hosts
                exec("\"$laragonExe\" --reload-vhosts 2>NUL", $output, $returnCode);
                Log::info("Señal de recarga de virtual hosts enviada");
            }
            
            // 3. Crear un archivo temporal para forzar re-escaneo
            $triggerFile = 'C:\laragon\www\.laragon_refresh_' . time();
            file_put_contents($triggerFile, 'trigger');
            
            // 4. Esperar un momento y eliminar el archivo
            sleep(1);
            if (file_exists($triggerFile)) {
                unlink($triggerFile);
            }
            
            // 5. Intentar recargar configuración de Apache directamente
            $this->reloadApacheVirtualHosts();
            
            Log::info("Detección automática activada");
            
        } catch (\Exception $e) {
            Log::warning("No se pudo activar detección automática: " . $e->getMessage());
        }
    }
    
    /**
     * Asegurar que la GUI de Laragon esté ejecutándose
     */
    private function ensureLaragonGUIRunning(): void
    {
        try {
            // Verificar si Laragon.exe está ejecutándose
            $laragonCheck = shell_exec('tasklist /FI "IMAGENAME eq laragon.exe" 2>NUL | find /I "laragon.exe"');
            
            if (empty($laragonCheck)) {
                Log::info("Laragon GUI no está ejecutándose, iniciando...");
                
                $laragonExe = 'C:\laragon\laragon.exe';
                if (file_exists($laragonExe)) {
                    // Iniciar Laragon en segundo plano
                    $command = "start /B \"\" \"$laragonExe\"";
                    exec($command, $output, $returnCode);
                    
                    // Esperar a que se inicie
                    sleep(3);
                    
                    // Verificar que se haya iniciado
                    $laragonCheck = shell_exec('tasklist /FI "IMAGENAME eq laragon.exe" 2>NUL | find /I "laragon.exe"');
                    if (!empty($laragonCheck)) {
                        Log::info("Laragon GUI iniciado exitosamente");
                    } else {
                        Log::warning("No se pudo verificar que Laragon GUI esté ejecutándose");
                    }
                }
            } else {
                Log::info("Laragon GUI ya está ejecutándose");
            }
            
        } catch (\Exception $e) {
            Log::warning("Error verificando/iniciando Laragon GUI: " . $e->getMessage());
        }
    }
    
    /**
     * Recargar virtual hosts de Apache directamente
     */
    private function reloadApacheVirtualHosts(): void
    {
        try {
            Log::info("Recargando virtual hosts de Apache...");
            
            // Buscar la configuración de Apache de Laragon
            $apacheConfigPath = 'C:\laragon\etc\apache2\httpd.conf';
            $sitesEnabledPath = 'C:\laragon\etc\apache2\sites-enabled';
            
            if (is_dir($sitesEnabledPath)) {
                // Listar archivos de virtual hosts
                $vhostFiles = glob($sitesEnabledPath . '/*.conf');
                Log::info("Virtual hosts encontrados: " . count($vhostFiles));
                
                // Intentar recargar gracefully Apache
                $apachePath = 'C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\httpd.exe';
                if (file_exists($apachePath)) {
                    exec("\"$apachePath\" -k graceful 2>NUL", $output, $returnCode);
                    if ($returnCode === 0) {
                        Log::info("Apache recargado gracefully");
                    }
                }
            }
            
        } catch (\Exception $e) {
            Log::warning("Error recargando virtual hosts: " . $e->getMessage());
        }
    }
    
    /**
     * Asegurar que todos los proyectos en www estén en el archivo hosts
     */
    private function ensureAllProjectsInHosts(): void
    {
        try {
            Log::info("Verificando que todos los proyectos estén en el archivo hosts...");
            
            $wwwPath = 'C:\laragon\www';
            $hostsFile = 'C:\Windows\System32\drivers\etc\hosts';
            
            // Obtener contenido actual del hosts
            $hostsContent = '';
            if (file_exists($hostsFile) && is_readable($hostsFile)) {
                $hostsContent = file_get_contents($hostsFile);
            }
            
            // Obtener todos los directorios de proyectos (excluyendo tesisv1)
            $projects = [];
            if (is_dir($wwwPath)) {
                $directories = array_filter(glob($wwwPath . '/*'), 'is_dir');
                foreach ($directories as $dir) {
                    $projectName = basename($dir);
                    // Excluir archivos zip y el proyecto tesisv1
                    if ($projectName !== 'tesisv1' && !str_ends_with($projectName, '.zip')) {
                        $projects[] = $projectName;
                    }
                }
            }
            
            $missingProjects = [];
            foreach ($projects as $project) {
                $hostEntry = "127.0.0.1      {$project}.test          #laragon magic!";
                if (strpos($hostsContent, "{$project}.test") === false) {
                    $missingProjects[] = $project;
                }
            }
            
            if (!empty($missingProjects)) {
                Log::info("Proyectos faltantes en hosts: " . implode(', ', $missingProjects));
                
                // Intentar múltiples métodos para agregar automáticamente
                $success = $this->addToHostsFileWithElevation($missingProjects);
                
                if (!$success) {
                    Log::warning("INSTRUCCIÓN MANUAL: Agregar al archivo hosts como administrador:");
                    foreach ($missingProjects as $project) {
                        Log::warning("127.0.0.1      {$project}.test          #laragon magic!");
                    }
                    
                    // Crear script batch temporal para facilitar la tarea
                    $this->createHostsUpdateScript($missingProjects);
                }
            } else {
                Log::info("Todos los proyectos están correctamente configurados en el archivo hosts");
            }
            
        } catch (\Exception $e) {
            Log::warning("Error verificando archivo hosts: " . $e->getMessage());
        }
    }
    
    /**
     * Intentar agregar proyectos al archivo hosts con elevación de privilegios
     */
    private function addToHostsFileWithElevation(array $missingProjects): bool
    {
        try {
            $hostsFile = 'C:\Windows\System32\drivers\etc\hosts';
            
            // Método 1: Intentar escribir directamente
            $hostsContent = file_get_contents($hostsFile);
            $newEntries = "\n";
            foreach ($missingProjects as $project) {
                $newEntries .= "127.0.0.1      {$project}.test          #laragon magic!\n";
            }
            
            $writeResult = @file_put_contents($hostsFile, $hostsContent . $newEntries, LOCK_EX);
            
            if ($writeResult !== false) {
                Log::info("Proyectos agregados automáticamente al archivo hosts: " . implode(', ', $missingProjects));
                return true;
            }
            
            // Método 2: Intentar con PowerShell como administrador
            $tempScript = tempnam(sys_get_temp_dir(), 'laragon_hosts_') . '.ps1';
            $scriptContent = '';
            foreach ($missingProjects as $project) {
                $scriptContent .= "Add-Content -Path 'C:\\Windows\\System32\\drivers\\etc\\hosts' -Value '127.0.0.1      {$project}.test          #laragon magic!' -Encoding ASCII\n";
            }
            
            file_put_contents($tempScript, $scriptContent);
            
            // Ejecutar PowerShell como administrador
            $command = "powershell -Command \"Start-Process powershell -ArgumentList '-ExecutionPolicy Bypass -File \\\"{$tempScript}\\\"' -Verb RunAs -WindowStyle Hidden\"";
            exec($command, $output, $returnCode);
            
            // Limpiar archivo temporal
            if (file_exists($tempScript)) {
                unlink($tempScript);
            }
            
            // Verificar si funcionó esperando un momento
            sleep(2);
            $updatedHosts = file_get_contents($hostsFile);
            foreach ($missingProjects as $project) {
                if (strpos($updatedHosts, "{$project}.test") !== false) {
                    Log::info("Proyecto agregado exitosamente al hosts: {$project}.test");
                    return true;
                }
            }
            
            return false;
            
        } catch (\Exception $e) {
            Log::warning("Error intentando agregar al hosts con elevación: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crear script batch para facilitar la actualización manual del hosts
     */
    private function createHostsUpdateScript(array $missingProjects): void
    {
        try {
            $scriptPath = 'C:\laragon\www\tesisv1\storage\app\update_hosts.bat';
            $scriptContent = "@echo off\necho Agregando proyectos al archivo hosts...\n\n";
            
            foreach ($missingProjects as $project) {
                $scriptContent .= "echo 127.0.0.1      {$project}.test          #laragon magic! >> C:\\Windows\\System32\\drivers\\etc\\hosts\n";
            }
            
            $scriptContent .= "\necho Proyectos agregados exitosamente!\npause\n";
            
            file_put_contents($scriptPath, $scriptContent);
            Log::info("Script de actualización creado en: {$scriptPath}");
            Log::info("EJECUTAR COMO ADMINISTRADOR: {$scriptPath}");
            
        } catch (\Exception $e) {
            Log::warning("Error creando script de actualización: " . $e->getMessage());
        }
    }
    
    /**
     * Crear virtual hosts automáticamente para proyectos faltantes
     */
    private function createMissingVirtualHosts(): void
    {
        try {
            Log::info("Creando virtual hosts automáticamente para proyectos faltantes...");
            
            $wwwPath = 'C:\laragon\www';
            $sitesEnabledPath = 'C:\laragon\etc\apache2\sites-enabled';
            
            if (!is_dir($sitesEnabledPath)) {
                Log::warning("Directorio sites-enabled no encontrado: {$sitesEnabledPath}");
                return;
            }
            
            // Obtener todos los proyectos
            $projects = [];
            if (is_dir($wwwPath)) {
                $directories = array_filter(glob($wwwPath . '/*'), 'is_dir');
                foreach ($directories as $dir) {
                    $projectName = basename($dir);
                    if ($projectName !== 'tesisv1' && !str_ends_with($projectName, '.zip')) {
                        $projects[] = $projectName;
                    }
                }
            }
            
            foreach ($projects as $project) {
                $vhostFile = "{$sitesEnabledPath}/auto.{$project}.test.conf";
                
                // Si el virtual host no existe, crearlo
                if (!file_exists($vhostFile)) {
                    $this->createVirtualHost($project);
                    Log::info("Virtual host creado para: {$project}.test");
                }
            }
            
            // Recargar Apache después de crear los virtual hosts
            $this->reloadApacheVirtualHosts();
            
        } catch (\Exception $e) {
            Log::warning("Error creando virtual hosts automáticamente: " . $e->getMessage());
        }
    }
    
    /**
     * Recargar configuración de Apache
     */
    private function reloadApacheConfiguration(): void
    {
        try {
            $apachePath = 'C:\laragon\bin\apache\httpd-2.4.54-win64-VS16\bin\httpd.exe';
            if (file_exists($apachePath)) {
                // Graceful restart para recargar configuración
                exec("\"$apachePath\" -k graceful", $output, $returnCode);
                if ($returnCode === 0) {
                    Log::info("Configuración de Apache recargada exitosamente");
                } else {
                    Log::warning("Error recargando configuración de Apache: " . implode("\n", $output));
                }
            }
        } catch (\Exception $e) {
            Log::warning("Error recargando configuración de Apache: " . $e->getMessage());
        }
    }
    
    /**
     * Verificar que el proyecto sea accesible
     */
    private function verifyProjectAccess(string $projectName): void
    {
        try {
            $projectUrl = "http://{$projectName}.test";
            
            // Verificar que curl esté disponible
            if (function_exists('curl_init')) {
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $projectUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);
                
                if ($httpCode === 200 || $httpCode === 302) {
                    Log::info("Proyecto $projectName es accesible en $projectUrl");
                } else {
                    Log::warning("Proyecto $projectName no responde correctamente (HTTP $httpCode)");
                }
            }
        } catch (\Exception $e) {
            Log::warning("Error verificando acceso al proyecto $projectName: " . $e->getMessage());
        }
    }
}