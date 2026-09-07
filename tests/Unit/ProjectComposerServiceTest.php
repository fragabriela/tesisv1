<?php

namespace Tests\Unit;

use App\Services\ProjectComposerService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProjectComposerServiceTest extends TestCase
{
    private string $directory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->directory = storage_path('framework/testing/composer-'.bin2hex(random_bytes(6)));
        File::ensureDirectoryExists($this->directory);
        config(['projects.composer.runtime_path' => $this->directory.'/runtime']);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->directory);
        parent::tearDown();
    }

    public function test_php_temp_and_composer_home_are_explicit_writable_directories(): void
    {
        $script = $this->directory.'/composer.phar';
        file_put_contents($script, '<?php echo json_encode([sys_get_temp_dir(), getenv("COMPOSER_HOME"), getenv("COMPOSER_CACHE_DIR"), getenv("TEMP"), getenv("TMP"), getenv("TMPDIR")]);');
        config(['projects.composer.phar' => $script]);
        $process = (new ProjectComposerService())->installationProcess($this->directory);
        $environment = $process->getEnv();
        $process->setEnv(array_merge($environment, ['APPDATA' => false, 'USERPROFILE' => false]));
        $process->mustRun();
        $paths = json_decode($process->getOutput(), true);
        $this->assertSame($environment['TEMP'], $paths[0]);
        $this->assertSame($environment['COMPOSER_HOME'], $paths[1]);
        foreach ($paths as $path) {
            $this->assertDirectoryIsWritable($path);
            $this->assertStringStartsWith(realpath($this->directory), $path);
        }
    }

    public function test_failed_composer_install_is_reported(): void
    {
        $script = $this->directory.'/composer.phar';
        file_put_contents($script, '<?php fwrite(STDERR, "dependency conflict"); exit(2);');
        config(['projects.composer.phar' => $script]);
        $this->expectExceptionMessage('dependency conflict');
        (new ProjectComposerService())->install($this->directory);
    }

    public function test_real_composer_installs_an_offline_project_without_appdata(): void
    {
        $phar = 'C:/composer/composer.phar';
        if (!is_file($phar)) {
            $this->markTestSkipped('Local Composer unavailable.');
        }
        config(['projects.composer.phar' => $phar]);
        file_put_contents($this->directory.'/composer.json', json_encode([
            'name' => 'test/offline', 'require' => new \stdClass(),
            'repositories' => ['packagist.org' => false],
        ]));
        $process = (new ProjectComposerService())->installationProcess($this->directory);
        $process->setEnv(array_merge($process->getEnv(), ['APPDATA' => false, 'USERPROFILE' => false, 'COMPOSER_DISABLE_NETWORK' => '1']));
        $process->mustRun();
        $this->assertFileExists($this->directory.'/vendor/autoload.php');
    }
}
