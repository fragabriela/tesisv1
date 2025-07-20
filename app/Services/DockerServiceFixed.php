<?php

namespace App\Services;

use App\Models\Tesis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DockerServiceFixed
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
            return '8.1'; // Versión por defecto
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
        
        return '8.1'; // Versión por defecto
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
        
        // Detectar la versión de PHP requerida
        $phpVersion = $this->detectRequiredPhpVersion($repoPath);
        Log::info("Detected PHP version requirement: {$phpVersion} for project at {$repoPath}");
        
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
RUN echo '<Directory /var/www/html>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
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
}
