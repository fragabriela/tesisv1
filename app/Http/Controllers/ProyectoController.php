<?php

namespace App\Http\Controllers;

use App\Models\Tesis;
use App\Services\DockerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProyectoController extends Controller
{
    protected $dockerService;
    
    public function __construct(DockerService $dockerService)
    {
        $this->dockerService = $dockerService;
    }
      /**
     * Mostrar el listado de proyectos
     * 
     * @return \Illuminate\View\View
     */    public function index()
    {
        // Obtener todas las tesis, incluyendo los recién creados sin GitHub o contenedor
        $proyectos = Tesis::query();
        
        // Si el usuario no tiene permiso para ver proyectos no visibles, filtramos
        if (!auth()->user()->can('ver proyectos no visibles')) {
            $proyectos->where('is_visible', true);
        }
        
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
        // Obtener alumnos y tutores para el formulario
        $alumnos = \App\Models\Alumno::where('estado', 'activo')->get();
        $tutores = \App\Models\Tutor::where('activo', true)->get();
        
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
            $repoPath = $this->dockerService->cloneRepository($tesis->github_repo);
            
            if (!$repoPath) {
                return redirect()->back()
                    ->with('error', 'No se pudo clonar el repositorio. Verifique la URL y los permisos.');
            }
            
            // Detectar el tipo de proyecto
            $projectType = $this->dockerService->detectProjectType($repoPath);
            
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
     * Desplegar el proyecto en un contenedor
     */    public function deploy($id)
    {
        $tesis = Tesis::findOrFail($id);
        
        if (empty($tesis->project_repo_path)) {
            return redirect()->route('proyectos.setup', $tesis->id)
                ->with('error', 'Primero debe clonar el repositorio');
        }
        
        try {
            // Establecer estado de despliegue en progreso
            $tesis->container_status = 'deploying';
            $tesis->save();
            
            // Si ya existe un contenedor, detenerlo primero
            if (!empty($tesis->container_id)) {
                $this->dockerService->stopContainer($tesis->container_id);
            }
            
            // Construir y ejecutar el proyecto
            $result = $this->dockerService->buildAndRunProject($tesis);
            
            if (!$result) {
                $tesis->container_status = 'failed';
                $tesis->save();
                
                Log::error('Deployment failed: No result returned from buildAndRunProject');
                
                return redirect()->back()
                    ->with('error', 'No se pudo desplegar el proyecto. El proceso de construcción o ejecución falló. Verifique los logs para más detalles.');
            }
            
            // Actualizar la tesis con la información del contenedor
            $tesis->container_id = $result['container_id'];
            $tesis->container_status = $result['container_status'];
            $tesis->project_url = $result['project_url'];
            $tesis->project_config = $result['project_config'];
            $tesis->deployment_error = null; // Limpiar errores anteriores
            $tesis->last_deployed = now();
            $tesis->save();
            
            // Registrar información sobre el despliegue exitoso
            Log::info('Proyecto desplegado exitosamente', [
                'tesis_id' => $tesis->id,
                'container_id' => $result['container_id'],
                'project_url' => $result['project_url'],
                'external_port' => $result['project_config']['external_port']
            ]);
            
            return redirect()->route('proyectos.show', $tesis->id)
                ->with('success', 'Proyecto desplegado exitosamente. Utilice el botón "Abrir Proyecto" para acceder a la aplicación.');
        } catch (\Exception $e) {
            Log::error('Error deploying project: ' . $e->getMessage());
            
            // Guardar información detallada del error
            $tesis->container_status = 'failed';
            $tesis->deployment_error = $e->getMessage();
            $tesis->save();
            
            return redirect()->back()
                ->with('error', 'Error al desplegar el proyecto: ' . $e->getMessage());
        }
    }
    
    /**
     * Mostrar el proyecto desplegado
     */
    public function show($id)
    {
        $tesis = Tesis::with(['alumno', 'tutor'])->findOrFail($id);
        
        if (empty($tesis->container_id)) {
            return redirect()->route('proyectos.deploy', $tesis->id)
                ->with('error', 'Primero debe desplegar el proyecto');
        }
        
        // Verificar el estado del contenedor
        $status = $this->dockerService->getContainerStatus($tesis->container_id);
        
        if ($status && $status != $tesis->container_status) {
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
            $stopped = $this->dockerService->stopContainer($tesis->container_id);
            
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
            // Detener el contenedor actual
            $this->dockerService->stopContainer($tesis->container_id);
            
            // Volver a desplegar el proyecto
            return $this->deploy($id);
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
        if (!auth()->user()->can('configurar proyectos')) {
            return redirect()->route('proyectos.index')
                ->with('error', 'No tiene permiso para realizar esta acción');
        }
        
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
        
        // La redirección real se maneja por el middleware ProjectProxyMiddleware
        // Este método solo se llama para resolver la ruta y validar el proyecto
        
        return response()->make('Redireccionando...', 200);
    }
}
