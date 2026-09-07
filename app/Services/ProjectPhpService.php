<?php

namespace App\Services;

use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class ProjectPhpService
{
    public function executable(): string
    {
        $configured = config('projects.php_binary');
        $candidates = $configured ? [$configured] : $this->candidates();
        foreach (array_unique(array_filter($candidates)) as $candidate) {
            // PHP_BINARY can be httpd.exe when PHP runs as an Apache module.
            if (!preg_match('/^php(?:[0-9.]+)?(?:\.exe)?$/i', basename(str_replace('\\', '/', $candidate))) || !is_file($candidate)) {
                continue;
            }
            try {
                $probe = new Process([$candidate, '-n', '-r', 'echo PHP_SAPI;']);
                $probe->setTimeout(10);
                $probe->run();
                if ($probe->isSuccessful() && trim($probe->getOutput()) === 'cli') {
                    return $candidate;
                }
            } catch (\Throwable $e) {
                // Try the next installed CLI executable.
            }
        }
        throw new RuntimeException('No se encontró PHP CLI. Configure PROJECT_PHP_BINARY con la ruta completa a php.exe (no httpd.exe ni php-cgi.exe).');
    }

    protected function candidates(): array
    {
        $name = PHP_OS_FAMILY === 'Windows' ? 'php.exe' : 'php';
        $ini = php_ini_loaded_file();
        $laragon = glob(config('projects.laragon_path').'/bin/php/php-'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'*/'.$name) ?: [];
        return array_merge($laragon, [
            $ini ? dirname($ini).'/'.$name : null,
            PHP_BINARY,
            PHP_BINDIR.'/'.$name,
        ], [
            (new ExecutableFinder())->find('php'),
        ]);
    }
}
