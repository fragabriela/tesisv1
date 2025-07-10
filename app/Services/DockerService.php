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
            // Ya existe un Dockerfile, usaremos ese
            return true;
        }
        
        $dockerfile = '';
          switch ($projectType) {
            case 'laravel':
            case 'php': // Tratar proyectos PHP genéricos como Laravel por ahora
                $dockerfile = <<<EOT
FROM php:8.1-apache

# Instalar dependencias
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git

# Configurar extensiones PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install pdo_mysql gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar Apache
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN a2enmod rewrite

# Copiar proyecto
WORKDIR /var/www/html
COPY . .

# Instalar dependencias
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Configurar permisos si hay directorios de Laravel
RUN if [ -d "storage" ]; then \
        chown -R www-data:www-data /var/www/html/storage; \
        chmod -R 775 /var/www/html/storage; \
    fi
RUN if [ -d "bootstrap/cache" ]; then \
        chown -R www-data:www-data /var/www/html/bootstrap/cache; \
        chmod -R 775 /var/www/html/bootstrap/cache; \
    fi

# Crear archivo .env si no existe pero hay un .env.example
RUN if [ ! -f .env ] && [ -f .env.example ]; then \
        cp .env.example .env; \
        if [ -f "artisan" ]; then \
            php artisan key:generate; \
        fi; \
    fi

EXPOSE 80
EOT;
                break;

            case 'java-maven':
                $dockerfile = <<<EOT
FROM maven:3.8.5-openjdk-17 as builder

WORKDIR /app
COPY . .
RUN mvn clean package -DskipTests

FROM openjdk:17-jdk-slim
WORKDIR /app
COPY --from=builder /app/target/*.jar app.jar
EXPOSE 8080
ENTRYPOINT ["java", "-jar", "app.jar"]
EOT;
                break;

            case 'java-gradle':
                $dockerfile = <<<EOT
FROM gradle:7.4.2-jdk17 as builder

WORKDIR /app
COPY . .
RUN gradle build --no-daemon -x test

FROM openjdk:17-jdk-slim
WORKDIR /app
COPY --from=builder /app/build/libs/*.jar app.jar
EXPOSE 8080
ENTRYPOINT ["java", "-jar", "app.jar"]
EOT;
                break;

            case 'java':
                $dockerfile = <<<EOT
FROM openjdk:17-jdk-slim

WORKDIR /app
COPY . .

RUN find . -name "*.java" > sources.txt
RUN mkdir -p build
RUN javac -d build @sources.txt
RUN find build -name "*.class" | grep -v "Test" | head -1 | xargs dirname > main-class-dir.txt
RUN cat main-class-dir.txt | sed 's/build\///' | sed 's/\//./g' > package-name.txt
RUN find build -name "*.class" | grep -v "Test" | grep "main" | head -1 | xargs basename | sed 's/.class//' > main-class-name.txt
RUN echo "$(cat package-name.txt).$(cat main-class-name.txt)" > full-main-class.txt

ENTRYPOINT ["sh", "-c", "java -cp build $(cat full-main-class.txt)"]
EXPOSE 8080
EOT;
                break;

            case 'node':
                $dockerfile = <<<EOT
FROM node:16-alpine

WORKDIR /app
COPY . .

RUN npm install
RUN npm run build --if-present

EXPOSE 3000
CMD ["npm", "start"]
EOT;
                break;

            case 'python':
                $dockerfile = <<<EOT
FROM python:3.9-slim

WORKDIR /app
COPY . .

RUN pip install --no-cache-dir -r requirements.txt

EXPOSE 5000
CMD ["python", "app.py"]
EOT;
                break;
                
            default:
                return false;
        }
        
        file_put_contents($dockerfilePath, $dockerfile);
        return true;
    }

    /**
     * Crear un archivo Docker Compose para el proyecto
     * 
     * @param string $repoPath
     * @param string $projectType
     * @return bool
     */
    public function createDockerCompose($repoPath, $projectType)
    {
        $fullPath = storage_path('app/public/' . $repoPath);
        $composePath = $fullPath . '/docker-compose.yml';
        
        if (file_exists($composePath)) {
            // Ya existe un archivo docker-compose.yml, usaremos ese
            return true;
        }
        
        $projectName = basename($repoPath);
        $containerName = Str::slug($projectName) . '-' . Str::random(5);
        
        $compose = '';
          switch ($projectType) {
            case 'laravel':
            case 'php': // Tratar proyectos PHP genéricos como Laravel por ahora
                $compose = <<<EOT
version: '3'
services:
  app:
    build: .
    container_name: {$containerName}
    restart: unless-stopped
    ports:
      - "0:80"
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
      - DB_CONNECTION=sqlite
      - DB_DATABASE=:memory:
EOT;
                break;

            case 'java-maven':
            case 'java-gradle':
            case 'java':
                $compose = <<<EOT
version: '3'
services:
  app:
    build: .
    container_name: {$containerName}
    restart: unless-stopped
    ports:
      - "0:8080"
EOT;
                break;

            case 'node':
                $compose = <<<EOT
version: '3'
services:
  app:
    build: .
    container_name: {$containerName}
    restart: unless-stopped
    ports:
      - "0:3000"
EOT;
                break;

            case 'python':
                $compose = <<<EOT
version: '3'
services:
  app:
    build: .
    container_name: {$containerName}
    restart: unless-stopped
    ports:
      - "0:5000"
EOT;
                break;
                
            default:
                return false;
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
            $this->createDockerfile($tesis->project_repo_path, $projectType);
            $this->createDockerCompose($tesis->project_repo_path, $projectType);
            
            // Generar un nombre único para el contenedor
            $containerName = 'tesis-' . $tesis->id . '-' . Str::random(5);
            
            // Intentar usar "docker compose" (nuevo formato con espacio)
            $cmd = "cd {$repoPath} && docker compose up -d 2>&1";
            exec($cmd, $output, $returnVar);
            
            // Si falla, intentar con "docker-compose" (formato antiguo con guión)
            if ($returnVar !== 0) {
                Log::warning('Comando "docker compose" falló, intentando con "docker-compose": ' . implode("\n", $output));
                $cmd = "cd {$repoPath} && docker-compose up -d 2>&1";
                exec($cmd, $output, $returnVar);
                
                if ($returnVar !== 0) {
                    Log::error('Error building and running container: ' . implode("\n", $output));
                    throw new \Exception('No se pudo ejecutar el contenedor Docker. Verifique que Docker y Docker Compose estén instalados correctamente.');
                }
            }
            
            // Obtener información del contenedor
            $cmd = "docker ps --filter name=" . basename($repoPath) . " --format '{{.ID}}|{{.Ports}}'";
            exec($cmd, $containerInfo, $returnVar);
            
            if ($returnVar !== 0 || empty($containerInfo)) {
                Log::error('Error getting container info');
                return null;
            }
            
            list($containerId, $ports) = explode('|', $containerInfo[0]);
            
            // Extraer el puerto mapeado
            preg_match('/0.0.0.0:(\d+)/', $ports, $matches);
            $port = $matches[1] ?? null;
            
            if (!$port) {
                Log::error('Could not determine container port');
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
                    'container_name' => $containerName,
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
            
            exec("docker inspect --format='{{.State.Status}}' {$containerId} 2>&1", $output, $returnVar);
            
            if ($returnVar !== 0) {
                // Comprobar si el error es porque el contenedor no existe
                exec("docker ps -a --filter \"id={$containerId}\" --format '{{.ID}}' 2>&1", $checkOutput, $checkReturnVar);
                
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
