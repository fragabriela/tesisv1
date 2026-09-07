<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use RuntimeException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class ProjectFrontendService
{
    public function build(string $path): bool
    {
        if (!is_file($path.'/package.json')) return false;
        $package = json_decode(file_get_contents($path.'/package.json'), true);
        if (!is_array($package) || empty($package['scripts']['build'])) return false;
        $manifest = $path.'/public/build/manifest.json';
        $sources = array_merge(glob($path.'/resources/js/*') ?: [], glob($path.'/resources/css/*') ?: []);
        $newestSource = $sources ? max(array_map('filemtime', $sources)) : 0;
        if (is_file($manifest) && filemtime($manifest) >= $newestSource && is_dir($path.'/node_modules')) return false;

        $npm = config('projects.npm_binary') ?: (new ExecutableFinder())->find(PHP_OS_FAMILY === 'Windows' ? 'npm.cmd' : 'npm');
        if (!$npm) throw new RuntimeException('El proyecto necesita compilar el frontend, pero no se encontró npm. Configure PROJECT_NPM_BINARY.');
        $cache = storage_path('app/project-npm/cache');
        File::ensureDirectoryExists($cache);
        $environment = [
            'npm_config_cache' => realpath($cache),
            'npm_config_fetch_retries' => '5',
            'npm_config_fetch_retry_mintimeout' => '1000',
            'npm_config_fetch_retry_maxtimeout' => '20000',
            'npm_config_prefer_offline' => 'true',
            'CI' => 'true',
        ];
        $this->runWithRetries([$npm, 'install', '--include=dev', '--no-audit', '--no-fund'], $path, $environment);
        for ($buildAttempt = 1; $buildAttempt <= 4; $buildAttempt++) {
            try {
                $this->run([$npm, 'run', 'build'], $path, $environment);
                break;
            } catch (RuntimeException $error) {
                if ($this->isRollupSourcePhaseCompatibilityError($error->getMessage())) {
                    $this->runWithRetries([
                        $npm, 'install', '--no-save', '--package-lock=false', '--include=optional',
                        '--no-audit', '--no-fund', 'rollup@^4.60.3',
                    ], $path, $environment);
                    $this->repairWindowsRollupBinary($npm, $path, $environment);
                } elseif ($this->isMissingWindowsRollupBinary($error->getMessage())) {
                    $this->repairWindowsRollupBinary($npm, $path, $environment);
                } elseif ($this->isMissingWindowsRolldownBinding($error->getMessage())) {
                    $this->repairWindowsRolldownBinding($npm, $path, $environment);
                } elseif ($this->isMissingWindowsLightningCssBinding($error->getMessage())) {
                    $this->repairWindowsLightningCssBinding($npm, $path, $environment);
                } elseif ($this->isMissingWindowsTailwindOxideBinding($error->getMessage())) {
                    $this->repairWindowsTailwindOxideBinding($npm, $path, $environment);
                } else {
                    throw $error;
                }
                if ($buildAttempt === 4) {
                    throw $error;
                }
            }
        }
        if (!is_file($manifest)) throw new RuntimeException('La compilación terminó sin crear public/build/manifest.json.');
        return true;
    }

    private function runWithRetries(array $command, string $path, array $environment): void
    {
        $lastError = null;
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $this->run($command, $path, $environment);
                return;
            } catch (RuntimeException $error) {
                $lastError = $error;
                if (!$this->isTransientInstallError($error->getMessage()) || $attempt === 3) {
                    throw $error;
                }
                usleep($attempt * 750000);
            }
        }
        throw $lastError;
    }

    public function isTransientInstallError(string $output): bool
    {
        return (bool) preg_match('/ECONNRESET|ETIMEDOUT|EAI_AGAIN|ENETUNREACH|EPERM|EBUSY/i', $output);
    }

    public function isMissingWindowsRollupBinary(string $output): bool
    {
        return PHP_OS_FAMILY === 'Windows'
            && str_contains($output, 'Cannot find module')
            && str_contains($output, '@rollup/rollup-win32-x64-msvc');
    }

    public function isRollupSourcePhaseCompatibilityError(string $output): bool
    {
        return str_contains($output, 'Source phase import')
            && str_contains($output, 'must be external');
    }

    public function isMissingWindowsRolldownBinding(string $output): bool
    {
        return PHP_OS_FAMILY === 'Windows'
            && str_contains($output, 'Cannot find native binding')
            && str_contains($output, '@rolldown/binding-win32-x64-msvc');
    }

    private function repairWindowsRolldownBinding(string $npm, string $path, array $environment): void
    {
        $packagePath = $path.'/node_modules/rolldown/package.json';
        $package = is_file($packagePath)
            ? json_decode((string) file_get_contents($packagePath), true)
            : null;
        $version = is_array($package) ? ($package['version'] ?? null) : null;

        if (!is_string($version) || !preg_match('/\A\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?\z/', $version)) {
            throw new RuntimeException('No se pudo determinar la versiÃ³n instalada de Rolldown para reparar su binding nativo de Windows.');
        }

        $this->runWithRetries([
            $npm, 'install', '--no-save', '--package-lock=false', '--include=optional',
            '--no-audit', '--no-fund', '@rolldown/binding-win32-x64-msvc@'.$version,
        ], $path, $environment);
    }

    public function isMissingWindowsLightningCssBinding(string $output): bool
    {
        return PHP_OS_FAMILY === 'Windows'
            && str_contains($output, 'Cannot find module')
            && str_contains($output, 'lightningcss.win32-x64-msvc.node');
    }

    private function repairWindowsLightningCssBinding(string $npm, string $path, array $environment): void
    {
        $packagePath = $path.'/node_modules/lightningcss/package.json';
        $package = is_file($packagePath)
            ? json_decode((string) file_get_contents($packagePath), true)
            : null;
        $version = is_array($package) ? ($package['version'] ?? null) : null;

        if (!is_string($version) || !preg_match('/\A\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?\z/', $version)) {
            throw new RuntimeException('No se pudo determinar la versiÃ³n instalada de Lightning CSS para reparar su binding nativo de Windows.');
        }

        $this->runWithRetries([
            $npm, 'install', '--no-save', '--package-lock=false', '--include=optional',
            '--no-audit', '--no-fund', 'lightningcss-win32-x64-msvc@'.$version,
        ], $path, $environment);
    }

    public function isMissingWindowsTailwindOxideBinding(string $output): bool
    {
        return PHP_OS_FAMILY === 'Windows'
            && str_contains($output, 'Cannot find native binding')
            && str_contains($output, '@tailwindcss/oxide-win32-x64-msvc');
    }

    private function repairWindowsTailwindOxideBinding(string $npm, string $path, array $environment): void
    {
        $packagePath = $path.'/node_modules/@tailwindcss/oxide/package.json';
        $package = is_file($packagePath)
            ? json_decode((string) file_get_contents($packagePath), true)
            : null;
        $version = is_array($package) ? ($package['version'] ?? null) : null;

        if (!is_string($version) || !preg_match('/\A\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?\z/', $version)) {
            throw new RuntimeException('No se pudo determinar la versiÃ³n instalada de Tailwind CSS Oxide para reparar su binding nativo de Windows.');
        }

        $this->runWithRetries([
            $npm, 'install', '--no-save', '--package-lock=false', '--include=optional',
            '--no-audit', '--no-fund', '@tailwindcss/oxide-win32-x64-msvc@'.$version,
        ], $path, $environment);
    }

    private function repairWindowsRollupBinary(string $npm, string $path, array $environment): void
    {
        $packagePath = $path.'/node_modules/rollup/package.json';
        $package = is_file($packagePath)
            ? json_decode((string) file_get_contents($packagePath), true)
            : null;
        $version = is_array($package) ? ($package['version'] ?? null) : null;

        if (!is_string($version) || !preg_match('/\A\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?\z/', $version)) {
            throw new RuntimeException('No se pudo determinar la versiÃ³n instalada de Rollup para reparar su mÃ³dulo nativo de Windows.');
        }

        $this->runWithRetries([
            $npm,
            'install',
            '--no-save',
            '--package-lock=false',
            '--include=optional',
            '--no-audit',
            '--no-fund',
            '@rollup/rollup-win32-x64-msvc@'.$version,
        ], $path, $environment);
    }

    private function run(array $command, string $path, array $environment): void
    {
        $process = new Process($command, $path, $environment);
        $process->setTimeout(600);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new RuntimeException('Error preparando el frontend: '.mb_substr(trim($process->getErrorOutput()."\n".$process->getOutput()), 0, 3000));
        }
    }
}
