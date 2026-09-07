<?php

namespace Tests\Unit;

use App\Http\Controllers\ProyectoController;
use App\Models\Tesis;
use App\Services\LaragonService;
use App\Services\DockerProjectService;
use App\Services\ProjectBackupService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class ProjectDeploymentFlowTest extends TestCase
{
    public function test_uploaded_env_and_selected_seeders_work_without_backup(): void
    {
        $tesis = Tesis::create(['titulo' => 'Demo', 'project_repo_path' => 'repos/demo']);
        $env = "DB_CONNECTION=mysql\nDB_DATABASE=uploaded_project\nDB_USERNAME=project_user\nDB_PASSWORD=example\n";
        $service = Mockery::mock(LaragonService::class);
        $result = $this->deploymentResult();
        $result['project_config']['project_path'] = 'fixture-project';
        $service->shouldReceive('deployProject')->once()->withArgs(function ($target, $withBackup, $options) use ($env) {
            return !$withBackup && $options['env_content'] === $env && $options['run_migrations'] === true;
        })->andReturn($result);
        $databaseService = Mockery::mock(\App\Services\ProjectDatabaseService::class)->makePartial();
        $databaseService->shouldReceive('seed')->once()->with('fixture-project');
        $this->app->instance(\App\Services\ProjectDatabaseService::class, $databaseService);
        $request = Request::create('/deploy', 'POST', ['deployment_target' => 'laragon', 'run_migrations' => '1', 'run_seeders' => '1'], [], [
            'env_file' => \Illuminate\Http\UploadedFile::fake()->createWithContent('.env', $env),
        ]);
        (new ProyectoController($service))->deploy($request, $tesis->id);
        $this->assertSame('running', $tesis->fresh()->container_status);
        $this->assertFalse($tesis->fresh()->backup_restored);
        $this->assertStringNotContainsString('DB_PASSWORD', json_encode($tesis->fresh()->project_config));
    }

    public function test_migrations_can_be_disabled_and_seeders_are_not_run_by_default(): void
    {
        $tesis = Tesis::create(['titulo' => 'Demo', 'project_repo_path' => 'repos/demo']);
        $service = Mockery::mock(LaragonService::class);
        $service->shouldReceive('deployProject')->once()->with(Mockery::type(Tesis::class), false, ['run_migrations' => false])->andReturn($this->deploymentResult());
        $databaseService = Mockery::mock(\App\Services\ProjectDatabaseService::class);
        $databaseService->shouldNotReceive('seed');
        $this->app->instance(\App\Services\ProjectDatabaseService::class, $databaseService);
        (new ProyectoController($service))->deploy(Request::create('/deploy', 'POST', ['deployment_target' => 'laragon', 'run_migrations' => '0']), $tesis->id);
        $this->assertSame('running', $tesis->fresh()->container_status);
    }

    public function test_first_deployment_runs_detected_seeder_and_saves_test_credentials(): void
    {
        $tesis = Tesis::create(['titulo' => 'Demo', 'project_repo_path' => 'repos/demo']);
        $result = $this->deploymentResult();
        $result['project_config']['project_path'] = 'fixture-project';
        $result['project_config']['capabilities'] = [
            'env_found' => false, 'migrations_found' => 3, 'seeder_found' => true, 'database_was_empty' => true,
        ];
        $service = Mockery::mock(LaragonService::class);
        $service->shouldReceive('deployProject')->once()->with(Mockery::type(Tesis::class), false)->andReturn($result);
        $databaseService = Mockery::mock(\App\Services\ProjectDatabaseService::class);
        $databaseService->shouldReceive('seed')->once()->with('fixture-project')->andReturn('seeded');
        $databaseService->shouldReceive('testCredentials')->once()->with('fixture-project')->andReturn([
            ['email' => 'demo@example.com', 'password' => 'secret'],
        ]);
        $this->app->instance(\App\Services\ProjectDatabaseService::class, $databaseService);
        (new ProyectoController($service))->deploy(Request::create('/deploy', 'POST', ['deployment_target' => 'laragon']), $tesis->id);
        $config = $tesis->fresh()->project_config;
        $this->assertTrue($config['seeders_executed']);
        $this->assertSame('demo@example.com', $config['test_credentials'][0]['email']);
    }

    public function test_docker_is_the_default_deployment_target(): void
    {
        $tesis = Tesis::create(['titulo' => 'Demo', 'project_repo_path' => 'repos/demo']);
        $laragon = Mockery::mock(LaragonService::class);
        $laragon->shouldNotReceive('deployProject');
        $docker = Mockery::mock(DockerProjectService::class);
        $result = $this->dockerDeploymentResult();
        $result['project_config']['capabilities'] = ['seeder_found' => false, 'database_was_empty' => true];
        $docker->shouldReceive('deployProject')->once()
            ->with(Mockery::type(Tesis::class), false)
            ->andReturn($result);

        (new ProyectoController($laragon, $docker))->deploy(Request::create('/deploy', 'POST'), $tesis->id);

        $this->assertSame('docker', $tesis->fresh()->project_config['deployment_type']);
        $this->assertSame('running', $tesis->fresh()->container_status);
    }

    public function test_redeployment_preserves_credentials_and_does_not_repeat_seeders(): void
    {
        $credentials = [['email' => 'admin@example.com', 'password' => 'secret']];
        $tesis = Tesis::create([
            'titulo' => 'Demo',
            'project_repo_path' => 'repos/demo',
            'project_config' => [
                'deployment_type' => 'laragon',
                'seeders_executed' => true,
                'test_credentials' => $credentials,
            ],
        ]);
        $service = Mockery::mock(LaragonService::class);
        $result = $this->deploymentResult();
        $result['project_config']['capabilities'] = ['seeder_found' => true, 'database_was_empty' => false];
        $service->shouldReceive('deployProject')->once()->andReturn($result);
        $databaseService = Mockery::mock(\App\Services\ProjectDatabaseService::class);
        $databaseService->shouldNotReceive('seed');
        $this->app->instance(\App\Services\ProjectDatabaseService::class, $databaseService);

        (new ProyectoController($service))->deploy(
            Request::create('/deploy', 'POST', ['deployment_target' => 'laragon']), $tesis->id
        );

        $this->assertSame($credentials, $tesis->fresh()->project_config['test_credentials']);
        $this->assertTrue($tesis->fresh()->project_config['seeders_executed']);
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        Schema::create('tesis', function (Blueprint $table) {
            $table->id();
            foreach (['titulo', 'project_repo_path', 'container_id', 'container_status', 'project_url', 'project_config', 'deployment_error'] as $column) {
                $table->text($column)->nullable();
            }
            $table->boolean('backup_restored')->default(false);
            $table->boolean('env_configured')->default(false);
            $table->timestamp('backup_restored_at')->nullable();
            $table->timestamp('last_deployed')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('project_backups', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tesis_id');
            $table->text('file_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function deploymentResult(): array
    {
        return [
            'container_id' => 'demo-proyecto-1', 'container_status' => 'running',
            'project_url' => 'http://demo-proyecto-1.test',
            'project_config' => ['deployment_type' => 'laragon', 'external_port' => 80, 'database_name' => 'demo_proyecto_1'],
        ];
    }

    public function test_failed_database_preparation_is_saved_as_failed(): void
    {
        $tesis = Tesis::create(['titulo' => 'Demo', 'project_repo_path' => 'repos/demo']);
        $service = Mockery::mock(LaragonService::class);
        $docker = Mockery::mock(DockerProjectService::class);
        $docker->shouldReceive('deployProject')->once()->andThrow(new \RuntimeException('Database denied'));
        (new ProyectoController($service, $docker))->deployWithoutBackup($tesis->id);
        $tesis->refresh();
        $this->assertSame('failed', $tesis->container_status);
        $this->assertSame('Database denied', $tesis->deployment_error);
        $this->assertFalse($tesis->backup_restored);
    }

    public function test_deployment_without_backup_does_not_claim_a_backup_was_restored(): void
    {
        $tesis = Tesis::create(['titulo' => 'Demo', 'project_repo_path' => 'repos/demo']);
        $service = Mockery::mock(LaragonService::class);
        $docker = Mockery::mock(DockerProjectService::class);
        $docker->shouldReceive('deployProject')->once()->with(Mockery::type(Tesis::class), false)->andReturn($this->dockerDeploymentResult());
        (new ProyectoController($service, $docker))->deployWithoutBackup($tesis->id);
        $tesis->refresh();
        $this->assertSame('running', $tesis->container_status);
        $this->assertTrue($tesis->env_configured);
        $this->assertFalse($tesis->backup_restored);
        $this->assertNull($tesis->backup_restored_at);
    }

    public function test_backup_restore_uses_refreshed_laragon_destination_and_boolean_result(): void
    {
        $tesis = Tesis::create(['titulo' => 'Demo', 'project_repo_path' => 'repos/demo']);
        $backup = $tesis->backups()->create(['file_path' => 'unused.sql']);
        $service = Mockery::mock(LaragonService::class);
        $service->shouldReceive('deployProject')->once()->with(Mockery::type(Tesis::class), true)->andReturn($this->deploymentResult());
        $restore = Mockery::mock(ProjectBackupService::class);
        $restore->shouldReceive('restoreBackupToContainer')->once()->withArgs(function ($target, $selected) use ($backup) {
            return $target->container_id === 'demo-proyecto-1' && $target->project_config['deployment_type'] === 'laragon' && $selected->id === $backup->id;
        })->andReturn(true);
        $this->app->instance(ProjectBackupService::class, $restore);
        $request = Request::create('/proyectos/'.$tesis->id.'/deploy', 'POST', ['deployment_target' => 'laragon', 'existing_backup_id' => $backup->id]);
        (new ProyectoController($service))->deploy($request, $tesis->id);
        $tesis->refresh();
        $this->assertTrue($tesis->backup_restored);
        $this->assertSame('running', $tesis->container_status);
        $this->assertNotNull($tesis->backup_restored_at);
    }

    public function test_data_only_backup_runs_migrations_first_and_failed_restore_is_persisted(): void
    {
        $tesis = Tesis::create(['titulo' => 'Demo', 'project_repo_path' => 'repos/demo']);
        $backup = $tesis->backups()->create(['file_path' => 'unused.sql']);
        $service = Mockery::mock(LaragonService::class);
        $service->shouldReceive('deployProject')->once()->with(Mockery::type(Tesis::class), false)->andReturn($this->deploymentResult());
        $restore = Mockery::mock(ProjectBackupService::class);
        $restore->shouldReceive('restoreBackupToContainer')->once()->andThrow(new \RuntimeException('SQL failed'));
        $this->app->instance(ProjectBackupService::class, $restore);
        $request = Request::create('/proyectos/'.$tesis->id.'/deploy', 'POST', ['deployment_target' => 'laragon', 'existing_backup_id' => $backup->id, 'backup_data_only' => '1']);
        (new ProyectoController($service))->deploy($request, $tesis->id);
        $tesis->refresh();
        $this->assertFalse($tesis->backup_restored);
        $this->assertSame('failed', $tesis->container_status);
        $this->assertSame('SQL failed', $tesis->deployment_error);
    }

    private function dockerDeploymentResult(): array
    {
        $result = $this->deploymentResult();
        $result['project_url'] = 'http://localhost:49152';
        $result['project_config']['deployment_type'] = 'docker';
        $result['project_config']['external_port'] = 49152;
        return $result;
    }
}
