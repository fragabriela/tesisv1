<?php

namespace App\Http\Middleware;

use App\Models\Tesis;
use App\Services\DockerService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ProjectProxyMiddleware
{
    protected $dockerService;
    
    public function __construct(DockerService $dockerService)
    {
        $this->dockerService = $dockerService;
    }
    
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si la ruta actual es para un proyecto
        if (strpos($request->path(), 'projects/') === 0) {
            // Extraer el ID del proyecto de la URL
            $parts = explode('/', $request->path());
            if (count($parts) >= 2 && is_numeric($parts[1])) {
                $tesisId = $parts[1];
                
                try {
                    // Encontrar la tesis
                    $tesis = Tesis::find($tesisId);
                    
                    if ($tesis && !empty($tesis->container_id) && $tesis->container_status === 'running' && 
                        !empty($tesis->project_config) && isset($tesis->project_config['external_port'])) {
                        
                        // Verificar si el contenedor está en ejecución
                        $containerStatus = $this->dockerService->getContainerStatus($tesis->container_id);
                        if ($containerStatus !== 'running') {
                            $tesis->container_status = $containerStatus;
                            $tesis->save();
                            
                            return response()->view('errors.container', [
                                'tesis' => $tesis,
                                'error' => 'El contenedor no está en ejecución'
                            ], 503);
                        }
                        
                        // Construir la URL para redirigir al contenedor
                        $port = $tesis->project_config['external_port'];
                        $host = $request->getHost();
                        
                        // Extraer la ruta adicional si existe (después de /projects/{id}/)
                        $additionalPath = '';
                        if (count($parts) > 2) {
                            $additionalPath = '/' . implode('/', array_slice($parts, 2));
                        }
                          // Construir la URL completa usando el protocolo correcto
                        $protocol = $request->secure() ? 'https://' : 'http://';
                        $targetUrl = $protocol . $host . ':' . $port . $additionalPath;
                        
                        // Si hay parámetros de consulta, agregarlos
                        if ($request->getQueryString()) {
                            $targetUrl .= '?' . $request->getQueryString();
                        }
                        
                        // Registrar la URL a la que se está redirigiendo
                        Log::info('ProjectProxyMiddleware: Redirecting to ' . $targetUrl);
                        
                        // Redirigir al puerto del contenedor
                        return redirect()->away($targetUrl);
                    }
                } catch (\Exception $e) {
                    Log::error('Error en ProjectProxyMiddleware: ' . $e->getMessage());
                }
                
                // Si no se encuentra la tesis o el contenedor no está en ejecución
                return response()->view('errors.container', [
                    'tesis' => $tesis ?? null,
                    'error' => 'Proyecto no disponible'
                ], 404);
            }
        }
        
        return $next($request);
    }
}
