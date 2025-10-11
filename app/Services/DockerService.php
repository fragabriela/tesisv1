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
        Log::info("DEBUG createDockerfile: repoPath = $repoPath, projectType = $projectType");
        $fullPath = storage_path('app/public/' . $repoPath);
        $dockerfilePath = $fullPath . '/Dockerfile';
        Log::info("DEBUG createDockerfile: fullPath = $fullPath");
        Log::info("DEBUG createDockerfile: dockerfilePath = $dockerfilePath");
        Log::info("DEBUG createDockerfile: file_exists = " . (file_exists($dockerfilePath) ? 'true' : 'false'));
        
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
        Log::info("DEBUG createDockerfile: projectType = '$projectType'");
        
        switch (strtolower($projectType)) {
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
                
            default:
                Log::info("DEBUG createDockerfile: Tipo de proyecto '$projectType' no reconocido, tratando como Laravel");
                // Para tipos de proyecto no reconocidos, usar la plantilla de Laravel
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
    mariadb-client \
    && rm -rf /var/lib/apt/lists/*

# Configurar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo_mysql mysqli gd zip mbstring xml

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar Apache para usar el directorio público
RUN sed -ri -e 's!/var/www/html!{$documentRoot}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!{$documentRoot}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN a2enmod rewrite

# Configurar ServerName para evitar warnings
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

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
        echo "DB_CONNECTION=mysql" >> .env; \
        echo "DB_HOST=mysql" >> .env; \
        echo "DB_PORT=3306" >> .env; \
        echo "DB_DATABASE=tesisv1" >> .env; \
        echo "DB_USERNAME=root" >> .env; \
        echo "DB_PASSWORD=secret" >> .env; \
    fi

EXPOSE 80

# Comando de inicio
CMD ["apache2-foreground"]
EOT;
                break;
        }

        // Guardar el Dockerfile
        if (!empty($dockerfile)) {
            try {
                $result = file_put_contents($dockerfilePath, $dockerfile);
                if ($result === false) {
                    Log::error("No se pudo escribir el Dockerfile en: $dockerfilePath");
                    return false;
                }
                Log::info("Dockerfile creado exitosamente en: $dockerfilePath");
                return true;
            } catch (\Exception $e) {
                Log::error("Error al crear Dockerfile: " . $e->getMessage());
                return false;
            }
        }

        return false;
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
            // Comprobar si Docker está instalado
            exec('docker --version 2>&1', $output, $returnVar);
            
            if ($returnVar !== 0) {
                Log::error('Docker no está instalado: ' . implode("\n", $output));
                return false;
            }
            
            // Verificar si Docker Desktop está ejecutándose
            exec('docker info 2>&1', $infoOutput, $infoReturnVar);
            
            if ($infoReturnVar !== 0) {
                $errorMessage = implode("\n", $infoOutput);
                
                // Detectar el error específico de Docker Desktop no ejecutándose
                if (strpos($errorMessage, 'dockerDesktopLinuxEngine') !== false || 
                    strpos($errorMessage, 'cannot connect') !== false ||
                    strpos($errorMessage, 'system cannot find the file') !== false) {
                    Log::error('Docker Desktop no está ejecutándose. Por favor, inicie Docker Desktop.');
                    return false;
                }
                
                Log::error('Error al conectar con Docker: ' . $errorMessage);
                return false;
            }
            
            Log::info('Docker está instalado y ejecutándose correctamente');
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
        Log::info("DOCKER SERVICE DEBUG: buildAndRunProject iniciado", [
            'tesis_id' => $tesis->id,
            'project_repo_path' => $tesis->project_repo_path,
            'project_type' => $tesis->project_type
        ]);
        
        try {
            // Verificar si Docker está disponible
            if (!$this->checkDockerAvailability()) {
                throw new \Exception('Docker no está instalado o Docker Desktop no está ejecutándose. Por favor, inicie Docker Desktop y vuelva a intentar.');
            }
            
            $repoPath = storage_path('app/public/' . $tesis->project_repo_path);
            $projectType = $tesis->project_type;
            
            Log::info("DOCKER SERVICE DEBUG: Rutas calculadas", [
                'repoPath' => $repoPath,
                'projectType' => $projectType
            ]);
            
            // Asegurarnos que exista el Dockerfile y docker-compose.yml
            if (!$this->createDockerfile($tesis->project_repo_path, $projectType)) {
                throw new \Exception("No se pudo crear el Dockerfile para el tipo de proyecto: $projectType");
            }
            
            if (!$this->createDockerCompose($tesis->project_repo_path, $projectType)) {
                throw new \Exception("No se pudo crear el archivo docker-compose.yml para el tipo de proyecto: $projectType");
            }
            
            // Verificar que los archivos se crearon correctamente
            Log::info("DEBUG: Verificando existencia de archivos");
            Log::info("DEBUG: repoPath = " . $repoPath);
            Log::info("DEBUG: Dockerfile path = " . $repoPath . '/Dockerfile');
            Log::info("DEBUG: file_exists result = " . (file_exists($repoPath . '/Dockerfile') ? 'true' : 'false'));
            Log::info("DEBUG: is_file result = " . (is_file($repoPath . '/Dockerfile') ? 'true' : 'false'));
            Log::info("DEBUG: is_readable result = " . (is_readable($repoPath . '/Dockerfile') ? 'true' : 'false'));
            
            $dockerfilePath = $repoPath . '/Dockerfile';
            $dockerfileExists = file_exists($dockerfilePath) && is_file($dockerfilePath) && is_readable($dockerfilePath);
            
            if (!$dockerfileExists) {
                // Intentar rutas alternativas
                $alternativePaths = [
                    storage_path('app/public/repos/HigfxI9k01/Dockerfile'),
                    storage_path('app/repos/HigfxI9k01/Dockerfile'),
                    storage_path('repos/HigfxI9k01/Dockerfile')
                ];
                
                foreach ($alternativePaths as $altPath) {
                    Log::info("DEBUG: Verificando ruta alternativa: $altPath");
                    if (file_exists($altPath) && is_file($altPath)) {
                        Log::info("DEBUG: Encontrado en ruta alternativa, copiando...");
                        copy($altPath, $dockerfilePath);
                        $dockerfileExists = true;
                        break;
                    }
                }
            }
            
            if (!$dockerfileExists) {
                throw new \Exception("El Dockerfile no existe en la ruta esperada: $repoPath/Dockerfile");
            }
            
            $composePath = $repoPath . '/docker-compose.yml';
            $composeExists = file_exists($composePath) && is_file($composePath) && is_readable($composePath);
            
            if (!$composeExists) {
                // Intentar rutas alternativas para docker-compose.yml
                $alternativeComposePaths = [
                    storage_path('app/public/repos/HigfxI9k01/docker-compose.yml'),
                    storage_path('app/repos/HigfxI9k01/docker-compose.yml'),
                    storage_path('repos/HigfxI9k01/docker-compose.yml')
                ];
                
                foreach ($alternativeComposePaths as $altPath) {
                    Log::info("DEBUG: Verificando docker-compose en ruta alternativa: $altPath");
                    if (file_exists($altPath) && is_file($altPath)) {
                        Log::info("DEBUG: docker-compose encontrado en ruta alternativa, copiando...");
                        copy($altPath, $composePath);
                        $composeExists = true;
                        break;
                    }
                }
            }
            
            if (!$composeExists) {
                throw new \Exception("El archivo docker-compose.yml no existe en la ruta esperada: $repoPath/docker-compose.yml");
            }
            
            // Generar un nombre único para el contenedor basado en el directorio
            $baseName = basename($repoPath);
            $containerName = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $baseName));
            
            // Asegurarse de que el docker-compose.yml use el mismo nombre de contenedor
            if (!$this->createDockerCompose($tesis->project_repo_path, $projectType, $containerName)) {
                throw new \Exception("No se pudo crear el archivo docker-compose.yml para el tipo de proyecto: $projectType");
            }
            
            // Registrar comandos que vamos a ejecutar
            Log::info('Ejecutando docker compose en el directorio: ' . $repoPath);
            Log::info('Nombre del contenedor: ' . $containerName);
            
            // Detener contenedores existentes si los hay
            $stopCmd = "cd {$repoPath} && docker compose down 2>&1";
            exec($stopCmd, $stopOutput, $stopReturn);
            
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
            
            // Añadir un retraso más largo para Docker Compose con múltiples contenedores
            Log::info('Esperando 10 segundos para que los contenedores inicien...');
            sleep(10);
            
            // Buscar contenedor de aplicación (no MySQL)
            $containerInfo = $this->findApplicationContainer($containerName, $repoPath);
            
            if (!$containerInfo) {
                Log::error('No se pudo encontrar el contenedor de la aplicación');
                return null;
            }
            
            // Procesar información del contenedor
            $containerParts = explode('|', $containerInfo[0]);
            if (count($containerParts) < 2) {
                Log::error('Formato de información del contenedor no válido: ' . $containerInfo[0]);
                return null;
            }
            
            $containerId = $containerParts[0];
            $portsStr = $containerParts[1];
            
            Log::info("Contenedor encontrado - ID: $containerId, Puertos: $portsStr");
            
            // Extraer el puerto público
            preg_match('/0\.0\.0\.0:(\d+)->80/', $portsStr, $matches);
            $publicPort = isset($matches[1]) ? $matches[1] : null;
            
            if (!$publicPort) {
                Log::warning('No se pudo extraer el puerto público de: ' . $portsStr);
            }
            
            Log::info("Puerto público detectado: $publicPort");
            
            // Generar APP_KEY si es Laravel y no existe
            if ($projectType === 'laravel') {
                $this->ensureLaravelAppKey($containerId);
            }
            
            // Intentar restaurar backup si existe
            if ($tesis->backup && file_exists(storage_path('app/public/backups/' . $tesis->backup->backup_file))) {
                Log::info('Iniciando restauración automática de backup');
                $backupService = new ProjectBackupService();
                $restoreResult = $backupService->restoreBackupWithAutoDetection($tesis, $containerId);
                
                if ($restoreResult['success']) {
                    Log::info('Backup restaurado exitosamente: ' . $restoreResult['message']);
                } else {
                    Log::warning('Error restaurando backup: ' . $restoreResult['message']);
                }
            }
            
            return [
                'container_id' => $containerId,
                'container_status' => 'running',
                'project_url' => $publicPort ? "http://localhost:$publicPort" : null,
                'project_config' => [
                    'external_port' => $publicPort,
                    'container_name' => $containerName,
                    'project_type' => $projectType
                ],
                'port' => $publicPort,
                'container_name' => $containerName,
                'status' => 'success',
                'message' => 'Proyecto desplegado exitosamente con Docker Compose'
            ];
            
        } catch (\Exception $e) {
            Log::error('Error en buildAndRunProject: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Buscar el contenedor de aplicación (excluyendo MySQL y otros servicios)
     */
    private function findApplicationContainer(string $containerName, string $repoPath): ?array
    {
        $baseName = basename($repoPath);
        
        // Buscar contenedores por nombre del proyecto
        exec('docker ps --format "{{.ID}}|{{.Ports}}|{{.Names}}"', $allContainerDetails);
        
        foreach ($allContainerDetails as $container) {
            $parts = explode('|', $container);
            if (count($parts) >= 3) {
                $id = $parts[0];
                $ports = $parts[1];
                $name = $parts[2];
                
                // Excluir contenedores de base de datos
                if (stripos($name, 'mysql') !== false || 
                    stripos($name, 'postgres') !== false || 
                    stripos($name, 'redis') !== false) {
                    continue;
                }
                
                // Buscar contenedor de aplicación
                if (stripos($name, $containerName) !== false || 
                    stripos($name, $baseName) !== false) {
                    
                    Log::info("Contenedor de aplicación encontrado: $name");
                    return ["$id|$ports"];
                }
            }
        }
        
        return null;
    }

    /**
     * Generar APP_KEY para Laravel si no existe
     */
    private function ensureLaravelAppKey(string $containerId): void
    {
        try {
            Log::info('Verificando APP_KEY para Laravel...');
            
            // Verificar si existe APP_KEY válida
            exec("docker exec $containerId cat /var/www/html/.env | grep APP_KEY", $envOutput, $envReturn);
            
            $needsNewKey = true;
            if ($envReturn === 0 && !empty($envOutput)) {
                foreach ($envOutput as $line) {
                    if (strpos($line, 'APP_KEY=base64:') !== false && strlen(trim($line)) > 20) {
                        $needsNewKey = false;
                        Log::info('APP_KEY válida encontrada');
                        break;
                    }
                }
            }
            
            if ($needsNewKey) {
                Log::info('Generando nueva APP_KEY...');
                exec("docker exec $containerId php /var/www/html/artisan key:generate --force", $keyOutput, $keyReturn);
                
                if ($keyReturn === 0) {
                    Log::info('APP_KEY generada exitosamente');
                } else {
                    Log::warning('Error generando APP_KEY: ' . implode("\n", $keyOutput));
                }
            }
            
            // Limpiar cache
            exec("docker exec $containerId php /var/www/html/artisan config:clear", $configOutput);
            
        } catch (\Exception $e) {
            Log::error('Error en ensureLaravelAppKey: ' . $e->getMessage());
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
                return 80;
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

    /**
     * 🗄️ Restaurar backup en un contenedor Docker
     */
    public function restoreBackupInContainer(string $containerId, string $backupPath): bool
    {
        try {
            Log::info('Iniciando restauración de backup en contenedor', [
                'container_id' => $containerId,
                'backup_path' => $backupPath
            ]);

            // Verificar que el contenedor existe
            if (!$this->containerExists($containerId)) {
                throw new \Exception("El contenedor {$containerId} no existe");
            }

            // Crear directorio temporal en el contenedor
            $tempDir = '/tmp/restore-' . Str::random(8);
            $this->execInContainer($containerId, "mkdir -p {$tempDir}");

            // Copiar backup al contenedor
            $cmd = "docker cp \"{$backupPath}\" {$containerId}:{$tempDir}/backup.zip";
            exec($cmd, $output, $returnVar);
            
            if ($returnVar !== 0) {
                throw new \Exception('Error copiando backup al contenedor: ' . implode("\n", $output));
            }

            // Extraer backup
            $this->execInContainer($containerId, "cd {$tempDir} && unzip -o backup.zip");

            // Verificar si hay backup de base de datos
            $dbBackupExists = $this->execInContainer($containerId, "test -f {$tempDir}/database-backup.sql && echo 'exists' || echo 'not_found'");
            
            if (trim($dbBackupExists) === 'exists') {
                // Restaurar base de datos
                $this->restoreDatabaseInContainer($containerId, "{$tempDir}/database-backup.sql");
            }

            // Verificar si hay configuración de restauración
            $configExists = $this->execInContainer($containerId, "test -f {$tempDir}/restore-config.json && echo 'exists' || echo 'not_found'");
            
            if (trim($configExists) === 'exists') {
                // Aplicar configuración específica
                $this->applyRestoreConfig($containerId, "{$tempDir}/restore-config.json");
            }

            // Limpiar archivos temporales
            $this->execInContainer($containerId, "rm -rf {$tempDir}");

            // Reiniciar servicios si es necesario
            $this->restartContainerServices($containerId);

            Log::info('Backup restaurado exitosamente en contenedor', [
                'container_id' => $containerId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error restaurando backup en contenedor', [
                'container_id' => $containerId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Restaurar base de datos en contenedor
     */
    private function restoreDatabaseInContainer(string $containerId, string $sqlFile): bool
    {
        try {
            // Detectar tipo de base de datos
            $dbType = $this->detectDatabaseType($containerId);
            
            switch ($dbType) {
                case 'sqlite':
                    // Para SQLite, ejecutar comandos SQL directamente
                    $dbPath = '/var/www/html/database/database.sqlite';
                    $this->execInContainer($containerId, "sqlite3 {$dbPath} < {$sqlFile}");
                    break;
                    
                case 'mysql':
                    // Para MySQL
                    $this->execInContainer($containerId, "mysql -u root -p < {$sqlFile}");
                    break;
                    
                case 'postgres':
                    // Para PostgreSQL
                    $this->execInContainer($containerId, "psql -U postgres < {$sqlFile}");
                    break;
                    
                default:
                    Log::warning('Tipo de base de datos no soportado para restauración automática', [
                        'db_type' => $dbType,
                        'container_id' => $containerId
                    ]);
                    return false;
            }

            Log::info('Base de datos restaurada exitosamente', [
                'container_id' => $containerId,
                'db_type' => $dbType
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error restaurando base de datos', [
                'container_id' => $containerId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Detectar tipo de base de datos en contenedor
     */
    private function detectDatabaseType(string $containerId): string
    {
        try {
            // Buscar archivo SQLite
            $sqliteExists = $this->execInContainer($containerId, "test -f /var/www/html/database/database.sqlite && echo 'found'");
            if (trim($sqliteExists) === 'found') {
                return 'sqlite';
            }

            // Buscar MySQL
            $mysqlExists = $this->execInContainer($containerId, "which mysql && echo 'found'");
            if (strpos($mysqlExists, 'found') !== false) {
                return 'mysql';
            }

            // Buscar PostgreSQL
            $pgExists = $this->execInContainer($containerId, "which psql && echo 'found'");
            if (strpos($pgExists, 'found') !== false) {
                return 'postgres';
            }

            return 'unknown';

        } catch (\Exception $e) {
            Log::error('Error detectando tipo de base de datos', [
                'container_id' => $containerId,
                'error' => $e->getMessage()
            ]);
            return 'unknown';
        }
    }

    /**
     * Aplicar configuración de restauración
     */
    private function applyRestoreConfig(string $containerId, string $configFile): bool
    {
        try {
            $configData = json_decode($this->execInContainer($containerId, "cat {$configFile}"), true);
            
            if (!$configData) {
                Log::warning('No se pudo leer configuración de restauración');
                return false;
            }

            // Aplicar configuraciones de entorno si existen
            if (isset($configData['environment'])) {
                foreach ($configData['environment'] as $key => $value) {
                    $this->execInContainer($containerId, "export {$key}={$value}");
                }
            }

            // Ejecutar comandos post-restauración si existen
            if (isset($configData['restore_instructions']['commands'])) {
                foreach ($configData['restore_instructions']['commands'] as $command) {
                    // Reemplazar placeholder CONTAINER_ID
                    $command = str_replace('CONTAINER_ID', $containerId, $command);
                    $this->execInContainer($containerId, $command);
                }
            }

            Log::info('Configuración de restauración aplicada', [
                'container_id' => $containerId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error aplicando configuración de restauración', [
                'container_id' => $containerId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Reiniciar servicios del contenedor después de restauración
     */
    private function restartContainerServices(string $containerId): bool
    {
        try {
            // Limpiar cache de Laravel si existe
            $this->execInContainer($containerId, 'php artisan config:clear || true');
            $this->execInContainer($containerId, 'php artisan cache:clear || true');
            $this->execInContainer($containerId, 'php artisan route:clear || true');
            
            // Reiniciar servicios web si es necesario
            $this->execInContainer($containerId, 'service apache2 reload || service nginx reload || true');

            Log::info('Servicios del contenedor reiniciados', [
                'container_id' => $containerId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Error reiniciando servicios del contenedor', [
                'container_id' => $containerId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Verificar si un contenedor existe
     */
    public function containerExists(string $containerId): bool
    {
        try {
            $cmd = "docker ps -a --format \"{{.ID}}\" | grep -q {$containerId}";
            exec($cmd, $output, $returnVar);
            return $returnVar === 0;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Ejecutar comando en contenedor y retornar salida
     */
    private function execInContainer(string $containerId, string $command): string
    {
        $cmd = "docker exec {$containerId} bash -c '{$command}' 2>&1";
        exec($cmd, $output, $returnVar);
        
        if ($returnVar !== 0) {
            Log::warning('Comando en contenedor falló', [
                'container_id' => $containerId,
                'command' => $command,
                'output' => implode("\n", $output)
            ]);
        }
        
        return implode("\n", $output);
    }
}
