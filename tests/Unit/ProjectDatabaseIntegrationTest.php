<?php

namespace Tests\Unit;

use App\Services\ProjectDatabaseService;
use RuntimeException;
use Tests\TestCase;

class ProjectDatabaseIntegrationTest extends TestCase
{
    private ?string $database = null;
    private $sql;

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('PROJECT_DB_INTEGRATION') !== '1') {
            $this->markTestSkipped('Set PROJECT_DB_INTEGRATION=1 to test an isolated MySQL database.');
        }
        $this->database = 'project_db_test_'.bin2hex(random_bytes(8));
        (new ProjectDatabaseService())->create($this->database);
        $this->sql = tmpfile();
    }

    protected function tearDown(): void
    {
        if (is_resource($this->sql)) {
            fclose($this->sql);
        }
        if ($this->database && preg_match('/\Aproject_db_test_[a-f0-9]{16}\z/', $this->database)) {
            (new ProjectDatabaseService())->connection()->exec('DROP DATABASE `'.$this->database.'`');
        }
        parent::tearDown();
    }

    private function sqlFile(string $sql): string
    {
        fwrite($this->sql, $sql);
        fflush($this->sql);
        return stream_get_meta_data($this->sql)['uri'];
    }

    public function test_import_creates_tables_and_data_despite_empty_client_output(): void
    {
        $service = new ProjectDatabaseService();
        $service->import($this->sqlFile('CREATE TABLE sample (id INT PRIMARY KEY, value VARCHAR(30)); INSERT INTO sample VALUES (1, "restored");'), $this->database);
        $this->assertSame('restored', $service->connection($this->database)->query('SELECT value FROM sample WHERE id=1')->fetchColumn());
        // A second deployment creates no second database and preserves data.
        $service->create($this->database);
        $this->assertSame(1, (int) $service->connection($this->database)->query('SELECT COUNT(*) FROM sample')->fetchColumn());
    }

    public function test_sql_errors_are_reported(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No se pudo importar');
        (new ProjectDatabaseService())->import($this->sqlFile('INSERT INTO missing_table VALUES (1);'), $this->database);
    }

    public function test_backup_cannot_write_to_the_mysql_system_database(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No se pudo importar');
        (new ProjectDatabaseService())->import($this->sqlFile('SELECT * FROM mysql.user;'), $this->database);
    }

    public function test_original_dump_database_name_is_redirected_without_changing_string_values(): void
    {
        $sql = "CREATE DATABASE IF NOT EXISTS original_database;\nUSE `original_database`;\n".
            "CREATE TABLE sample (value TEXT);\nINSERT INTO sample VALUES ('start\nUSE original_database;\nend');\n";
        $service = new ProjectDatabaseService();
        $service->import($this->sqlFile($sql), $this->database);
        $this->assertSame("start\nUSE original_database;\nend", $service->connection($this->database)->query('SELECT value FROM sample')->fetchColumn());
    }
}
