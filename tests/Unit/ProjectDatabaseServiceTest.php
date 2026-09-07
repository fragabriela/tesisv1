<?php

namespace Tests\Unit;

use App\Services\ProjectDatabaseService;
use Dotenv\Dotenv;
use Illuminate\Support\Facades\File;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class ProjectDatabaseServiceTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = storage_path('framework/testing/project-db-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($this->directory);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_uploaded_environment_keeps_its_database_credentials_and_custom_settings(): void
    {
        $content = "DB_CONNECTION=mysql\nDB_HOST=127.0.0.2\nDB_PORT=3307\nDB_DATABASE=uploaded_project\nDB_USERNAME=project_user\nDB_PASSWORD=project_password\nAPP_KEY=base64:uploaded\nMAIL_MAILER=log\n";
        $original = config('projects.mysql');
        $service = (new ProjectDatabaseService())->forEnvironment($content);
        $service->configureEnvironment($this->directory, 'demo', 'uploaded_project', $content);
        $values = Dotenv::parse(file_get_contents($this->directory.'/.env'));
        $this->assertSame('uploaded_project', $values['DB_DATABASE']);
        $this->assertSame('127.0.0.2', $values['DB_HOST']);
        $this->assertSame('3307', $values['DB_PORT']);
        $this->assertSame('project_user', $values['DB_USERNAME']);
        $this->assertSame('project_password', $values['DB_PASSWORD']);
        $this->assertSame('base64:uploaded', $values['APP_KEY']);
        $this->assertSame('log', $values['MAIL_MAILER']);
        $this->assertSame($original, config('projects.mysql'));
    }

    public function test_invalid_uploaded_environment_does_not_expose_its_content(): void
    {
        try {
            (new ProjectDatabaseService())->forEnvironment('DB_PASSWORD="secret_without_closing_quote');
            $this->fail('Expected malformed environment to fail.');
        } catch (RuntimeException $e) {
            $this->assertNotSame('', $e->getMessage());
            $this->assertStringNotContainsString('secret_without_closing_quote', $e->getMessage());
        }
    }

    public function test_explicit_seeding_runs_database_seeder(): void
    {
        $service = new ProjectDatabaseService();
        $service->configureEnvironment($this->directory, 'demo', 'project_test_database');
        File::ensureDirectoryExists($this->directory.'/database/seeders');
        file_put_contents($this->directory.'/database/seeders/DatabaseSeeder.php', '<?php');
        file_put_contents($this->directory.'/artisan', '<?php file_put_contents(__DIR__."/seed-command", implode(" ", array_slice($argv, 1)));');
        $service->seed($this->directory);
        $this->assertSame('db:seed --force --no-interaction', file_get_contents($this->directory.'/seed-command'));
    }

    public function test_explicit_seeding_without_database_seeder_reports_error(): void
    {
        $this->expectExceptionMessage('no contiene un DatabaseSeeder');
        (new ProjectDatabaseService())->seed($this->directory);
    }

    public function test_project_inspection_detects_environment_migrations_and_seeder(): void
    {
        File::ensureDirectoryExists($this->directory.'/database/migrations');
        File::ensureDirectoryExists($this->directory.'/database/seeders');
        file_put_contents($this->directory.'/.env', 'APP_ENV=local');
        file_put_contents($this->directory.'/database/migrations/one.php', '<?php');
        file_put_contents($this->directory.'/database/migrations/two.php', '<?php');
        file_put_contents($this->directory.'/database/seeders/DatabaseSeeder.php', '<?php');
        $this->assertSame([
            'env_found' => true,
            'migrations_found' => 2,
            'seeder_found' => true,
        ], (new ProjectDatabaseService())->inspectProject($this->directory));
    }

    public function test_test_credentials_are_extracted_from_seeders_without_database_access(): void
    {
        File::ensureDirectoryExists($this->directory.'/database/seeders');
        file_put_contents($this->directory.'/database/seeders/UserSeeder.php', <<<'PHP'
<?php
User::create([
    'email' => 'demo@example.com',
    'password' => Hash::make('secret123'),
]);
User::updateOrCreate(
    ['email' => 'admin@example.com'],
    ['name' => 'Admin', 'password' => 'Admin2024!']
);
PHP);
        $this->assertSame([
            ['email' => 'demo@example.com', 'password' => 'secret123'],
            ['email' => 'admin@example.com', 'password' => 'Admin2024!'],
        ], (new ProjectDatabaseService())->testCredentials($this->directory));
    }

    public function test_environment_preserves_key_and_quotes_credentials_and_removes_cached_database(): void
    {
        config(['projects.mysql.password' => 'a $secret # "quoted" \\ end']);
        file_put_contents($this->directory.'/.env', "APP_KEY=base64:existing\nDB_DATABASE=original\nMAIL_MAILER=log\n");
        File::ensureDirectoryExists($this->directory.'/bootstrap/cache');
        file_put_contents($this->directory.'/bootstrap/cache/config.php', '<?php return [];');
        file_put_contents($this->directory.'/bootstrap/cache/services.php', '<?php return [];');
        file_put_contents($this->directory.'/bootstrap/cache/packages.php', '<?php return [];');
        $service = new ProjectDatabaseService();
        $service->configureEnvironment($this->directory, 'demo', 'project_test_database');
        $values = Dotenv::parse(file_get_contents($this->directory.'/.env'));
        $this->assertSame(config('projects.mysql.password'), $values['DB_PASSWORD']);
        $this->assertSame('base64:existing', $values['APP_KEY']);
        $this->assertSame('project_test_database', $values['DB_DATABASE']);
        $this->assertSame('http://demo.localhost', $values['APP_URL']);
        $this->assertSame('log', $values['MAIL_MAILER']);
        $this->assertFileDoesNotExist($this->directory.'/bootstrap/cache/config.php');
        $this->assertFileDoesNotExist($this->directory.'/bootstrap/cache/services.php');
        $this->assertFileDoesNotExist($this->directory.'/bootstrap/cache/packages.php');
        $this->assertDirectoryExists($this->directory.'/storage/framework/cache/data');
        $this->assertDirectoryExists($this->directory.'/storage/framework/sessions');
        $this->assertDirectoryExists($this->directory.'/storage/framework/views');
        $this->assertDirectoryExists($this->directory.'/storage/logs');
    }

    public function test_artisan_receives_project_database_instead_of_parent_database(): void
    {
        (new ProjectDatabaseService())->configureEnvironment($this->directory, 'demo', 'project_test_database');
        file_put_contents($this->directory.'/artisan', '<?php file_put_contents(__DIR__."/result", getenv("DB_DATABASE"));');
        $previous = getenv('DB_DATABASE');
        putenv('DB_DATABASE=hosting_application');
        try {
            (new ProjectDatabaseService())->runArtisan($this->directory, ['migrate', '--force']);
            $this->assertSame('project_test_database', file_get_contents($this->directory.'/result'));
        } finally {
            putenv($previous === false ? 'DB_DATABASE' : 'DB_DATABASE='.$previous);
        }
    }

    public function test_failed_artisan_command_is_not_reported_as_success(): void
    {
        (new ProjectDatabaseService())->configureEnvironment($this->directory, 'demo', 'project_test_database');
        file_put_contents($this->directory.'/artisan', '<?php fwrite(STDERR, "migration failed"); exit(1);');
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('migration failed');
        (new ProjectDatabaseService())->runArtisan($this->directory, ['migrate', '--force']);
    }

    public function test_empty_laravel_project_without_migrations_requires_backup(): void
    {
        file_put_contents($this->directory.'/artisan', '<?php');
        $service = Mockery::mock(ProjectDatabaseService::class)->makePartial();
        $service->shouldReceive('hasTables')->with('project_test_database')->andReturn(false);
        $service->shouldNotReceive('runArtisan');
        $this->expectExceptionMessage('no tiene migraciones ni tablas');
        $service->initialize($this->directory, 'project_test_database');
    }

    public function test_existing_database_without_migrations_is_reused(): void
    {
        file_put_contents($this->directory.'/artisan', '<?php');
        $service = Mockery::mock(ProjectDatabaseService::class)->makePartial();
        $service->shouldReceive('hasTables')->once()->andReturn(true);
        $service->shouldNotReceive('runArtisan');
        $service->initialize($this->directory, 'project_test_database');
        $this->addToAssertionCount(1);
    }

    public function test_migrations_run_without_seeders(): void
    {
        file_put_contents($this->directory.'/artisan', '<?php');
        File::ensureDirectoryExists($this->directory.'/database/migrations');
        file_put_contents($this->directory.'/database/migrations/create_users.php', '<?php');
        $service = Mockery::mock(ProjectDatabaseService::class)->makePartial();
        $service->shouldReceive('connection')->andReturn(Mockery::mock(\PDO::class));
        $service->shouldReceive('hasTables')->andReturn(false);
        $service->shouldReceive('runArtisan')->once()->with($this->directory, ['migrate', '--force', '--no-interaction']);
        $service->initialize($this->directory, 'project_test_database');
    }

    public function test_hosting_database_cannot_be_used_as_project_database(): void
    {
        config(['database.connections.mysql.database' => 'host_database']);
        $this->expectException(RuntimeException::class);
        (new ProjectDatabaseService())->validateDatabase('host_database');
    }

    public function test_zip_without_sql_is_rejected(): void
    {
        if (!class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZIP extension unavailable.');
        }
        $zip = new \ZipArchive();
        $zip->open($this->directory.'/backup.zip', \ZipArchive::CREATE);
        $zip->addFromString('readme.txt', 'no database');
        $zip->close();
        $this->expectExceptionMessage('exactamente un archivo SQL');
        (new ProjectDatabaseService())->restoreBackup($this->directory.'/backup.zip', 'project_test_database');
    }

    public function test_zip_sql_is_imported_without_extracting_other_files(): void
    {
        if (!class_exists(\ZipArchive::class)) {
            $this->markTestSkipped('ZIP extension unavailable.');
        }
        $zip = new \ZipArchive();
        $zip->open($this->directory.'/backup.zip', \ZipArchive::CREATE);
        $zip->addFromString('nested/database.sql', 'CREATE TABLE example (id INT);');
        $zip->addFromString('../unwanted.php', '<?php');
        $zip->close();
        $service = Mockery::mock(ProjectDatabaseService::class)->makePartial();
        $service->shouldReceive('import')->once()->withArgs(function ($file, $database) {
            return file_get_contents($file) === 'CREATE TABLE example (id INT);' && $database === 'project_test_database';
        });
        $service->restoreBackup($this->directory.'/backup.zip', 'project_test_database');
    }
}
