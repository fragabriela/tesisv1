<?php

namespace Tests\Unit;

use App\Services\ProjectPhpService;
use Mockery;
use Tests\TestCase;

class ProjectPhpServiceTest extends TestCase
{
    public function test_apache_binary_is_skipped_in_favor_of_php_cli(): void
    {
        config(['projects.php_binary' => null]);
        $service = Mockery::mock(ProjectPhpService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('candidates')->once()->andReturn([
            'C:/laragon/bin/apache/httpd-2.4.54-win64-VS16/bin/httpd.exe',
            PHP_BINARY,
        ]);
        $this->assertSame(PHP_BINARY, $service->executable());
    }

    public function test_explicit_cli_configuration_is_used(): void
    {
        config(['projects.php_binary' => PHP_BINARY]);
        $this->assertSame(PHP_BINARY, (new ProjectPhpService())->executable());
    }

    public function test_configured_apache_is_rejected_without_running_it(): void
    {
        config(['projects.php_binary' => 'C:/laragon/bin/apache/httpd-2.4.54-win64-VS16/bin/httpd.exe']);
        $this->expectExceptionMessage('Configure PROJECT_PHP_BINARY');
        (new ProjectPhpService())->executable();
    }
}
