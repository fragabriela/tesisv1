<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tesis;
use App\Services\DockerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class TesisApiController extends Controller
{
    protected $dockerService;
    
    public function __construct(DockerService $dockerService)
    {
        $this->dockerService = $dockerService;
    }
    
    /**
     * Obtener listado de tesis
     */
    public function index(Request $request)
    {
        try {
            // Validar permisos
            if (!Auth::user()->can('ver tesis')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para ver tesis'
                ], 403);
            }
            
            $perPage = $request->input('per_page', 10);
            $query = Tesis::with(['alumno', 'tutor']);
            
            // Filtros
            if ($request->has('estado')) {
                $query->where('estado', $request->estado);
            }
            
            if ($request->has('alumno_id')) {
                $query->where('alumno_id', $request->alumno_id);
            }
            
            if ($request->has('tutor_id')) {
                $query->where('tutor_id', $request->tutor_id);
            }
            
            if ($request->has('project_status')) {
                if ($request->project_status === 'running') {
                    $query->where('container_status', 'running');
                } elseif ($request->project_status === 'stopped') {
                    $query->where('container_status', 'stopped');
                } elseif ($request->project_status === 'configured') {
                    $query->whereNotNull('github_repo');
                }
            }
            
            // Ordenar
            $orderBy = $request->input('order_by', 'updated_at');
            $orderDir = $request->input('order_dir', 'desc');
            $query->orderBy($orderBy, $orderDir);
            
            $tesis = $query->paginate($perPage);
            
            return response()->json([
                'success' => true,
                'data' => $tesis
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las tesis: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Obtener detalles de una tesis
     */
    public function show($id)
    {
        try {
            // Validar permisos
            if (!Auth::user()->can('ver tesis')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para ver tesis'
                ], 403);
            }
            
            $tesis = Tesis::with(['alumno', 'tutor'])->findOrFail($id);
            
            // Verificar el estado del contenedor si existe
            if (!empty($tesis->container_id)) {
                $containerStatus = $this->dockerService->getContainerStatus($tesis->container_id);
                if ($containerStatus && $containerStatus != $tesis->container_status) {
                    $tesis->container_status = $containerStatus;
                    $tesis->save();
                }
            }
            
            return response()->json([
                'success' => true,
                'data' => $tesis
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la tesis: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Gestionar el proyecto de una tesis
     */
    public function manageProject(Request $request, $id)
    {
        try {
            // Validar permisos
            if (!Auth::user()->can('editar tesis')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tiene permisos para gestionar proyectos'
                ], 403);
            }
            
            $tesis = Tesis::findOrFail($id);
            $action = $request->input('action');
            
            switch ($action) {
                case 'start':
                    if (empty($tesis->container_id)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'El proyecto no está configurado para iniciar'
                        ], 400);
                    }
                    
                    $result = $this->dockerService->buildAndRunProject($tesis);
                    
                    if (!$result) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No se pudo iniciar el proyecto'
                        ], 500);
                    }
                    
                    $tesis->container_id = $result['container_id'];
                    $tesis->container_status = $result['container_status'];
                    $tesis->project_url = $result['project_url'];
                    $tesis->project_config = $result['project_config'];
                    $tesis->last_deployed = now();
                    $tesis->save();
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Proyecto iniciado correctamente',
                        'data' => [
                            'container_id' => $tesis->container_id,
                            'container_status' => $tesis->container_status,
                            'project_url' => $tesis->project_url
                        ]
                    ]);
                    
                case 'stop':
                    if (empty($tesis->container_id)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No hay contenedor para detener'
                        ], 400);
                    }
                    
                    $stopped = $this->dockerService->stopContainer($tesis->container_id);
                    
                    if (!$stopped) {
                        return response()->json([
                            'success' => false,
                            'message' => 'No se pudo detener el proyecto'
                        ], 500);
                    }
                    
                    $tesis->container_status = 'stopped';
                    $tesis->save();
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Proyecto detenido correctamente'
                    ]);
                    
                case 'status':
                    if (empty($tesis->container_id)) {
                        return response()->json([
                            'success' => true,
                            'data' => [
                                'status' => 'not_configured',
                                'message' => 'El proyecto no está configurado'
                            ]
                        ]);
                    }
                    
                    $containerStatus = $this->dockerService->getContainerStatus($tesis->container_id);
                    
                    if ($containerStatus && $containerStatus != $tesis->container_status) {
                        $tesis->container_status = $containerStatus;
                        $tesis->save();
                    }
                    
                    $stats = null;
                    if ($containerStatus === 'running') {
                        $stats = $this->dockerService->getContainerStats($tesis->container_id);
                    }
                    
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'container_id' => $tesis->container_id,
                            'status' => $tesis->container_status,
                            'project_url' => $tesis->project_url,
                            'last_deployed' => $tesis->last_deployed,
                            'stats' => $stats
                        ]
                    ]);
                    
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Acción no reconocida'
                    ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al gestionar el proyecto: ' . $e->getMessage()
            ], 500);
        }
    }
}
