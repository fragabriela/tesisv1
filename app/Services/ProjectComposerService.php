<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class ProjectComposerService
{
    public function installationProcess(string $projectPath, bool $includeDev = false): Process
    {
        $root = config('projects.composer.runtime_path') ?: storage_path('app/project-composer');
        $environment = [];
        foreach (['COMPOSER_HOME' => 'home', 'COMPOSER_CACHE_DIR' => 'cache', 'TEMP' => 'tmp', 'TMP' => 'tmp', 'TMPDIR' => 'tmp'] as $key => $directory) {
            $path = $root.'/'.$directory;
            File::ensureDirectoryExists($path);
            if (!is_writable($path)) {
                throw new RuntimeException('Composer necesita permiso de escritura en: '.$path);
            }
            $environment[$key] = realpath($path);
        }
        // Use PHP CLI and override even an explicitly configured C:\WINDOWS temp dir.
        // No global php.ini or Windows user profile is required.
        $command = [
            app(ProjectPhpService::class)->executable(), '-d', 'sys_temp_dir='.$environment['TEMP'], $this->composerPhar(),
            'install', '--no-interaction', '--prefer-dist', '--no-scripts', '--no-progress',
        ];
        if (!$includeDev) {
            $command[] = '--no-dev';
        }
        $process = new Process($command, $projectPath, $environment);
        $process->setTimeout(600);
        return $process;
    }

    public function install(string $projectPath, bool $includeDev = false): void
    {
        $process = $this->installationProcess($projectPath, $includeDev);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new RuntimeException('Error instalando dependencias: '.mb_substr(
                trim($process->getErrorOutput()."\n".$process->getOutput()), 0, 3000
            ));
        }
    }

    private function composerPhar(): string
    {
        $configured = config('projects.composer.phar');
        if ($configured) {
            if (!is_file($configured) || !is_readable($configured)) {
                throw new RuntimeException('PROJECT_COMPOSER_PHAR debe indicar un archivo composer.phar legible.');
            }
            return $configured;
        }
        $executable = (new ExecutableFinder())->find('composer');
        $candidates = [
            $executable ? dirname($executable).'/composer.phar' : null,
            config('projects.laragon_path').'/bin/composer/composer.phar',
            'C:/composer/composer.phar',
        ];
        foreach ($candidates as $candidate) {
            if ($candidate && is_file($candidate) && is_readable($candidate)) {
                return $candidate;
            }
        }
        throw new RuntimeException('No se encontró composer.phar. Configure PROJECT_COMPOSER_PHAR con su ruta completa.');
    }
}
