<?php

namespace App\Services;

use Dotenv\Dotenv;
use PDO;
use RuntimeException;
use Symfony\Component\Process\Process;

class ProjectDatabaseService
{
    private ?array $mysqlConfig = null;

    public function forEnvironment(string $content): self
    {
        try {
            $values = Dotenv::parse($content);
        } catch (\Throwable $e) {
            throw new RuntimeException('El archivo .env no tiene un formato válido. Revise las comillas y los valores.');
        }
        if (($values['DB_CONNECTION'] ?? 'mysql') !== 'mysql' || !empty($values['DB_URL']) || !empty($values['DATABASE_URL'])) {
            throw new RuntimeException('Use DB_CONNECTION=mysql y las variables DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME y DB_PASSWORD, sin URL de conexión.');
        }
        $this->validateDatabase($values['DB_DATABASE'] ?? '');
        $service = clone $this;
        $service->mysqlConfig = array_replace(config('projects.mysql'), [
            'host' => $values['DB_HOST'] ?? '127.0.0.1',
            'port' => $values['DB_PORT'] ?? '3306',
            'username' => $values['DB_USERNAME'] ?? '',
            'password' => $values['DB_PASSWORD'] ?? '',
        ]);
        if ($service->mysqlConfig['username'] === '' || !ctype_digit((string) $service->mysqlConfig['port']) ||
            (int) $service->mysqlConfig['port'] < 1 || (int) $service->mysqlConfig['port'] > 65535 ||
            preg_match('/[;\r\n]/', $service->mysqlConfig['host'])) {
            throw new RuntimeException('Revise el usuario, host y puerto MySQL del archivo .env.');
        }
        return $service;
    }

    public function connection(?string $database = null): PDO
    {
        $config = $this->mysqlConfig ?? config('projects.mysql');
        if ($database !== null) {
            $this->validateDatabase($database);
        }
        return new PDO(
            'mysql:host='.$config['host'].';port='.$config['port'].';charset=utf8mb4'.
                ($database === null ? '' : ';dbname='.$database),
            $config['username'], $config['password'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    public function validateDatabase(string $database): void
    {
        if (!preg_match('/\A[a-zA-Z0-9_]{1,64}\z/', $database) ||
            in_array(strtolower($database), ['mysql', 'sys', 'information_schema', 'performance_schema']) ||
            strcasecmp($database, (string) config('database.connections.mysql.database')) === 0) {
            throw new RuntimeException('La base de datos del proyecto debe ser independiente de la del sistema.');
        }
    }

    public function create(string $database): void
    {
        $this->validateDatabase($database);
        $this->connection()->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    }

    public function hasTables(string $database): bool
    {
        return (bool) $this->connection($database)->query('SHOW TABLES')->fetchColumn();
    }

    public function configureEnvironment(string $path, string $projectName, string $database, ?string $uploadedContent = null): void
    {
        $this->validateDatabase($database);
        foreach (['bootstrap/cache', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $directory) {
            $directoryPath = $path.'/'.$directory;
            if (!is_dir($directoryPath) && !mkdir($directoryPath, 0775, true) && !is_dir($directoryPath)) {
                throw new RuntimeException('No se pudo crear el directorio requerido: '.$directory);
            }
            if (!is_writable($directoryPath)) {
                throw new RuntimeException('El proyecto necesita permiso de escritura en: '.$directory);
            }
        }
        $envPath = $path.'/.env';
        $content = $uploadedContent ?? (is_file($envPath) ? file_get_contents($envPath) :
            (is_file($path.'/.env.example') ? file_get_contents($path.'/.env.example') : ''));
        $current = Dotenv::parse($content);
        $config = $this->mysqlConfig ?? config('projects.mysql');
        $values = [
            'APP_NAME' => $uploadedContent !== null ? ($current['APP_NAME'] ?? $projectName) : $projectName,
            'APP_ENV' => $uploadedContent !== null ? ($current['APP_ENV'] ?? 'local') : 'local',
            'APP_KEY' => $current['APP_KEY'] ?? '',
            'APP_URL' => 'http://'.$projectName.'.localhost',
            'DB_CONNECTION' => 'mysql', 'DB_HOST' => $config['host'],
            'DB_PORT' => $config['port'], 'DB_DATABASE' => $database,
            'DB_USERNAME' => $config['username'], 'DB_PASSWORD' => $config['password'],
            'DB_URL' => '', 'DATABASE_URL' => '', 'DB_SOCKET' => '',
        ];
        if ($values['APP_KEY'] === '') {
            $values['APP_KEY'] = 'base64:'.base64_encode(random_bytes(32));
        }
        foreach ($values as $key => $value) {
            $line = $key.'="'.str_replace(['\\', '"', '$', "\r", "\n"], ['\\\\', '\\"', '\\$', '', '\\n'], (string) $value).'"';
            $pattern = '/^\h*(?:export\h+)?'.preg_quote($key, '/').'\h*=.*$/m';
            $content = preg_match($pattern, $content)
                ? preg_replace_callback($pattern, fn () => $line, $content)
                : rtrim($content)."\n".$line."\n";
        }
        if (file_put_contents($envPath, $content) === false) {
            throw new RuntimeException('No se pudo escribir el entorno del proyecto.');
        }
        // Copied Laravel caches can reference the original database or dev-only providers.
        foreach (['config.php', 'packages.php', 'services.php', 'events.php', 'routes-v7.php'] as $cacheFile) {
            $cachePath = $path.'/bootstrap/cache/'.$cacheFile;
            if (is_file($cachePath) && !unlink($cachePath)) {
                throw new RuntimeException('No se pudo limpiar la caché anterior del proyecto: '.$cacheFile);
            }
        }
    }

    public function runArtisan(string $path, array $arguments): string
    {
        $env = Dotenv::parse(file_get_contents($path.'/.env'));
        // Never inherit the hosting application's database or config-cache override.
        $env = array_merge(array_fill_keys([
            'DB_CONNECTION', 'DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME',
            'DB_PASSWORD', 'DB_URL', 'DATABASE_URL', 'DB_SOCKET', 'APP_CONFIG_CACHE',
        ], false), $env);
        $process = new Process(array_merge([app(ProjectPhpService::class)->executable(), 'artisan'], $arguments), $path, $env);
        $process->setTimeout(300);
        $process->run();
        if (!$process->isSuccessful()) {
            throw new RuntimeException('Falló '.implode(' ', $arguments).': '.
                mb_substr(trim($process->getErrorOutput()."\n".$process->getOutput()), 0, 3000));
        }
        return trim($process->getOutput());
    }

    public function initialize(string $path, string $database): void
    {
        if (!is_file($path.'/artisan')) {
            return;
        }
        $migrations = glob($path.'/database/migrations/*.php') ?: [];
        $schemas = glob($path.'/database/schema/*.sql') ?: [];
        if (!$migrations && !$schemas) {
            if (!$this->hasTables($database)) {
                throw new RuntimeException('El proyecto no tiene migraciones ni tablas. Suba un backup SQL para preparar la base de datos.');
            }
            return;
        }
        $connection = $this->connection($database);
        if ($this->hasTables($database) && !$connection->query("SHOW TABLES LIKE 'migrations'")->fetchColumn()) {
            throw new RuntimeException('La base existente no tiene historial de migraciones. Use un backup completo; no se ejecutarán migraciones sobre tablas sin historial.');
        }
        $this->runArtisan($path, ['migrate', '--force', '--no-interaction']);
    }

    public function seed(string $path): string
    {
        if (!is_file($path.'/artisan') ||
            (!is_file($path.'/database/seeders/DatabaseSeeder.php') && !is_file($path.'/database/seeds/DatabaseSeeder.php'))) {
            throw new RuntimeException('El proyecto no contiene un DatabaseSeeder de Laravel.');
        }
        return $this->runArtisan($path, ['db:seed', '--force', '--no-interaction']);
    }

    public function inspectProject(string $path): array
    {
        return [
            'env_found' => is_file($path.'/.env'),
            'migrations_found' => count(glob($path.'/database/migrations/*.php') ?: []),
            'seeder_found' => is_file($path.'/database/seeders/DatabaseSeeder.php') ||
                is_file($path.'/database/seeds/DatabaseSeeder.php'),
        ];
    }

    public function testCredentials(string $path): array
    {
        $files = array_merge(glob($path.'/database/seeders/*.php') ?: [], glob($path.'/database/seeds/*.php') ?: []);
        $credentials = [];
        foreach ($files as $file) {
            preg_match_all(
                "/['\"]email['\"]\s*=>\s*['\"]([^'\"]+)['\"].{0,800}?['\"]password['\"]\s*=>\s*(?:(?:Hash::make|bcrypt)\(\s*)?['\"]([^'\"]+)['\"]\s*\)?/si",
                file_get_contents($file), $matches, PREG_SET_ORDER
            );
            foreach ($matches as $match) {
                $credentials[$match[1].'|'.$match[2]] = ['email' => $match[1], 'password' => $match[2]];
            }
        }
        return array_values(array_slice($credentials, 0, 20));
    }

    public function import(string $file, string $database): void
    {
        $this->validateDatabase($database);
        $config = $this->mysqlConfig ?? config('projects.mysql');
        $binary = $config['binary'];
        if (!$binary) {
            $binaries = glob(config('projects.laragon_path').'/bin/mysql/*/bin/mysql.exe') ?: [];
            $binary = end($binaries) ?: 'mysql';
        }
        $input = fopen($file, 'rb');
        if (!$input) {
            throw new RuntimeException('No se pudo leer el backup SQL.');
        }
        $prepared = tmpfile();
        if (!$prepared) {
            fclose($input);
            throw new RuntimeException('No se pudo preparar el SQL.');
        }
        $admin = null;
        $importUser = 'project_import_'.bin2hex(random_bytes(6));
        $importPassword = bin2hex(random_bytes(24));
        $account = "'{$importUser}'@'%'";
        $created = false;
        try {
            $this->prepareDump($input, $prepared, $database);
            rewind($prepared);
            $admin = $this->connection();
            // Dumps execute only with privileges on this project's database.
            // Qualified tables referring to another database must fail.
            $admin->exec("CREATE USER {$account} IDENTIFIED BY '{$importPassword}'");
            $created = true;
            $admin->exec("GRANT ALL PRIVILEGES ON `{$database}`.* TO {$account}");
            // Fail on the first SQL error; empty stdout is normal for a successful import.
            $process = new Process([
                $binary, '--host='.$config['host'], '--port='.$config['port'],
                '--user='.$importUser, '--default-character-set=utf8mb4',
                '--binary-mode', '--database='.$database,
            ], null, ['MYSQL_PWD' => $importPassword], $prepared, 300);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new RuntimeException('No se pudo importar el backup: '.mb_substr($process->getErrorOutput(), 0, 3000));
            }
            if (!$this->hasTables($database)) {
                throw new RuntimeException('El backup no creó tablas en la base del proyecto.');
            }
        } finally {
            fclose($input);
            fclose($prepared);
            if ($created) {
                $admin->exec("DROP USER IF EXISTS {$account}");
            }
        }
    }

    private function prepareDump($input, $output, string $database): void
    {
        $quote = null;
        $blockComment = false;
        $sourceDatabase = null;
        while (($line = fgets($input)) !== false) {
            if ($quote === null && !$blockComment) {
                // Standard mysqldump database headers refer to the original name.
                if (preg_match('/^\h*CREATE\h+(?:DATABASE|SCHEMA)\b[^;]*;\h*$/i', rtrim($line))) {
                    continue;
                }
                if (preg_match('/^\h*USE\h+(`(?:[^`]|``)+`|[a-zA-Z0-9_]+)\h*;\h*$/i', rtrim($line), $match)) {
                    $name = str_replace('``', '`', trim($match[1], '`'));
                    if ($sourceDatabase !== null && $sourceDatabase !== $name) {
                        throw new RuntimeException('El backup contiene varias bases de datos. Exporte solamente la del proyecto.');
                    }
                    $sourceDatabase = $name;
                    fwrite($output, "USE `{$database}`;\n");
                    continue;
                }
            }
            fwrite($output, $line);
            // Track SQL strings/comments so a multiline value is never rewritten.
            for ($i = 0, $length = strlen($line); $i < $length; $i++) {
                $char = $line[$i];
                $next = $line[$i + 1] ?? '';
                if ($blockComment) {
                    if ($char === '*' && $next === '/') {
                        $blockComment = false;
                        $i++;
                    }
                } elseif ($quote !== null) {
                    if ($char === '\\') {
                        $i++;
                    } elseif ($char === $quote) {
                        if ($next === $quote) {
                            $i++;
                        } else {
                            $quote = null;
                        }
                    }
                } elseif ($char === "'" || $char === '"' || $char === '`') {
                    $quote = $char;
                } elseif ($char === '/' && $next === '*') {
                    $blockComment = true;
                    $i++;
                } elseif ($char === '#' || ($char === '-' && $next === '-' && ctype_space($line[$i + 2] ?? ' '))) {
                    break;
                }
            }
        }
    }

    public function preparedBackupStream(string $file, string $database)
    {
        $this->validateDatabase($database);
        $input = fopen($file, 'rb');
        $output = tmpfile();
        if (!$input || !$output) {
            if (is_resource($input)) fclose($input);
            if (is_resource($output)) fclose($output);
            throw new RuntimeException('No se pudo preparar el backup SQL.');
        }
        try {
            $this->prepareDump($input, $output, $database);
            rewind($output);
            return $output;
        } catch (\Throwable $error) {
            fclose($output);
            throw $error;
        } finally {
            fclose($input);
        }
    }

    public function restoreBackup(string $file, string $database): void
    {
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if ($extension === 'sql') {
            $this->import($file, $database);
            return;
        }
        $temporary = tmpfile();
        if (!$temporary) {
            throw new RuntimeException('No se pudo preparar el archivo SQL temporal.');
        }
        try {
            if ($extension === 'zip') {
                $zip = new \ZipArchive();
                if ($zip->open($file) !== true) {
                    throw new RuntimeException('El ZIP no es válido.');
                }
                try {
                    $entries = [];
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $name = $zip->getNameIndex($i);
                        if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) === 'sql') {
                            $entries[] = $name;
                        }
                    }
                    if (count($entries) !== 1) {
                        throw new RuntimeException('El backup debe contener exactamente un archivo SQL.');
                    }
                    $stream = $zip->getStream($entries[0]);
                    if (!$stream) {
                        throw new RuntimeException('No se pudo leer el SQL del ZIP.');
                    }
                    try {
                        $this->copySqlStream($stream, $temporary);
                    } finally {
                        fclose($stream);
                    }
                } finally {
                    $zip->close();
                }
            } elseif (in_array($extension, ['tar', 'gz'])) {
                try {
                    $archive = new \PharData($file);
                    $entries = [];
                    foreach (new \RecursiveIteratorIterator($archive) as $entry) {
                        if ($entry->isFile() && strtolower($entry->getExtension()) === 'sql') {
                            $entries[] = $entry->getPathname();
                        }
                    }
                    if (count($entries) !== 1) {
                        throw new RuntimeException('El backup debe contener exactamente un archivo SQL.');
                    }
                    $stream = fopen($entries[0], 'rb');
                } catch (\UnexpectedValueException $e) {
                    if ($extension !== 'gz') {
                        throw $e;
                    }
                    $stream = gzopen($file, 'rb');
                }
                if (!$stream) {
                    throw new RuntimeException('No se pudo leer el SQL comprimido.');
                }
                try {
                    $this->copySqlStream($stream, $temporary);
                } finally {
                    fclose($stream);
                }
            } else {
                throw new RuntimeException('Formato de backup no compatible.');
            }
            fflush($temporary);
            $this->import(stream_get_meta_data($temporary)['uri'], $database);
        } finally {
            fclose($temporary);
        }
    }

    private function copySqlStream($source, $destination): void
    {
        $limit = 100 * 1024 * 1024;
        $copied = stream_copy_to_stream($source, $destination, $limit + 1);
        if ($copied === false || $copied === 0 || $copied > $limit) {
            throw new RuntimeException('El SQL debe tener contenido y no superar 100 MB descomprimido.');
        }
    }
}
