<?php

namespace App\Http\Controllers;

use App\Models\Tesis;
use App\Services\LaragonService;
use App\Services\DockerProjectService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProyectoController extends Controller
{
    protected $laragonService;
    protected $dockerProjectService;
    
    public function __construct(LaragonService $laragonService, ?DockerProjectService $dockerProjectService = null)
    {
        $this->laragonService = $laragonService;
        $this->dockerProjectService = $dockerProjectService;
    }
      /**
     * Mostrar el listado de proyectos
     * 
     * @return \Illuminate\View\View
     */    public function index()
    {
        $user = auth()->user();
        
        // Filtrar proyectos según el rol del usuario
        $proyectos = Tesis::query();
        
        if ($user->hasRole('alumno') && $user->alumno) {
            // Si es alumno, solo mostrar sus propios proyectos/tesis
            $proyectos->where('alumno_id', $user->alumno->id);
        } elseif ($user->hasRole('tutor') && $user->tutor) {
            // Si es tutor, solo mostrar las tesis donde es tutor
            $proyectos->where('tutor_id', $user->tutor->id);
        }
        // Admin y coordinador ven todos los proyectos (sin filtro adicional)
        
        // Si el usuario no tiene permiso para ver proyectos no visibles, filtramos
        // Comentado temporalmente debido a problemas con extensiones PHP
        // if (!auth()->user()->can('ver proyectos no visibles')) {
            // $proyectos->where('is_visible', true);
        // }
        
        $proyectos = $proyectos->with(['alumno', 'tutor'])->get();
        
        return view('proyectos.index', compact('proyectos'));
    }
    
    /**
     * Mostrar el formulario para crear un nuevo proyecto
     * 
     * @return \Illuminate\View\View
     */
    public function create()
    {
        $user = auth()->user();
        
        // Filtrar alumnos y tutores según el rol del usuario
        if ($user->hasRole('alumno') && $user->alumno) {
            // Si es alumno, solo puede crear proyectos para sí mismo
            $alumnos = collect([$user->alumno]);
            $tutores = \App\Models\Tutor::where('activo', true)->get();
        } elseif ($user->hasRole('tutor') && $user->tutor) {
            // Si es tutor, puede ver alumnos donde es tutor asignado
            $alumnos = \App\Models\Alumno::whereHas('tesis', function($query) use ($user) {
                $query->where('tutor_id', $user->tutor->id);
            })->where('estado', 'activo')->get();
            $tutores = collect([$user->tutor]);
        } else {
            // Admin y coordinador ven todos
            $alumnos = \App\Models\Alumno::where('estado', 'activo')->get();
            $tutores = \App\Models\Tutor::where('activo', true)->get();
        }
        
        return view('proyectos.create', compact('alumnos', 'tutores'));
    }
    
    /**
     * Guardar un nuevo proyecto en la base de datos
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */    public function store(Request $request)
    {
        // Validar los datos del formulario
        $validatedData = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'required|string',            'fecha_inicio' => 'required|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'alumno_id' => 'required|exists:alumnos,id',
            'tutor_id' => 'required|exists:tutores,id',
            'estado' => 'required|in:pendiente,en_progreso,completado,rechazado',
            'observaciones' => 'nullable|string',
            'is_visible' => 'sometimes|boolean',
            'github_repo' => 'nullable|url',
        ]);
        
        // Establecer visibilidad predeterminada si no se proporciona
        if (!isset($validatedData['is_visible'])) {
            $validatedData['is_visible'] = true;
        }
        
        // Crear el nuevo proyecto
        $tesis = Tesis::create($validatedData);
          // Si se proporcionó un repositorio GitHub, redireccionar a la configuración
        if (!empty($request->github_repo)) {
            return redirect()->route('proyectos.github-config', $tesis->id)
                ->with('success', 'Proyecto creado exitosamente. Por favor confirme la URL del repositorio GitHub para continuar con la configuración.');
        }
        
        return redirect()->route('proyectos.index')
            ->with('success', 'Proyecto creado exitosamente. Para continuar, configure el repositorio GitHub haciendo clic en el botón "Configurar GitHub" junto al proyecto.');
    }
    
    /**
     * Mostrar la página para configurar un repositorio GitHub
     */
    public function showGitHubConfig($id)
    {
        $tesis = Tesis::findOrFail($id);
        return view('proyectos.github-config', compact('tesis'));
    }
    
    /**
     * Guardar la configuración del repositorio GitHub
     */    public function saveGitHubConfig(Request $request, $id)
    {
        $request->validate([
            'github_repo' => 'required|url',
        ]);
        
        // Verificar el formato de la URL de GitHub
        $githubUrl = $request->github_repo;
        if (!preg_match('/^https:\/\/github\.com\/[^\/]+\/[^\/]+(.git)?$/i', $githubUrl)) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['github_repo' => 'La URL debe ser de un repositorio GitHub válido (https://github.com/username/repo)']);
        }
        
        // Normalizar la URL (quitar .git si está presente)
        $normalizedUrl = preg_replace('/\.git$/i', '', $githubUrl);
        
        $tesis = Tesis::findOrFail($id);
        $tesis->github_repo = $normalizedUrl;
        $tesis->save();
        
        return redirect()->route('proyectos.setup', $tesis->id)
            ->with('success', 'Repositorio GitHub configurado exitosamente. Ahora puede continuar con la configuración del proyecto.');
    }
    
    /**
     * Mostrar la página para configurar el proyecto
     */
    public function showSetup($id)
    {
        $tesis = Tesis::findOrFail($id);
        
        if (empty($tesis->github_repo)) {
            return redirect()->route('proyectos.github-config', $tesis->id)
                ->with('error', 'Primero debe configurar un repositorio GitHub');
        }
        
        return view('proyectos.setup', compact('tesis'));
    }
    
    /**
     * Clonar el repositorio y detectar el tipo de proyecto
     */
    public function cloneAndDetect($id)
    {
        $tesis = Tesis::findOrFail($id);
        
        if (empty($tesis->github_repo)) {
            return redirect()->route('proyectos.github-config', $tesis->id)
                ->with('error', 'Primero debe configurar un repositorio GitHub');
        }
        
        try {
            // Clonar el repositorio
            $repoPath = $this->laragonService->cloneRepository($tesis->github_repo);
            
            if (!$repoPath) {
                return redirect()->back()
                    ->with('error', 'No se pudo clonar el repositorio. Verifique la URL y los permisos.');
            }
            
            // Detectar el tipo de proyecto
            $projectType = $this->laragonService->detectProjectType($repoPath);
            
            // Actualizar la tesis con la información del proyecto
            $tesis->project_repo_path = $repoPath;
            $tesis->project_type = $projectType;
            $tesis->save();
            
            return redirect()->route('proyectos.deploy', $tesis->id)
                ->with('success', 'Repositorio clonado exitosamente. Tipo de proyecto detectado: ' . ucfirst($projectType));
        } catch (\Exception $e) {
            Log::error('Error cloning repository: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Error al clonar el repositorio: ' . $e->getMessage());
        }
    }
    
    /**
     * Mostrar la página para desplegar el proyecto
     */
    public function showDeploy($id)
    {
        $tesis = Tesis::findOrFail($id);
        
        if (empty($tesis->project_repo_path)) {
            return redirect()->route('proyectos.setup', $tesis->id)
                ->with('error', 'Primero debe clonar el repositorio');
        }
        
        return view('proyectos.deploy', compact('tesis'));
    }
    
    /**
     * Desplegar el proyecto en un contenedor (desde formulario web)
     */
    public function deploy(Request $request, $id)
    {
        $tesis = Tesis::findOrFail($id);
        $request->validate([
            'backup_file' => 'nullable|file|max:102400',
            'existing_backup_id' => 'nullable|integer',
            'backup_data_only' => 'nullable|boolean',
            'env_file' => 'nullable|file|max:128',
            'run_migrations' => 'nullable|boolean',
            'run_seeders' => 'nullable|boolean',
            'deployment_target' => 'nullable|in:docker,laragon',
        ]);
        $temporaryPath = null;
        try {
            $previousConfig = $tesis->project_config ?? [];
            $options = ['deployment_target' => $request->input('deployment_target', 'docker')];
            if ($request->hasFile('env_file')) {
                $options['env_content'] = file_get_contents($request->file('env_file')->getRealPath());
                app(\App\Services\ProjectDatabaseService::class)->forEnvironment($options['env_content']);
            }
            if ($request->has('run_migrations')) {
                $options['run_migrations'] = $request->boolean('run_migrations');
            }
            if (!$tesis->project_repo_path) {
                throw new \RuntimeException('Primero debe clonar el repositorio.');
            }
            if ($request->filled('existing_backup_id')) {
                $backup = $tesis->backups()->findOrFail($request->integer('existing_backup_id'));
            } elseif ($request->hasFile('backup_file')) {
                $file = $request->file('backup_file');
                $extension = strtolower($file->getClientOriginalExtension());
                if (!in_array($extension, ['sql', 'zip', 'tar', 'gz'])) {
                    throw new \RuntimeException('Formato no válido. Use SQL, ZIP, TAR o GZ.');
                }
                $directory = storage_path('app/temp-backups');
                \Illuminate\Support\Facades\File::ensureDirectoryExists($directory);
                $name = bin2hex(random_bytes(16)).'.'.$extension;
                $file->move($directory, $name);
                $temporaryPath = $directory.'/'.$name;
                $backup = new \App\Models\ProjectBackup([
                    'tesis_id' => $tesis->id,
                    'file_path' => 'temp-backups/'.$name,
                    'backup_type' => 'database',
                    'is_temporary' => true,
                ]);
            } else {
                $backup = null;
            }
            if (!$this->executeProjectDeployment($id, $backup, $request->boolean('backup_data_only'), $options)) {
                $tesis->refresh();
                throw new \RuntimeException($tesis->deployment_error ?: 'No se pudo preparar el proyecto.');
            }
            // Deployment loaded a separate model instance; use its saved destination.
            $tesis->refresh();
            if ($backup && !app(\App\Services\ProjectBackupService::class)->restoreBackupToContainer($tesis, $backup)) {
                throw new \RuntimeException('No se pudo restaurar la base de datos. Revise los logs.');
            }
            $projectConfig = $tesis->project_config;
            if (($previousConfig['deployment_type'] ?? null) === ($projectConfig['deployment_type'] ?? null)) {
                foreach (['seeders_executed', 'test_credentials'] as $preservedKey) {
                    if (array_key_exists($preservedKey, $previousConfig)) {
                        $projectConfig[$preservedKey] = $previousConfig[$preservedKey];
                    }
                }
                $tesis->project_config = $projectConfig;
            }
            $hasSeeder = (bool) ($projectConfig['capabilities']['seeder_found'] ?? false);
            $seedChoice = $request->has('run_seeders') ? $request->boolean('run_seeders') : null;
            $targetChanged = isset($previousConfig['deployment_type'])
                && $previousConfig['deployment_type'] !== ($projectConfig['deployment_type'] ?? null);
            $alreadySeeded = !$targetChanged && (
                (bool) ($previousConfig['seeders_executed'] ?? false) || !empty($previousConfig['test_credentials'])
            );
            $seedingPending = (bool) ($previousConfig['seeders_pending'] ?? false);
            $databaseWasEmpty = (bool) ($projectConfig['capabilities']['database_was_empty'] ?? false);
            $runSeeders = $seedChoice ?? ($hasSeeder && !$backup && !$alreadySeeded && ($databaseWasEmpty || $seedingPending));
            if ($runSeeders) {
                $databaseService = app(\App\Services\ProjectDatabaseService::class);
                $projectConfig['seeders_pending'] = true;
                $tesis->project_config = $projectConfig;
                $tesis->save();
                if (($projectConfig['deployment_type'] ?? 'laragon') === 'docker') {
                    $this->dockerService()->seed($projectConfig, $seedingPending && !$backup);
                } else {
                    if ($seedingPending && !$backup) {
                        $databaseService->runArtisan($projectConfig['project_path'], [
                            'migrate:fresh', '--force', '--no-interaction',
                        ]);
                    }
                    $databaseService->seed($projectConfig['project_path']);
                }
                $projectConfig['seeders_executed'] = true;
                unset($projectConfig['seeders_pending']);
                $projectConfig['test_credentials'] = $databaseService->testCredentials($projectConfig['project_path']);
                $tesis->project_config = $projectConfig;
            }
            $tesis->update([
                'container_status' => 'running', 'deployment_error' => null,
                'backup_restored' => $backup ? true : $tesis->backup_restored,
                'backup_restored_at' => $backup ? now() : $tesis->backup_restored_at,
            ]);
            return redirect()->route('proyectos.show', $id)
                ->with('success', 'Proyecto preparado correctamente: entorno, base de datos, migraciones y datos iniciales verificados.');
        } catch (\Throwable $e) {
            $tesis->update([
                'container_status' => 'failed', 'deployment_error' => $e->getMessage(),
                'backup_restored' => false, 'backup_restored_at' => null,
            ]);
            Log::error('Error preparando la base del proyecto', ['tesis_id' => $id, 'error' => $e->getMessage()]);
            return redirect()->back()->with('error', $e->getMessage());
        } finally {
            if ($temporaryPath && is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }
    private function executeProjectDeployment($id, $selectedBackup = null, bool $dataOnly = false, array $options = [])
    {
        $tesis = Tesis::findOrFail($id);
        
        try {
            // Establecer estado de despliegue en progreso
            $tesis->container_status = 'deploying';
            $tesis->save();
            
            // Construir y ejecutar el proyecto en Laragon
            Log::info("CONTROLLER DEBUG: executeProjectDeployment llamando a deployProject", [
                'tesis_id' => $tesis->id,
                'project_repo_path' => $tesis->project_repo_path,
                'project_type' => $tesis->project_type
            ]);
            
            $target = $options['deployment_target'] ?? 'docker';
            unset($options['deployment_target']);
            $service = $target === 'docker' ? $this->dockerService() : $this->laragonService;
            $result = $options
                ? $service->deployProject($tesis, $selectedBackup !== null && !$dataOnly, $options)
                : $service->deployProject($tesis, $selectedBackup !== null && !$dataOnly);
            
            if (!$result) {
                $tesis->container_status = 'failed';
                $tesis->save();
                
                Log::error('Deployment failed: No result returned from deployProject');
                return false;
            }
            
            // Actualizar la tesis con la información del proyecto
            $tesis->container_id = $result['container_id'];
            $tesis->container_status = $selectedBackup ? 'deploying' : $result['container_status'];
            $tesis->project_url = $result['project_url'];
            $tesis->project_config = $result['project_config'];
            $tesis->deployment_error = null; // Limpiar errores anteriores
            $tesis->last_deployed = now();
            
            $tesis->env_configured = true;
            if ($selectedBackup) {
                $tesis->backup_restored = false;
                $tesis->backup_restored_at = null;
            }
            $tesis->save();
            
            // Registrar información sobre el despliegue exitoso
            Log::info('Proyecto desplegado exitosamente', [
                'tesis_id' => $tesis->id,
                'container_id' => $result['container_id'],
                'project_url' => $result['project_url'],
                'external_port' => $result['project_config']['external_port']
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Error deploying project: ' . $e->getMessage());
            
            // Guardar información detallada del error
            $tesis->container_status = 'failed';
            $tesis->deployment_error = $e->getMessage();
            $tesis->save();
            
            return false;
        }
    }

    private function dockerService(): DockerProjectService
    {
        return $this->dockerProjectService ??= app(DockerProjectService::class);
    }

    /**
     * Mostrar el proyecto desplegado
     */
    public function show($id)
    {
        $tesis = Tesis::with(['alumno', 'tutor'])->findOrFail($id);
        
        // Verificar acceso según el rol del usuario
        if (!$this->checkProjectAccess($tesis)) {
            abort(403, 'No tienes acceso a este proyecto.');
        }
        
        if (empty($tesis->container_id)) {
            return redirect()->route('proyectos.deploy', $tesis->id)
                ->with('error', 'Primero debe desplegar el proyecto');
        }
        
        // Verificar el estado del proyecto
        $status = ($tesis->project_config['deployment_type'] ?? 'laragon') === 'docker'
            ? $this->dockerService()->status($tesis->container_id)
            : $this->laragonService->getProjectStatus($tesis->container_id);
        
        if (!$tesis->deployment_error && $tesis->container_status !== 'deploying' && $status && $status != $tesis->container_status) {
            $tesis->container_status = $status;
            $tesis->save();
        }
        
        return view('proyectos.show', compact('tesis'));
    }
    
    /**
     * Detener el proyecto
     */
    public function stop($id)
    {
        $tesis = Tesis::findOrFail($id);
        
        if (empty($tesis->container_id)) {
            return redirect()->back()
                ->with('error', 'El proyecto no está desplegado');
        }
        
        try {
            if (($tesis->project_config['deployment_type'] ?? 'laragon') === 'docker') {
                $this->dockerService()->stop($tesis->project_config);
                $stopped = true;
            } else {
                $stopped = $this->laragonService->stopProject($tesis->container_id);
            }
            
            if (!$stopped) {
                return redirect()->back()
                    ->with('error', 'No se pudo detener el proyecto. Verifique los logs para más detalles.');
            }
            
            $tesis->container_status = 'stopped';
            $tesis->save();
            
            return redirect()->back()
                ->with('success', 'Proyecto detenido exitosamente');
        } catch (\Exception $e) {
            Log::error('Error stopping project: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Error al detener el proyecto: ' . $e->getMessage());
        }
    }
    
    /**
     * Reiniciar el proyecto
     */
    public function restart($id)
    {
        $tesis = Tesis::findOrFail($id);
        
        if (empty($tesis->container_id)) {
            return redirect()->route('proyectos.deploy', $tesis->id)
                ->with('error', 'El proyecto no está desplegado');
        }
        
        try {

            
            // Volver a desplegar el proyecto
            return $this->deployWithoutBackup($id);
        } catch (\Exception $e) {
            Log::error('Error restarting project: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Error al reiniciar el proyecto: ' . $e->getMessage());
        }
    }
    
    /**
     * Mostrar el panel de monitoreo de proyectos
     */
    public function monitor(Request $request)
    {
        $filter = $request->input('filter', 'all');
        
        $query = Tesis::with(['alumno', 'tutor'])
            ->whereNotNull('github_repo');
            
        if ($filter === 'running') {
            $query->where('container_status', 'running');
        } elseif ($filter === 'stopped') {
            $query->where('container_status', 'stopped');
        }
        
        $proyectos = $query->paginate(10);
        
        // Estadísticas
        $totalProyectos = Tesis::whereNotNull('github_repo')->count();
        $proyectosActivos = Tesis::where('container_status', 'running')->count();
        $proyectosDetenidos = Tesis::where('container_status', 'stopped')->count();
        
        // Uso de recursos (simulado)
        $resourceUsage = [
            'cpu' => rand(10, 60),
            'memory' => rand(20, 70),
            'disk' => rand(5, 40),
            'network' => rand(5, 30)
        ];
        
        // Distribución de tipos de proyectos
        $projectTypes = Tesis::whereNotNull('project_type')
            ->select('project_type')
            ->selectRaw('count(*) as count')
            ->groupBy('project_type')
            ->pluck('count', 'project_type')
            ->toArray();
            
        $projectTypeLabels = array_map('ucfirst', array_keys($projectTypes));
        $projectTypeCounts = array_values($projectTypes);
        
        return view('proyectos.monitor', compact(
            'proyectos', 
            'totalProyectos', 
            'proyectosActivos', 
            'proyectosDetenidos',
            'resourceUsage',
            'projectTypeLabels',
            'projectTypeCounts'
        ));
    }
    
    /**
     * Mostrar los logs del contenedor
     */
    public function logs($id)
    {
        $tesis = Tesis::findOrFail($id);
        
        if (empty($tesis->container_id)) {
            return redirect()->route('proyectos.deploy', $tesis->id)
                ->with('error', 'El proyecto no está desplegado');
        }
        
        try {
            // Obtener logs del contenedor
            $cmd = "docker logs --tail=100 {$tesis->container_id} 2>&1";
            exec($cmd, $logs, $returnVar);
            
            if ($returnVar !== 0) {
                $logs = ['Error al obtener logs del contenedor'];
            }
            
            return view('proyectos.logs', compact('tesis', 'logs'));
        } catch (\Exception $e) {
            Log::error('Error getting container logs: ' . $e->getMessage());
            
            return redirect()->back()
                ->with('error', 'Error al obtener los logs del proyecto: ' . $e->getMessage());        }
    }
      /**
     * Cambiar la visibilidad de un proyecto
     * 
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function toggleVisibility($id)
    {
        // Verificar permiso
        // Comentado temporalmente debido a problemas con extensiones PHP
        // if (!auth()->user()->can('configurar proyectos')) {
        //     return redirect()->route('proyectos.index')
        //         ->with('error', 'No tiene permiso para realizar esta acción');
        // }
        
        $tesis = Tesis::findOrFail($id);
        $tesis->is_visible = !$tesis->is_visible;
        $tesis->save();
        
        $visibilityStatus = $tesis->is_visible ? 'visible' : 'no visible';
        
        return redirect()->route('proyectos.index')
            ->with('success', "El proyecto ahora es {$visibilityStatus}");
    }
    
    /**
     * Mostrar una página de diagnóstico de Docker
     * 
     * @return \Illuminate\View\View
     */    /**
     * Mostrar la guía de instalación de Docker
     *
     * @return \Illuminate\View\View
     */
    public function dockerInstallGuide()
    {
        return view('proyectos.docker-install-guide');
    }
    
    /**
     * Mostrar la página de instalación automática de Docker
     *
     * @return \Illuminate\View\View
     */
    public function dockerAutoInstall()
    {
        return view('proyectos.docker-auto-install');
    }
    
    /**
     * Mostrar página de diagnóstico de Docker
     *
     * @return \Illuminate\View\View
     */
    public function dockerTroubleshoot()
    {
        // Verificar si Docker está instalado
        exec('docker --version 2>&1', $dockerOutput, $dockerReturnVar);
        $dockerInstalled = $dockerReturnVar === 0;
        $dockerVersion = $dockerInstalled ? $dockerOutput[0] : null;
        
        // Verificar si Docker está en ejecución
        $dockerRunning = false;
        if ($dockerInstalled) {
            exec('docker info 2>&1', $infoOutput, $infoReturnVar);
            $dockerRunning = $infoReturnVar === 0;
        }
        
        // Verificar Docker Compose (ambos formatos)
        exec('docker compose version 2>&1', $composeOutput, $composeReturnVar);
        if ($composeReturnVar !== 0) {
            exec('docker-compose --version 2>&1', $composeOutput, $composeReturnVar);
        }
        
        $composeAvailable = $composeReturnVar === 0;
        $composeVersion = $composeAvailable ? $composeOutput[0] : null;
        
        return view('proyectos.docker-troubleshoot', compact(
            'dockerInstalled',
            'dockerRunning',
            'dockerVersion',
            'composeAvailable',
            'composeVersion'
        ));
    }
      /**
     * Verificar la validez de una URL de repositorio GitHub
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkGitHubUrl(Request $request)
    {
        $url = $request->input('url');
        
        if (empty($url)) {
            return response()->json([
                'valid' => false,
                'message' => 'La URL no puede estar vacía.'
            ]);
        }
        
        // Verificar el formato de la URL
        if (!preg_match('/^https:\/\/github\.com\/[^\/]+\/[^\/]+(.git)?$/i', $url)) {
            return response()->json([
                'valid' => false,
                'message' => 'El formato de la URL no es válido. Debe ser https://github.com/username/repo'
            ]);
        }
        
        // Normalizar la URL para asegurar que sea la URL base del repositorio
        $url = preg_replace('/\.git$/i', '', $url);
        
        // Verificar si el repositorio existe usando la API de GitHub en lugar de HEAD request
        try {
            // Extraer el usuario y el nombre del repositorio de la URL
            preg_match('/github\.com\/([^\/]+)\/([^\/]+)/i', $url, $matches);
            
            if (count($matches) < 3) {
                return response()->json([
                    'valid' => false,
                    'message' => 'No se pudo extraer la información del repositorio de la URL.'
                ]);
            }
            
            $username = $matches[1];
            $repo = $matches[2];
            
            // Utilizar la API de GitHub para verificar el repositorio
            $client = new \GuzzleHttp\Client([
                'http_errors' => false // Evitar excepciones por códigos de error HTTP
            ]);
            
            $apiUrl = "https://api.github.com/repos/{$username}/{$repo}";
            $response = $client->get($apiUrl);
            
            if ($response->getStatusCode() === 200) {
                $repoData = json_decode($response->getBody(), true);
                
                return response()->json([
                    'valid' => true,
                    'message' => 'Repositorio válido: ' . $repoData['full_name'],
                    'repoDetails' => [
                        'name' => $repoData['name'],
                        'fullName' => $repoData['full_name'],
                        'description' => $repoData['description'],
                        'isPrivate' => $repoData['private']
                    ]
                ]);
            } else if ($response->getStatusCode() === 404) {
                return response()->json([
                    'valid' => false,
                    'message' => 'El repositorio no existe o es privado. Verifica que hayas escrito correctamente el nombre o que tengas acceso si es privado.'
                ]);
            } else {
                return response()->json([
                    'valid' => false,
                    'message' => 'Error al verificar el repositorio: ' . $response->getStatusCode()
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'valid' => false,
                'message' => 'Error al verificar el repositorio: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Proxy para acceder a los proyectos desplegados
     *
     * @param int $id ID del proyecto/tesis
     * @param string $path Path opcional para la URL interna del proyecto
     * @return \Illuminate\Http\Response
     */
    public function proxy($id, $path = '')
    {
        $tesis = Tesis::findOrFail($id);
        
        // Verificar que el proyecto tenga un contenedor en ejecución
        if (empty($tesis->container_id) || $tesis->container_status !== 'running') {
            return redirect()->route('proyectos.deploy', $id)
                ->with('error', 'El proyecto no está en ejecución actualmente');
        }
        
        // NUEVO: Reiniciar Laragon antes de acceder al proyecto
        try {
            Log::info("Reiniciando Laragon para el proyecto: " . $tesis->container_id);
            $this->laragonService->restartLaragonForProject($tesis->container_id);
        } catch (\Exception $e) {
            Log::warning("Error reiniciando Laragon: " . $e->getMessage());
        }
        
        // Para proyectos de Laragon, verificar disponibilidad del proyecto
        if (!empty($tesis->project_url)) {
            $projectUrl = $tesis->project_url;
            $projectName = $tesis->container_id;
            
            // Verificar si el proyecto está disponible en el dominio .test
            $isHostsConfigured = $this->checkProjectAvailability($projectUrl);
            
            if (!$isHostsConfigured) {
                // Si no está configurado en hosts, usar IP local como fallback
                $fallbackUrl = "http://127.0.0.1/{$projectName}/";
                if (!empty($path)) {
                    $fallbackUrl = rtrim($fallbackUrl, '/') . '/' . $path;
                }
                Log::info("Usando fallback URL para {$projectName}: {$fallbackUrl}");
                return redirect()->away($fallbackUrl);
            }
            
            // Si está configurado, usar la URL normal
            $finalUrl = $projectUrl;
            if (!empty($path)) {
                $finalUrl = rtrim($finalUrl, '/') . '/' . $path;
            }
            return redirect()->away($finalUrl);
        }
        
        // Fallback: La redirección real se maneja por el middleware ProjectProxyMiddleware
        // Este método solo se llama para resolver la ruta y validar el proyecto
        
        return response()->make('Redireccionando...', 200);
    }
    
    /**
     * Verificar si un proyecto está disponible en su URL configurada
     */
    private function checkProjectAvailability(string $url): bool
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'timeout' => 3,
                    'method' => 'GET'
                ]
            ]);
            
            $headers = @get_headers($url, 1, $context);
            return $headers !== false && strpos($headers[0], '200') !== false;
            
        } catch (\Exception $e) {
            Log::info("URL no disponible: {$url} - " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verificar si el usuario tiene acceso a la tesis/proyecto
     */
    private function checkProjectAccess($tesis, $user = null)
    {
        if (!$user) {
            $user = auth()->user();
        }
        
        // Admin y coordinador tienen acceso a todo
        if ($user->hasRole(['administrador', 'coordinador'])) {
            return true;
        }
        
        // Alumno solo puede acceder a sus propias tesis
        if ($user->hasRole('alumno') && $user->alumno) {
            return $user->alumno->id == $tesis->alumno_id;
        }
        
        // Tutor solo puede acceder a tesis donde es tutor
        if ($user->hasRole('tutor') && $user->tutor) {
            return $user->tutor->id == $tesis->tutor_id;
        }
        
        return false;
    }
    
    /**
     * Desplegar proyecto sin backup (para reintentos)
     */
    public function deployWithoutBackup($id)
    {
        Log::info("=== DEPLOY WITHOUT BACKUP ===", ['tesis_id' => $id]);
        
        try {
            $tesis = Tesis::findOrFail($id);
            $target = $tesis->project_config['deployment_type'] ?? 'docker';
            $deployResult = $this->executeProjectDeployment($id, null, false, ['deployment_target' => $target]);
            
            // Verificar si la petición es AJAX
            if (request()->ajax()) {
                if ($deployResult) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Proyecto desplegado exitosamente con '.ucfirst($target).'.'
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => (Tesis::findOrFail($id)->deployment_error ?: 'No se pudo desplegar el proyecto.')
                    ], 500);
                }
            }
            
            // Respuesta tradicional para navegadores
            if ($deployResult) {
                return redirect()->back()
                    ->with('success', 'Proyecto desplegado exitosamente con '.ucfirst($target).'.');
            } else {
                return redirect()->back()
                    ->with('error', (Tesis::findOrFail($id)->deployment_error ?: 'No se pudo desplegar el proyecto.'));
            }
        } catch (\Exception $e) {
            Log::error('Error en deployWithoutBackup: ' . $e->getMessage());
            
            // Verificar si la petición es AJAX
            if (request()->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al desplegar el proyecto: ' . $e->getMessage()
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Error al desplegar el proyecto: ' . $e->getMessage());
        }
    }
}
