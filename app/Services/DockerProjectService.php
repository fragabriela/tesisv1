<?php

namespace App\Services;

use App\Models\Tesis;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\ExecutableFinder;

class DockerProjectService
{
    public function available(): bool
    {
        try {
            $process = new Process([$this->dockerBinary(), 'info', '--format', '{{.ServerVersion}}']);
            $process->setTimeout(20);
            $process->run();
            return $process->isSuccessful() && trim($process->getOutput()) !== '';
        } catch (\Throwable) {
            return false;
        }
    }

    public function deployProject(Tesis $tesis, bool $withBackup = false, array $options = []): array
    {
        if (!$this->available()) {
            throw new RuntimeException('Docker Desktop está instalado, pero el motor Docker no está iniciado. Abra Docker Desktop y espere hasta que indique que está ejecutándose.');
        }
        if ($tesis->project_type !== 'laravel') {
            throw new RuntimeException('El despliegue Docker automático está disponible actualmente para proyectos Laravel.');
        }

        $source = storage_path('app/public/'.$tesis->project_repo_path);
        if (!is_dir($source)) {
            throw new RuntimeException('No se encontró el repositorio del proyecto.');
        }
        $name = $this->projectName($tesis);
        $path = storage_path('app/docker-projects/'.$name);
        File::ensureDirectoryExists(dirname($path));
        File::copyDirectory($source, $path);

        $capabilities = app(ProjectDatabaseService::class)->inspectProject($path);
        $password = $tesis->project_config['docker_db_password'] ?? bin2hex(random_bytes(18));
        $database = str_replace('-', '_', $name);
        $appKey = 'base64:'.base64_encode(random_bytes(32));
        $this->prepareEnvironment($path, $options['env_content'] ?? null);
        $this->removeWindowsOnlyFrontendPackages($path);
        $this->writeDockerFiles($path, $name, $database, $password, $appKey, $capabilities['seeder_found']);

        $this->compose($path, ['up', '-d', '--build', '--remove-orphans'], 1800);
        $containerId = trim($this->compose($path, ['ps', '-q', 'app']));
        if ($containerId === '') {
            throw new RuntimeException('Docker no creó el contenedor de la aplicación.');
        }
        $this->waitForApplicationContainer($path);
        $this->ensureWritableDirectories($path);
        if (is_file($path.'/package.json')) {
            $this->compose($path, ['exec', '-T', 'app', 'test', '-f', 'public/build/manifest.json'], 30);
        }

        if (!$withBackup && ($options['run_migrations'] ?? true) && $capabilities['migrations_found']) {
            $this->artisan($path, ['migrate', '--force', '--no-interaction']);
        }

        $portOutput = $this->docker(['port', $containerId, '80/tcp']);
        if (!preg_match('/(?:127\.0\.0\.1|0\.0\.0\.0|\[::\]):(\d+)/', $portOutput, $match)) {
            throw new RuntimeException('No se pudo determinar el puerto publicado por Docker.');
        }
        $port = (int) $match[1];
        $capabilities['database_was_empty'] = true;
        $capabilities['frontend_built'] = is_file($path.'/package.json');

        return [
            'container_id' => $containerId,
            'container_status' => 'running',
            'project_url' => 'http://localhost:'.$port,
            'project_config' => [
                'deployment_type' => 'docker',
                'project_name' => $name,
                'project_path' => $path,
                'compose_file' => $path.'/.tesis-compose.yml',
                'database_name' => $database,
                'docker_db_password' => $password,
                'external_port' => $port,
                'capabilities' => $capabilities,
                'custom_env' => isset($options['env_content']),
            ],
            'status' => 'success',
        ];
    }

    public function seed(array $config, bool $fresh = false): void
    {
        $path = $this->validatedProjectPath($config);
        if ($fresh) {
            $this->artisan($path, ['migrate:fresh', '--force', '--no-interaction']);
        }
        $this->artisan($path, ['db:seed', '--force', '--no-interaction']);
    }

    public function restoreSql(array $config, string $file): void
    {
        $path = $this->validatedProjectPath($config);
        $database = $config['database_name'] ?? '';
        $password = $config['docker_db_password'] ?? '';
        if (!is_file($file) || !is_string($password) || $password === '') {
            throw new RuntimeException('No se pudo preparar el backup para Docker.');
        }
        $input = app(ProjectDatabaseService::class)->preparedBackupStream($file, $database);
        try {
            $process = new Process([
                $this->composeBinary(), '-f', '.tesis-compose.yml', 'exec', '-T',
                'db', 'mysql', '--user=project', '--password='.$password, '--database='.$database,
            ], $path, null, $input, 600);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new RuntimeException('No se pudo importar el backup en Docker: '.mb_substr(trim($process->getErrorOutput()), 0, 3000));
            }
        } finally {
            fclose($input);
        }
    }

    public function stop(array $config): void
    {
        $this->compose($this->validatedProjectPath($config), ['stop'], 120);
    }

    public function status(string $containerId): ?string
    {
        if (!preg_match('/\A[a-f0-9]{12,64}\z/i', $containerId)) {
            return null;
        }
        try {
            $status = strtolower(trim($this->docker(['inspect', '--format', '{{.State.Status}}', $containerId])));
            return match ($status) {
                'running', 'restarting' => 'running',
                'created', 'exited', 'dead', 'removing', 'paused' => 'stopped',
                default => null,
            };
        } catch (RuntimeException) {
            return null;
        }
    }

    private function projectName(Tesis $tesis): string
    {
        $existing = $tesis->project_config['deployment_type'] ?? null;
        if ($existing === 'docker' && !empty($tesis->project_config['project_name'])) {
            return $tesis->project_config['project_name'];
        }
        return substr(Str::slug($tesis->titulo) ?: 'proyecto', 0, 30).'-proyecto-'.$tesis->id;
    }

    private function prepareEnvironment(string $path, ?string $content): void
    {
        if ($content !== null) {
            app(ProjectDatabaseService::class)->forEnvironment($content);
            file_put_contents($path.'/.env', $content);
        } elseif (!is_file($path.'/.env')) {
            if (is_file($path.'/.env.example')) {
                File::copy($path.'/.env.example', $path.'/.env');
            } else {
                file_put_contents($path.'/.env', "APP_NAME=Laravel\nAPP_ENV=production\nAPP_DEBUG=false\n");
            }
        }
    }

    private function removeWindowsOnlyFrontendPackages(string $path): void
    {
        $packagePath = $path.'/package.json';
        if (!is_file($packagePath)) {
            return;
        }
        $package = json_decode((string) file_get_contents($packagePath), true);
        if (!is_array($package)) {
            throw new RuntimeException('El package.json del proyecto no contiene JSON válido.');
        }
        $changed = false;
        foreach (['dependencies', 'devDependencies', 'optionalDependencies'] as $group) {
            foreach (array_keys($package[$group] ?? []) as $dependency) {
                if (preg_match('/(?:^|\/)rollup-win32-|binding-win32-|^lightningcss-win32-|oxide-win32-/', $dependency)) {
                    unset($package[$group][$dependency]);
                    $changed = true;
                }
            }
        }
        if ($changed) {
            file_put_contents($packagePath, json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n");
            if (is_file($path.'/package-lock.json')) {
                unlink($path.'/package-lock.json');
            }
        }
    }

    private function writeDockerFiles(string $path, string $name, string $database, string $password, string $appKey, bool $includeDev): void
    {
        $composerFlags = $includeDev ? '' : ' --no-dev';
        $dockerfile = <<<'DOCKERFILE'
FROM node:22-bookworm-slim AS frontend
WORKDIR /app
COPY . .
RUN mkdir -p /frontend-build/build && if [ -f package.json ]; then npm install --include=dev --include=optional --no-audit --no-fund; fi && if [ -f package.json ] && node -e "const p=require('./package.json');process.exit(p.scripts&&p.scripts.build?0:1)"; then npm run build && cp -r public/build/. /frontend-build/build/; fi

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts --ignore-platform-reqs__COMPOSER_FLAGS__

FROM php:8.3-apache-bookworm
RUN apt-get update && apt-get install -y --no-install-recommends libpng-dev libzip-dev libicu-dev unzip && docker-php-ext-install pdo_mysql gd zip intl bcmath opcache && a2enmod rewrite && rm -rf /var/lib/apt/lists/*
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
WORKDIR /var/www/html
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=frontend /frontend-build/build ./public/build
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 80
DOCKERFILE;
        file_put_contents($path.'/.tesis.Dockerfile', str_replace('__COMPOSER_FLAGS__', $composerFlags, $dockerfile));
        file_put_contents($path.'/.dockerignore', implode("\n", [
            '.git', '.env', 'node_modules', 'vendor', 'public/build',
            'storage/logs/*', 'storage/framework/cache/*', 'storage/framework/sessions/*',
            'storage/framework/views/*', '.tesis-compose.yml',
        ])."\n");

        $yamlPassword = json_encode($password, JSON_UNESCAPED_SLASHES);
        $yamlKey = json_encode($appKey, JSON_UNESCAPED_SLASHES);
        $compose = <<<YAML
name: {$name}
services:
  app:
    build:
      context: .
      dockerfile: .tesis.Dockerfile
    restart: unless-stopped
    ports:
      - "127.0.0.1::80"
    env_file:
      - .env
    environment:
      APP_ENV: production
      APP_DEBUG: "false"
      APP_KEY: {$yamlKey}
      APP_URL: http://localhost
      DB_CONNECTION: mysql
      DB_HOST: db
      DB_PORT: "3306"
      DB_DATABASE: {$database}
      DB_USERNAME: project
      DB_PASSWORD: {$yamlPassword}
    depends_on:
      db:
        condition: service_healthy
  db:
    image: mysql:8.0
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: {$database}
      MYSQL_USER: project
      MYSQL_PASSWORD: {$yamlPassword}
      MYSQL_ROOT_PASSWORD: {$yamlPassword}
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "127.0.0.1", "-p{$password}"]
      interval: 3s
      timeout: 5s
      retries: 30
    volumes:
      - database:/var/lib/mysql
volumes:
  database:
YAML;
        file_put_contents($path.'/.tesis-compose.yml', $compose);
    }

    private function waitForApplicationContainer(string $path): void
    {
        for ($attempt = 0; $attempt < 30; $attempt++) {
            try {
                $this->compose($path, ['exec', '-T', 'app', 'php', '-v'], 30);
                return;
            } catch (RuntimeException) {
                usleep(1000000);
            }
        }
        throw new RuntimeException('El contenedor de la aplicación no inició correctamente.');
    }

    private function artisan(string $path, array $arguments): string
    {
        return $this->compose($path, array_merge(['exec', '-T', '--user', 'www-data', 'app', 'php', 'artisan'], $arguments), 600);
    }

    private function ensureWritableDirectories(string $path): void
    {
        $this->compose($path, [
            'exec', '-T', '--user', 'root', 'app', 'chown', '-R',
            'www-data:www-data', 'storage', 'bootstrap/cache',
        ], 60);
    }

    private function compose(string $path, array $arguments, int $timeout = 600): string
    {
        return $this->run(array_merge([$this->composeBinary(), '-f', '.tesis-compose.yml'], $arguments), $path, $timeout);
    }

    private function docker(array $arguments): string
    {
        return $this->run(array_merge([$this->dockerBinary()], $arguments), null, 60);
    }

    private function dockerBinary(): string
    {
        return $this->executable(config('projects.docker.binary'), 'docker.exe', 'Docker CLI');
    }

    private function composeBinary(): string
    {
        return $this->executable(config('projects.docker.compose_binary'), 'docker-compose.exe', 'Docker Compose');
    }

    private function executable(?string $configured, string $fallback, string $label): string
    {
        $candidate = $configured ?: (new ExecutableFinder())->find($fallback);
        if (!$candidate || !is_file($candidate)) {
            throw new RuntimeException('No se encontró '.$label.'. Configure su ruta en el archivo .env del sistema.');
        }
        return $candidate;
    }

    private function run(array $command, ?string $directory, int $timeout): string
    {
        $process = new Process($command, $directory);
        $process->setTimeout($timeout);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new RuntimeException('Error de Docker: '.mb_substr(trim($process->getErrorOutput()."\n".$process->getOutput()), 0, 4000));
        }
        return trim($process->getOutput());
    }

    private function validatedProjectPath(array $config): string
    {
        $path = $config['project_path'] ?? '';
        $root = realpath(storage_path('app/docker-projects'));
        $real = is_string($path) ? realpath($path) : false;
        if (!$root || !$real || !str_starts_with(strtolower($real), strtolower($root.DIRECTORY_SEPARATOR))) {
            throw new RuntimeException('La ruta del proyecto Docker no es válida.');
        }
        return $real;
    }
}
