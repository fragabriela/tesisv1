<?php

namespace Tests\Unit;

use App\Services\ProjectFrontendService;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ProjectFrontendServiceTest extends TestCase
{
    private string $directory;
    protected function setUp(): void { parent::setUp(); $this->directory = storage_path('framework/testing/frontend-'.bin2hex(random_bytes(6))); File::ensureDirectoryExists($this->directory); }
    protected function tearDown(): void { File::deleteDirectory($this->directory); parent::tearDown(); }

    public function test_project_without_package_does_not_require_a_build(): void
    {
        $this->assertFalse((new ProjectFrontendService())->build($this->directory));
    }

    public function test_project_without_build_script_does_not_require_a_build(): void
    {
        file_put_contents($this->directory.'/package.json', '{"scripts":{"dev":"vite"}}');
        $this->assertFalse((new ProjectFrontendService())->build($this->directory));
    }

    public function test_current_manifest_skips_reinstalling_frontend(): void
    {
        File::ensureDirectoryExists($this->directory.'/resources/js');
        File::ensureDirectoryExists($this->directory.'/public/build');
        File::ensureDirectoryExists($this->directory.'/node_modules');
        file_put_contents($this->directory.'/resources/js/app.js', 'console.log(1)');
        file_put_contents($this->directory.'/package.json', '{"scripts":{"build":"vite build"}}');
        file_put_contents($this->directory.'/public/build/manifest.json', '{}');
        touch($this->directory.'/public/build/manifest.json', time() + 1);
        config(['projects.npm_binary' => 'missing-npm']);
        $this->assertFalse((new ProjectFrontendService())->build($this->directory));
    }

    public function test_network_and_windows_lock_errors_are_retryable(): void
    {
        $service = new ProjectFrontendService();
        $this->assertTrue($service->isTransientInstallError('npm error read ECONNRESET'));
        $this->assertTrue($service->isTransientInstallError('EPERM: operation not permitted'));
        $this->assertFalse($service->isTransientInstallError('ERESOLVE unable to resolve dependency tree'));
    }

    public function test_missing_windows_rollup_binary_is_detected(): void
    {
        $service = new ProjectFrontendService();
        $message = "Error: Cannot find module '@rollup/rollup-win32-x64-msvc'";

        $this->assertSame(PHP_OS_FAMILY === 'Windows', $service->isMissingWindowsRollupBinary($message));
        $this->assertFalse($service->isMissingWindowsRollupBinary('Error: Cannot find module vite'));
    }

    public function test_rollup_source_phase_compatibility_error_is_detected(): void
    {
        $service = new ProjectFrontendService();

        $this->assertTrue($service->isRollupSourcePhaseCompatibilityError(
            'Source phase import "./bootstrap" must be external.'
        ));
        $this->assertFalse($service->isRollupSourcePhaseCompatibilityError('Rollup failed to resolve import'));
    }

    public function test_missing_windows_rolldown_binding_is_detected(): void
    {
        $service = new ProjectFrontendService();
        $message = "Cannot find native binding: Cannot find module '@rolldown/binding-win32-x64-msvc'";

        $this->assertSame(PHP_OS_FAMILY === 'Windows', $service->isMissingWindowsRolldownBinding($message));
        $this->assertFalse($service->isMissingWindowsRolldownBinding('Cannot find native binding for Linux'));
    }

    public function test_missing_windows_lightning_css_binding_is_detected(): void
    {
        $service = new ProjectFrontendService();
        $message = "Cannot find module '../lightningcss.win32-x64-msvc.node'";

        $this->assertSame(PHP_OS_FAMILY === 'Windows', $service->isMissingWindowsLightningCssBinding($message));
        $this->assertFalse($service->isMissingWindowsLightningCssBinding('Cannot find module lightningcss-linux-x64'));
    }

    public function test_missing_windows_tailwind_oxide_binding_is_detected(): void
    {
        $service = new ProjectFrontendService();
        $message = "Cannot find native binding: Cannot find module '@tailwindcss/oxide-win32-x64-msvc'";

        $this->assertSame(PHP_OS_FAMILY === 'Windows', $service->isMissingWindowsTailwindOxideBinding($message));
        $this->assertFalse($service->isMissingWindowsTailwindOxideBinding('Cannot find native binding for Linux'));
    }
}
