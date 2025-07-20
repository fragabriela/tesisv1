<?php

namespace App\Services;

use App\Models\Tesis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DockerService
{
    /**
     * API URL del servidor Docker
     * 
     * @var string
     */
    protected $apiUrl;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiUrl = config('docker.api_url', 'http://localhost:2375');
    }

    /**
     * Clonar un repositorio de GitHub
     * 
     * @param string $repoUrl
     * @return string|null Path to cloned repo
     */
    public function cloneRepository($repoUrl)
    {
        try {
            $repoDir = 'repos/' . Str::random(10);
            $fullPath = storage_path('app/public/' . $repoDir);
            
            if (!file_exists(dirname($fullPath))) {
                mkdir(dirname($fullPath), 0777, true);
            }
            
            exec("git clone {$repoUrl} {$fullPath} 2>&1", $output, $returnVar);
            
            if ($returnVar !== 0) {
                Log::error('Error cloning repository: ' . implode("\n", $output));
                return null;
            }
            
            return $repoDir;
        } catch (\Exception $e) {
            Log::error('Error cloning repository: ' . $e->getMessage());
            return null;
        }
    }    /**
     * Detectar el tipo de proyecto basado en los archivos del repositorio
     * 
     * @param string $repoPath
     * @return string|null
     */
    public function detectProjectType($repoPath)
    {
        $fullPath = storage_path('app/public/' . $repoPath);
        
        // Verificación mejorada de Laravel con múltiples indicadores
        if (file_exists($fullPath . '/artisan') || 
            (file_exists($fullPath . '/composer.json') && 
            (strpos(file_get_contents($fullPath . '/composer.json'), 'laravel/framework') !== false ||
             strpos(file_get_contents($fullPath . '/composer.json'), 'laravel/laravel') !== false))) {
            return 'laravel';
        } elseif (file_exists($fullPath . '/pom.xml')) {
            return 'java-maven';
        } elseif (file_exists($fullPath . '/build.gradle')) {
            return 'java-gradle';
        } elseif (glob($fullPath . '/*.java')) {
            return 'java';
        } elseif (file_exists($fullPath . '/package.json') && !file_exists($fullPath . '/composer.json')) {
            // Solo es Node si tiene package.json pero no tiene composer.json
            return 'node';
        } elseif (file_exists($fullPath . '/requirements.txt') || file_exists($fullPath . '/setup.py')) {
            return 'python';
        } elseif (file_exists($fullPath . '/composer.json')) {
            // Proyectos PHP genéricos con Composer pero sin Laravel
            return 'php';
        }
        
        return 'unknown';
    }

    /**
     * Detectar la versión de PHP requerida basada en composer.json
     * 
     * @param string $repoPath
     * @return string
     */
    private function detectRequiredPhpVersion($repoPath)
    {
        $fullPath = storage_path('app/public/' . $repoPath);
        $composerPath = $fullPath . '/composer.json';
        
        if (!file_exists($composerPath)) {
            return '8.2'; // Versión por defecto actualizada para mejor compatibilidad
        }
        
        try {
            $composerContent = json_decode(file_get_contents($composerPath), true);
            
            if (isset($composerContent['require']['php'])) {
                $phpRequirement = $composerContent['require']['php'];
                
                // Extraer la versión mínima requerida
                if (preg_match('/>=?\s*(\d+\.\d+)/', $phpRequirement, $matches)) {
                    $requiredVersion = $matches[1];
                    
                    // Mapear a versiones disponibles de Docker
                    if (version_compare($requiredVersion, '8.3', '>=')) {
                        return '8.3';
                    } elseif (version_compare($requiredVersion, '8.2', '>=')) {
                        return '8.2';
                    } elseif (version_compare($requiredVersion, '8.1', '>=')) {
                        return '8.1';
                    } elseif (version_compare($requiredVersion, '8.0', '>=')) {
                        return '8.0';
                    } else {
                        return '7.4';
                    }
                }
            }
            
            // Si no hay requerimiento específico, revisar las dependencias para detectar versiones
            if (isset($composerContent['require'])) {
                foreach ($composerContent['require'] as $package => $version) {
                    // Laravel 11+ requiere PHP 8.2+
                    if ($package === 'laravel/framework' && preg_match('/\^11\./', $version)) {
                        return '8.2';
                    }
                    // Laravel 10+ requiere PHP 8.1+
                    if ($package === 'laravel/framework' && preg_match('/\^10\./', $version)) {
                        return '8.1';
                    }
                    // Symfony 7+ requiere PHP 8.2+
                    if (strpos($package, 'symfony/') === 0 && preg_match('/\^7\./', $version)) {
                        return '8.2';
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Error detecting PHP version from composer.json: ' . $e->getMessage());
        }
        
        return '8.2'; // Versión por defecto actualizada para mejor compatibilidad
    }

    /**
     * Crear un Dockerfile basado en el tipo de proyecto
     * 
     * @param string $repoPath
     * @param string $projectType
     * @return bool
     */
    public function createDockerfile($repoPath, $projectType)
    {
        $fullPath = storage_path('app/public/' . $repoPath);
        $dockerfilePath = $fullPath . '/Dockerfile';
        
        if (file_exists($dockerfilePath)) {
            // Verificar si el Dockerfile tiene una versión de PHP obsoleta
            $dockerfileContent = file_get_contents($dockerfilePath);
            if (preg_match('/FROM php:(\d+\.\d+)-/', $dockerfileContent, $matches)) {
                $currentPhpVersion = $matches[1];
                $requiredPhpVersion = $this->detectRequiredPhpVersion($repoPath);
                
                // Si la versión actual es diferente a la requerida, regenerar Dockerfile
                if (version_compare($currentPhpVersion, $requiredPhpVersion, '<')) {
                    Log::info("Regenerating Dockerfile: current PHP {$currentPhpVersion} < required PHP {$requiredPhpVersion}");
                    // Eliminar Dockerfile existente para forzar regeneración
                    unlink($dockerfilePath);
                } else {
                    // Dockerfile tiene versión adecuada, usarlo
                    return true;
                }
            }
        }
        
        // Detectar la versión de PHP requerida
        $phpVersion = $this->detectRequiredPhpVersion($repoPath);
        Log::info("Detected PHP version requirement: {\$phpVersion} for project at {\$repoPath}");
        
        $dockerfile = '';
        switch ($projectType) {
            case 'laravel':
            case 'php': // Tratar proyectos PHP genéricos
                // Determinar si el proyecto tiene un directorio público
                $hasPublicDir = is_dir($fullPath . '/public');
                $documentRoot = $hasPublicDir ? '/var/www/html/public' : '/var/www/html';
                
                $dockerfile = <<<EOT
FROM php:{$phpVersion}-apache

# Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    curl \
    libonig-dev \
    libxml2-dev \
    && rm -rf /var/lib/apt/lists/*

# Configurar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo_mysql gd zip mbstring xml

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

EOT;

                // Configurar Apache según el tipo de estructura del proyecto
                if ($hasPublicDir) {
                    $dockerfile .= <<<EOT
# Configurar Apache para usar el directorio público
RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

EOT;
                } else {
                    $dockerfile .= <<<EOT
# Configurar Apache para usar el directorio raíz del proyecto
RUN echo '<Directory /var/www/html>

    Options Indexes FollowSymLinks

    AllowOverride All

    Require all granted

</Directory>' > /etc/apache2/conf-available/project-permissions.conf \
    && a2enconf project-permissions

EOT;
                }

                $dockerfile .= <<<EOT
RUN a2enmod rewrite

# Establecer directorio de trabajo
WORKDIR /var/www/html

# Copiar proyecto
COPY . .

# Configurar git para evitar errores de ownership
RUN git config --global --add safe.directory /var/www/html || true

# Instalar dependencias de Composer
RUN if [ -f "composer.json" ]; then \
        composer install --no-interaction --optimize-autoloader --no-dev --ignore-platform-reqs || \
        composer install --no-interaction --no-dev --ignore-platform-reqs || \
        echo "Composer install failed, continuing without dependencies"; \
    fi

# Configurar permisos para proyectos Laravel/PHP
RUN if [ -d "storage" ]; then \
        chown -R www-data:www-data /var/www/html/storage; \
        chmod -R 775 /var/www/html/storage; \
    fi
RUN if [ -d "bootstrap/cache" ]; then \
        chown -R www-data:www-data /var/www/html/bootstrap/cache; \
        chmod -R 775 /var/www/html/bootstrap/cache; \
    fi

# Configurar permisos generales
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

# Crear archivo .env si no existe pero hay un .env.example
RUN if [ ! -f .env ] && [ -f .env.example ]; then \
        cp .env.example .env; \
        if [ -f "artisan" ]; then \
            php artisan key:generate --no-interaction || true; \
        fi; \
    fi

# Configurar variables de entorno para proyectos Laravel
RUN if [ -f "artisan" ]; then \
        echo "APP_ENV=production" >> .env; \
        echo "APP_DEBUG=false" >> .env; \
        echo "DB_CONNECTION=sqlite" >> .env; \
        echo "DB_DATABASE=:memory:" >> .env; \
    fi

EXPOSE 80

# Comando de inicio
CMD ["apache2-foreground"]
EOT;
                break;
        }

        return $dockerfile;
    }

    /**
     * Crear un archivo Docker Compose para el proyecto
     * 
     * @param string $repoPath
     * @param string $projectType
     * @return bool
     */
    public function createDockerCompose($repoPath, $projectType, $customContainerName = null)
    {
        $fullPath = storage_path('app/public/' . $repoPath);
        $composePath = $fullPath . '/docker-compose.yml';
        
        if (file_exists($composePath)) {
            // Ya existe un archivo docker-compose.yml, usaremos ese
            return true;
        }
        
        $projectName = basename($repoPath);
        $containerName = $customContainerName ?? Str::slug($projectName) . '-' . Str::random(5);
        
        // Determinar el puerto interno según el tipo de proyecto
        $port = $this->getInternalPort($projectType);
        
        // Crear un docker-compose.yml simple y compatible (sin version obsoleta)
        $compose = <<<EOT
services:
  app:
    build: .
    container_name: {$containerName}
    restart: unless-stopped
    ports:
      - "0:{$port}"
EOT;

        // Agregar variables de entorno sólo para proyectos PHP/Laravel
        if ($projectType === 'laravel' || $projectType === 'php') {
            $compose .= <<<EOT

    environment:
      APP_ENV: production
      APP_DEBUG: 'false'
      DB_CONNECTION: sqlite
      DB_DATABASE: ':memory:'
EOT;
        }
        
        file_put_contents($composePath, $compose);
        return true;
    }    /**
     * Verificar si Docker está disponible
     * 
     * @return bool
     */
    public function checkDockerAvailability()
    {
        try {
            // Comprobar si Docker está instalado y disponible
            exec('docker --version 2>&1', $output, $returnVar);
            
            if ($returnVar !== 0) {
                Log::error('Docker no está disponible: ' . implode("\n", $output));
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error verificando disponibilidad de Docker: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Construir y ejecutar un contenedor para un proyecto
     * 
     * @param Tesis $tesis
     * @return array|null
     */
    public function buildAndRunProject(Tesis $tesis)
    {
        try {
            // Verificar si Docker está disponible
            if (!$this->checkDockerAvailability()) {
                throw new \Exception('Docker no está instalado o no está accesible en este sistema.');
            }
            
            $repoPath = storage_path('app/public/' . $tesis->project_repo_path);
            $projectType = $tesis->project_type;
            
            // Asegurarnos que exista el Dockerfile y docker-compose.yml
            if (!$this->createDockerfile($tesis->project_repo_path, $projectType)) {
                throw new \Exception("No se pudo crear el Dockerfile para el tipo de proyecto: $projectType");
            }
            
            if (!$this->createDockerCompose($tesis->project_repo_path, $projectType)) {
                throw new \Exception("No se pudo crear el archivo docker-compose.yml para el tipo de proyecto: $projectType");
            }
            
            // Verificar que los archivos se crearon correctamente
            if (!file_exists($repoPath . '/Dockerfile')) {
                throw new \Exception("El Dockerfile no existe en la ruta esperada: $repoPath/Dockerfile");
            }
            
            if (!file_exists($repoPath . '/docker-compose.yml')) {
                throw new \Exception("El archivo docker-compose.yml no existe en la ruta esperada: $repoPath/docker-compose.yml");
            }
            
            // Generar un nombre único para el contenedor
            $containerName = 'tesis-' . $tesis->id . '-' . Str::random(5);
            
            // Asegurarse de que el docker-compose.yml use el mismo nombre de contenedor
            if (!$this->createDockerCompose($tesis->project_repo_path, $projectType, $containerName)) {
                throw new \Exception("No se pudo crear el archivo docker-compose.yml para el tipo de proyecto: $projectType");
            }
            
            // Registrar comandos que vamos a ejecutar
            Log::info('Ejecutando docker compose en el directorio: ' . $repoPath);
            
            // Intentar usar "docker compose" (nuevo formato con espacio)
            $cmd = "cd {$repoPath} && docker compose up -d 2>&1";
            Log::info("Ejecutando comando: $cmd");
            exec($cmd, $output, $returnVar);
            
            // Si falla, intentar con "docker-compose" (formato antiguo con guión)
            if ($returnVar !== 0) {
                Log::warning('Comando "docker compose" falló con código: ' . $returnVar . ', output: ' . implode("\n", $output));
                Log::warning('Intentando con "docker-compose"...');
                
                $cmd = "cd {$repoPath} && docker-compose up -d 2>&1";
                Log::info("Ejecutando comando: $cmd");
                exec($cmd, $output, $returnVar);
                
                if ($returnVar !== 0) {
                    Log::error('Error building and running container: ' . implode("\n", $output));
                    throw new \Exception('No se pudo ejecutar el contenedor Docker. Error: ' . implode("\n", $output));
                }
            }
            
            // Añadir un pequeño retraso para asegurar que el contenedor esté en funcionamiento
            Log::info('Esperando 5 segundos para que el contenedor inicie...');
            sleep(5);
            
            // Obtener información del contenedor - comprobamos primero el nombre exacto
            $cmd = 'docker ps --filter "name=' . $containerName . '" --format "{{.ID}}|{{.Ports}}"';
            exec($cmd, $containerInfo, $returnVar);
            
            // Si no encontramos con el nombre exacto, intentamos diferentes patrones
            if (empty($containerInfo)) {
                // Listar todos los contenedores para análisis
                exec('docker ps', $allContainers);
                Log::info("Contenedores activos: " . implode("\n", $allContainers));
                
                // Patrón 1: Búsqueda por basename del directorio (más genérica)
                $baseName = basename($repoPath);
                Log::info("No se encontró contenedor con nombre exacto. Buscando contenedores que contengan: " . $baseName);
                // Usamos una búsqueda más amplia sin filtrado previo para capturar todos los posibles matches
                exec('docker ps --format "{{.ID}}|{{.Ports}}|{{.Names}}"', $allContainerDetails);
                
                // Obtener lista completa con todos los detalles relevantes
                exec('docker ps --format "{{.ID}}|{{.Image}}|{{.Names}}|{{.Ports}}"', $allContainerDetails);
                Log::info("Buscando entre " . count($allContainerDetails) . " contenedores.");
                
                // Filtrar manualmente los resultados para encontrar coincidencias
                foreach ($allContainerDetails as $container) {
                    $parts = explode('|', $container);
                    if (count($parts) >= 4) {
                        $id = $parts[0];
                        $image = $parts[1];
                        $name = $parts[2];
                        $ports = $parts[3];
                        
                        Log::info("Evaluando: ID=$id, Image=$image, Name=$name");
                        
                        // Verificar si el nombre del contenedor o la imagen contienen el nombre base del directorio
                        if (stripos($name, $baseName) !== false || 
                            stripos($name, str_replace('_', '-', $baseName)) !== false || 
                            stripos($image, $baseName) !== false ||
                            stripos($image, str_replace('_', '-', $baseName)) !== false) {
                            
                            Log::info("¡MATCH! Encontrado contenedor relacionado por nombre o imagen: $name ($image)");
                            $containerInfo[] = "$id|$ports";
                            break;
                        }
                    }
                }
            }
            
            if ($returnVar !== 0) {
                Log::error('Error executing docker ps command: ' . implode("\n", $containerInfo));
                return null;
            }
            
            if (empty($containerInfo)) {
                // Último intento - buscar cualquier contenedor creado recientemente (últimos 60 segundos)
                Log::info('Intentando encontrar contenedor por tiempo de creación reciente...');
                exec('docker ps --filter "since=60s" --format "{{.ID}}|{{.Ports}}|{{.Names}}"', $recentContainers);
                
                if (!empty($recentContainers)) {
                    Log::info("Contenedores recientes encontrados: " . count($recentContainers));
                    // Tomar el contenedor más reciente
                    $containerParts = explode('|', $recentContainers[0]);
                    $containerInfo[] = $containerParts[0] . '|' . $containerParts[1];
                    Log::info("Usando el contenedor más reciente: " . $containerParts[2]);
                } else {
                    Log::error('No containers found matching any pattern or created recently');
                    // List all running containers to help debug
                    exec("docker ps", $allContainers);
                    Log::info('Running containers: ' . implode("\n", $allContainers));
                    return null;
                }
            }
            
            // Obtener información más completa del contenedor
            Log::info('Container info found: ' . $containerInfo[0]);
            $infoParts = explode('|', $containerInfo[0]);
            $containerId = $infoParts[0];
            $ports = $infoParts[1];
            
            // Obtener el nombre real del contenedor
            $cmd = 'docker ps --filter "id=' . $containerId . '" --format "{{.Names}}"';
            exec($cmd, $nameOutput, $nameReturnVar);
            $actualContainerName = $nameOutput[0] ?? $containerName;
            
            // Extraer el puerto mapeado - compatible con varios formatos
            $port = null;
            
            // Formato IPv4: 0.0.0.0:32774->80/tcp
            if (preg_match('/0.0.0.0:(\d+)/', $ports, $matches)) {
                $port = $matches[1];
                Log::info("Puerto encontrado (formato IPv4): $port");
            } 
            // Formato IPv6: [::]:32774->80/tcp
            elseif (preg_match('/\[\:\:\]:(\d+)/', $ports, $matches)) {
                $port = $matches[1];
                Log::info("Puerto encontrado (formato IPv6): $port");
            }
            // Formato alternativo: *:32774->80/tcp
            elseif (preg_match('/:(\d+)->/', $ports, $matches)) {
                $port = $matches[1];
                Log::info("Puerto encontrado (formato alternativo): $port");
            }
            
            if (!$port) {
                // Intento de recuperación: obtener la información directamente de Docker
                $cmd = 'docker port ' . $containerId . ' 80';
                exec($cmd, $portOutput, $portReturnVar);
                
                if ($portReturnVar === 0 && !empty($portOutput)) {
                    // Formato de respuesta: 0.0.0.0:32774
                    if (preg_match('/:(\d+)$/', $portOutput[0], $matches)) {
                        $port = $matches[1];
                        Log::info("Puerto encontrado (usando docker port): $port");
                    }
                }
            }
            
            if (!$port) {
                Log::error('Could not determine container port from: ' . $ports);
                return null;
            }
              // Generar la URL del proyecto usando la ruta correcta
            $projectUrl = route('proyectos.show', $tesis->id);
            
            return [
                'container_id' => $containerId,
                'container_status' => 'running',
                'project_url' => $projectUrl,
                'port' => $port,
                'project_config' => [
                    'container_name' => $actualContainerName, // Usar el nombre real del contenedor
                    'internal_port' => $this->getInternalPort($projectType),
                    'external_port' => $port,
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Error building and running project: ' . $e->getMessage());
            return null;
        }
    }
      /**
     * Obtener el puerto interno basado en el tipo de proyecto
     * 
     * @param string $projectType
     * @return int
     */
    private function getInternalPort($projectType)
    {
        switch ($projectType) {
            case 'laravel':
            case 'php':
                return 80;
            case 'java-maven':
            case 'java-gradle':
            case 'java':
                return 8080;
            case 'node':
                return 3000;
            case 'python':
                return 5000;
            default:
                return 8080;
        }
    }

    /**
     * Detener y eliminar un contenedor
     * 
     * @param string $containerId
     * @return bool
     */
    public function stopContainer($containerId)
    {
        try {
            exec("docker stop {$containerId} && docker rm {$containerId} 2>&1", $output, $returnVar);
            
            if ($returnVar !== 0) {
                Log::error('Error stopping container: ' . implode("\n", $output));
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error stopping container: ' . $e->getMessage());
            return false;
        }
    }    /**
     * Obtener el estado de un contenedor
     * 
     * @param string $containerId
     * @return string|null
     */
    public function getContainerStatus($containerId)
    {
        try {
            // Verificar si Docker está disponible primero
            if (!$this->checkDockerAvailability()) {
                Log::error('No se puede obtener el estado del contenedor porque Docker no está disponible');
                return 'docker_unavailable';
            }
            
            // Usar formato compatible con PowerShell
            $cmd = 'docker inspect --format="{{.State.Status}}" ' . $containerId . ' 2>&1';
            exec($cmd, $output, $returnVar);
            
            if ($returnVar !== 0) {
                // Comprobar si el error es porque el contenedor no existe
                $checkCmd = 'docker ps -a --filter "id=' . $containerId . '" --format "{{.ID}}" 2>&1';
                exec($checkCmd, $checkOutput, $checkReturnVar);
                
                if ($checkReturnVar === 0 && empty($checkOutput)) {
                    return 'not_found';
                }
                
                return null;
            }
            
            return $output[0] ?? null;
        } catch (\Exception $e) {
            Log::error('Error getting container status: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Limpiar contenedores inactivos
     * 
     * @param int $olderThanDays Días de inactividad antes de limpiar
     * @return array Resultados de la limpieza
     */
    public function cleanupInactiveContainers($olderThanDays = 7)
    {
        try {
            $results = [
                'stopped' => 0,
                'removed' => 0,
                'errors' => []
            ];
            
            // Buscar contenedores de tesis inactivos
            $cutoffDate = now()->subDays($olderThanDays);
            $inactiveTesis = \App\Models\Tesis::where('container_status', '!=', 'running')
                ->whereNotNull('container_id')
                ->where(function($query) use ($cutoffDate) {
                    $query->where('last_deployed', '<', $cutoffDate)
                        ->orWhereNull('last_deployed');
                })
                ->get();
            
            foreach ($inactiveTesis as $tesis) {
                try {
                    // Verificar si el contenedor existe
                    $containerStatus = $this->getContainerStatus($tesis->container_id);
                    
                    if ($containerStatus && $containerStatus != 'stopped') {
                        // Detener el contenedor si aún está en ejecución
                        $this->stopContainer($tesis->container_id);
                        $results['stopped']++;
                    }
                    
                    // Eliminar el contenedor
                    exec("docker rm {$tesis->container_id} 2>&1", $output, $returnVar);
                    
                    if ($returnVar === 0) {
                        $results['removed']++;
                        
                        // Actualizar el registro de la tesis
                        $tesis->container_status = 'removed';
                        $tesis->save();
                    }
                } catch (\Exception $e) {
                    $results['errors'][] = "Error en tesis ID {$tesis->id}: " . $e->getMessage();
                    Log::error("Error cleaning up container for tesis {$tesis->id}: " . $e->getMessage());
                }
            }
            
            return $results;
        } catch (\Exception $e) {
            Log::error('Error in cleanupInactiveContainers: ' . $e->getMessage());
            return [
                'stopped' => 0,
                'removed' => 0,
                'errors' => [$e->getMessage()]
            ];
        }
    }
    
    /**
     * Obtener estadísticas de uso de recursos
     * 
     * @param string $containerId ID del contenedor
     * @return array|null
     */
    public function getContainerStats($containerId)
    {
        try {
            // Obtener estadísticas del contenedor usando docker stats
            $cmd = "docker stats {$containerId} --no-stream --format \"{{.CPUPerc}}|{{.MemPerc}}|{{.NetIO}}|{{.BlockIO}}\" 2>&1";
            exec($cmd, $output, $returnVar);
            
            if ($returnVar !== 0 || empty($output)) {
                return null;
            }
            
            $stats = explode('|', $output[0]);
            
            if (count($stats) < 4) {
                return null;
            }
            
            // Convertir porcentajes a valores numéricos
            $cpuPerc = floatval(str_replace('%', '', $stats[0]));
            $memPerc = floatval(str_replace('%', '', $stats[1]));
            
            return [
                'cpu_percent' => $cpuPerc,
                'memory_percent' => $memPerc,
                'network_io' => $stats[2],
                'block_io' => $stats[3]
            ];
        } catch (\Exception $e) {
            Log::error('Error getting container stats: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Establecer límites de recursos para un contenedor
     * 
     * @param string $containerId ID del contenedor
     * @param array $limits Límites de recursos (cpu, memory)
     * @return bool
     */
    public function setContainerLimits($containerId, array $limits)
    {
        try {
            $cpuLimit = $limits['cpu'] ?? '1';  // 1 CPU por defecto
            $memoryLimit = $limits['memory'] ?? '512m';  // 512MB por defecto
            
            $cmd = "docker update --cpus={$cpuLimit} --memory={$memoryLimit} {$containerId} 2>&1";
            exec($cmd, $output, $returnVar);
            
            if ($returnVar !== 0) {
                Log::error('Error setting container limits: ' . implode("\n", $output));
                return false;
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Error setting container limits: ' . $e->getMessage());
            return false;
        }
    }
}
