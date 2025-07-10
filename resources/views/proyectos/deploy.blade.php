@extends('adminlte::page')

@section('title', 'Desplegar Proyecto')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Despliegue de Proyecto</h1>
        </div>
        <div class="col-sm-6">
            <div class="float-sm-right">
                <a href="{{ route('proyectos.setup', $tesis->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a configuración
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    @if(!empty($tesis->deployment_error) && 
        (strpos($tesis->deployment_error, 'docker-compose') !== false ||
         strpos($tesis->deployment_error, 'Docker no está instalado') !== false ||
         strpos($tesis->deployment_error, 'Docker no está accesible') !== false))
        <div class="alert alert-danger mb-4">
            <div class="d-flex">
                <div class="mr-3">
                    <i class="fas fa-exclamation-circle fa-3x text-danger"></i>
                </div>                <div>
                    <h4>Problema de instalación de Docker detectado</h4>
                    <p>Se ha detectado un problema con la instalación de Docker en el servidor. Este problema debe resolverse antes de poder desplegar proyectos.</p>
                    <div class="mt-3">
                        <a href="{{ route('proyectos.docker-troubleshoot') }}" class="btn btn-warning mr-2">
                            <i class="fas fa-tools mr-1"></i> Ejecutar diagnóstico de Docker
                        </a>
                        <a href="{{ route('proyectos.docker-install') }}" class="btn btn-info">
                            <i class="fas fa-book-open mr-1"></i> Guía de instalación
                        </a>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Despliegue de Proyecto</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info-circle"></i> Proyecto Configurado</h5>
                        <p>Tipo de proyecto detectado: <strong>{{ ucfirst($tesis->project_type) }}</strong></p>
                        <p>Repositorio: <strong>{{ $tesis->github_repo }}</strong></p>
                    </div>                    @if(!empty($tesis->container_id) && $tesis->container_status === 'running')
                        <div class="alert alert-success">
                            <h5><i class="icon fas fa-check"></i> Proyecto Desplegado</h5>
                            <p>El proyecto ya está desplegado y en ejecución.</p>
                            <p>Estado del contenedor: <span class="badge badge-success">En ejecución</span></p>
                            
                            <div class="mt-3">
                                <a href="{{ route('proyectos.show', $tesis->id) }}" class="btn btn-primary">
                                    <i class="fas fa-desktop"></i> Ver Proyecto
                                </a>
                                
                                <form action="{{ route('proyectos.stop', $tesis->id) }}" method="POST" class="d-inline-block ml-2">
                                    @csrf
                                    <button type="submit" class="btn btn-danger">
                                        <i class="fas fa-stop"></i> Detener Proyecto
                                    </button>
                                </form>
                                
                                <form action="{{ route('proyectos.restart', $tesis->id) }}" method="POST" class="d-inline-block ml-2">
                                    @csrf
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-sync"></i> Reiniciar Proyecto
                                    </button>
                                </form>
                                
                                <a href="{{ route('proyectos.logs', $tesis->id) }}" class="btn btn-info d-inline-block ml-2">
                                    <i class="fas fa-file-alt"></i> Ver Logs
                                </a>
                            </div>
                        </div>
                    @elseif(!empty($tesis->container_status) && $tesis->container_status === 'deploying')
                        <div class="alert alert-info">
                            <h5><i class="icon fas fa-spinner fa-spin"></i> Despliegue en Progreso</h5>
                            <p>El proyecto está siendo desplegado. Este proceso puede tardar varios minutos...</p>
                            <div class="progress progress-lg mt-2">
                                <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width: 100%"></div>
                            </div>
                            <p class="mt-2 small text-muted">No cierre esta página. Se actualizará automáticamente cuando el despliegue haya finalizado.</p>
                        </div>
                    @elseif(!empty($tesis->container_status) && $tesis->container_status === 'failed')
                        <div class="alert alert-danger">
                            <h5><i class="icon fas fa-exclamation-triangle"></i> Error en el Despliegue</h5>
                            <p>Se produjo un error durante el proceso de despliegue del proyecto.</p>
                            @if(!empty($tesis->deployment_error))
                                <div class="mt-2 border border-danger rounded p-2 bg-light">
                                    <p class="text-danger"><strong>Detalle del error:</strong></p>
                                    <pre class="text-danger">{{ $tesis->deployment_error }}</pre>
                                </div>
                            @endif
                            <p class="mt-2">Puede consultar los logs para más detalles o intentar desplegar nuevamente el proyecto.</p>
                            <div class="mt-3">
                                <form action="{{ route('proyectos.do-deploy', $tesis->id) }}" method="POST" class="d-inline-block">
                                    @csrf
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-rocket"></i> Intentar Nuevamente
                                    </button>
                                </form>
                                @if(!empty($tesis->container_id))
                                <a href="{{ route('proyectos.logs', $tesis->id) }}" class="btn btn-info d-inline-block ml-2">
                                    <i class="fas fa-file-alt"></i> Ver Logs
                                </a>
                                @endif
                            </div>
                        </div>
                    @elseif(!empty($tesis->container_id) && $tesis->container_status === 'stopped')
                        <div class="alert alert-warning">
                            <h5><i class="icon fas fa-pause"></i> Proyecto Detenido</h5>
                            <p>El proyecto está desplegado pero actualmente detenido.</p>
                            <p>Estado del contenedor: <span class="badge badge-warning">Detenido</span></p>
                            
                            <form action="{{ route('proyectos.do-deploy', $tesis->id) }}" method="POST" class="mt-3">
                                @csrf
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-play"></i> Iniciar Proyecto
                                </button>
                            </form>
                        </div>
                    @else
                        <form action="{{ route('proyectos.do-deploy', $tesis->id) }}" method="POST">
                            @csrf
                            <p>El siguiente paso es desplegar el proyecto en un contenedor Docker.</p>
                            <p>Al hacer clic en el botón, el sistema:</p>
                            <ol>
                                <li>Creará un Dockerfile específico para tu tipo de proyecto</li>
                                <li>Construirá una imagen Docker con tu aplicación</li>
                                <li>Desplegará un contenedor con tu proyecto en ejecución</li>
                                <li>Configurará el acceso para que pueda ser visualizado</li>
                            </ol>
                            
                            <div class="alert alert-warning">
                                <h5><i class="icon fas fa-exclamation-triangle"></i> Importante</h5>
                                <p>Este proceso puede tardar varios minutos dependiendo de la complejidad del proyecto y las dependencias que requiera.</p>
                                <p>Por favor, no cierre esta ventana durante el proceso de despliegue.</p>
                            </div>
                            
                            <button type="submit" class="btn btn-success btn-lg" id="deployButton">
                                <i class="fas fa-rocket"></i> Desplegar Proyecto
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Detalles Técnicos</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Configuración del Contenedor</h5>
                            <dl>
                                <dt>Tipo de Proyecto</dt>
                                <dd>{{ ucfirst($tesis->project_type ?? 'No detectado') }}</dd>
                                
                                <dt>Ruta del Repositorio</dt>
                                <dd><code>{{ $tesis->project_repo_path ?? 'No clonado' }}</code></dd>
                                
                                <dt>ID del Contenedor</dt>
                                <dd><code>{{ $tesis->container_id ?? 'No desplegado' }}</code></dd>
                                  <dt>Estado del Contenedor</dt>
                                <dd>
                                    @if(!empty($tesis->container_status))
                                        @php                                            $statusClass = '';
                                            $statusIcon = '';
                                            switch($tesis->container_status) {
                                                case 'running': 
                                                    $statusClass = 'success'; 
                                                    $statusIcon = 'fa-play-circle'; 
                                                    $statusText = 'En ejecución';
                                                    break;
                                                case 'stopped': 
                                                    $statusClass = 'warning'; 
                                                    $statusIcon = 'fa-pause-circle'; 
                                                    $statusText = 'Detenido';
                                                    break;
                                                case 'deploying': 
                                                    $statusClass = 'info'; 
                                                    $statusIcon = 'fa-spinner fa-spin'; 
                                                    $statusText = 'En progreso';
                                                    break;
                                                case 'failed': 
                                                    $statusClass = 'danger'; 
                                                    $statusIcon = 'fa-exclamation-circle'; 
                                                    $statusText = 'Falló';
                                                    break;
                                                case 'docker_unavailable': 
                                                    $statusClass = 'danger'; 
                                                    $statusIcon = 'fa-times-circle'; 
                                                    $statusText = 'Docker no disponible';
                                                    break;
                                                case 'not_found': 
                                                    $statusClass = 'secondary'; 
                                                    $statusIcon = 'fa-search'; 
                                                    $statusText = 'Contenedor no encontrado';
                                                    break;
                                                default: 
                                                    $statusClass = 'secondary'; 
                                                    $statusIcon = 'fa-question-circle'; 
                                                    $statusText = ucfirst($tesis->container_status);
                                                    break;
                                            }
                                        @endphp
                                        <span class="badge badge-{{ $statusClass }}" 
                                              @if(!empty($tesis->deployment_error) && $tesis->container_status === 'failed')
                                                  data-toggle="tooltip" 
                                                  data-placement="top" 
                                                  title="Error: {{ htmlspecialchars(substr($tesis->deployment_error, 0, 100)) }}{{ strlen($tesis->deployment_error) > 100 ? '...' : '' }}"
                                              @endif
                                        >
                                            <i class="fas {{ $statusIcon }} mr-1"></i> {{ $statusText }}
                                        </span>
                                    @else
                                        <span class="text-muted">No disponible</span>
                                    @endif
                                </dd>
                                
                                <dt>Última Actualización</dt>
                                <dd>{{ $tesis->last_deployed ? $tesis->last_deployed->format('d/m/Y H:i:s') : 'Nunca' }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h5>Recursos Asignados</h5>
                            <dl>
                                <dt>CPU</dt>
                                <dd>1 núcleo</dd>
                                
                                <dt>Memoria</dt>
                                <dd>512MB</dd>
                                
                                <dt>Almacenamiento</dt>
                                <dd>1GB</dd>
                                
                                <dt>Puerto</dt>
                                <dd>
                                    @if(!empty($tesis->project_config) && isset($tesis->project_config['external_port']))
                                        <code>{{ $tesis->project_config['external_port'] }}</code>
                                    @else
                                        <span class="text-muted">No asignado</span>
                                    @endif
                                </dd>
                                  <dt>URL del Proyecto</dt>
                                <dd>
                                    @if(!empty($tesis->project_url))
                                        <a href="{{ route('proyectos.proxy', $tesis->id) }}" target="_blank">
                                            {{ route('proyectos.proxy', $tesis->id) }}
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    @else
                                        <span class="text-muted">No disponible</span>
                                    @endif
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Información de la Tesis</h3>
                </div>
                <div class="card-body">
                    <dl>
                        <dt>Título</dt>
                        <dd>{{ $tesis->titulo }}</dd>
                        
                        <dt>Alumno</dt>
                        <dd>{{ $tesis->alumno->nombre }} {{ $tesis->alumno->apellido }}</dd>
                        
                        <dt>Tutor</dt>
                        <dd>{{ $tesis->tutor->nombre }} {{ $tesis->tutor->apellido }}</dd>
                        
                        <dt>Estado</dt>
                        <dd>
                            @php
                                $badgeClass = '';
                                switch($tesis->estado) {
                                    case 'pendiente': $badgeClass = 'warning'; break;
                                    case 'en_progreso': $badgeClass = 'info'; break;
                                    case 'completado': $badgeClass = 'success'; break;
                                    case 'rechazado': $badgeClass = 'danger'; break;
                                }
                            @endphp
                            <span class="badge badge-{{ $badgeClass }}">
                                {{ ucfirst(str_replace('_', ' ', $tesis->estado)) }}
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Proceso de Despliegue</h3>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="time-label">
                            <span class="bg-green">Inicio</span>
                        </div>
                        
                        <div>
                            <i class="fas fa-github bg-blue"></i>
                            <div class="timeline-item">
                                <h3 class="timeline-header">Configuración de GitHub</h3>
                                <div class="timeline-body">
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Completado</span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <i class="fas fa-cogs bg-yellow"></i>
                            <div class="timeline-item">
                                <h3 class="timeline-header">Configuración del Proyecto</h3>
                                <div class="timeline-body">
                                    <span class="badge badge-success"><i class="fas fa-check"></i> Completado</span>
                                </div>
                            </div>
                        </div>
                          <div>
                            <i class="fas fa-rocket bg-purple"></i>
                            <div class="timeline-item">
                                <h3 class="timeline-header">Despliegue</h3>
                                <div class="timeline-body">
                                    @php
                                        $deploymentStatus = 'pending';
                                        $deploymentLabel = 'Pendiente';
                                        $deploymentClass = 'secondary';
                                        
                                        if ($tesis->container_status === 'deploying') {
                                            $deploymentStatus = 'in_progress';
                                            $deploymentLabel = 'En Progreso';
                                            $deploymentClass = 'info';
                                        } elseif ($tesis->container_status === 'failed') {
                                            $deploymentStatus = 'failed';
                                            $deploymentLabel = 'Falló';
                                            $deploymentClass = 'danger';
                                        } elseif (!empty($tesis->container_id)) {
                                            $deploymentStatus = 'completed';
                                            $deploymentLabel = 'Completado';
                                            $deploymentClass = 'success';
                                        }
                                    @endphp
                                    
                                    <span class="badge badge-{{ $deploymentClass }}">
                                        @if($deploymentStatus === 'in_progress')
                                            <i class="fas fa-spinner fa-spin mr-1"></i>
                                        @elseif($deploymentStatus === 'failed')
                                            <i class="fas fa-times-circle mr-1"></i>
                                        @elseif($deploymentStatus === 'completed')
                                            <i class="fas fa-check-circle mr-1"></i>
                                        @endif
                                        {{ $deploymentLabel }}
                                    </span>
                                                      @if($deploymentStatus === 'failed' && !empty($tesis->deployment_error))
                                        <div class="mt-2">
                                            <button class="btn btn-sm btn-outline-danger" type="button" data-toggle="collapse" data-target="#errorDetails">
                                                Ver detalle del error
                                            </button>
                                            <div class="collapse mt-2" id="errorDetails">
                                                <div class="card card-body bg-light text-danger p-2">
                                                    <small>{{ $tesis->deployment_error }}</small>
                                                    
                                                    @if(strpos($tesis->deployment_error, 'Docker no está instalado') !== false || 
                                                       strpos($tesis->deployment_error, 'docker-compose') !== false ||
                                                       strpos($tesis->deployment_error, 'docker compose') !== false)
                                                        <hr>
                                                        <strong>Requisitos de instalación:</strong>
                                                        <ul class="mb-0 pl-3">
                                                            <li>Docker Desktop debe estar instalado en el servidor</li>
                                                            <li>Docker debe estar en ejecución</li>
                                                            <li>Docker Compose debe estar disponible (incluido con Docker Desktop)</li>
                                                            <li>El usuario del sistema debe tener permisos para ejecutar comandos Docker</li>
                                                        </ul>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <i class="fas fa-laptop-code bg-{{ (!empty($tesis->container_id) && $tesis->container_status === 'running') ? 'green' : 'gray' }}"></i>
                            <div class="timeline-item">
                                <h3 class="timeline-header">Aplicación en Ejecución</h3>
                                <div class="timeline-body">
                                    @php
                                        $runningStatus = 'pending';
                                        $runningLabel = 'Pendiente';
                                        $runningClass = 'secondary';
                                        
                                        if (!empty($tesis->container_id)) {
                                            if ($tesis->container_status === 'running') {
                                                $runningStatus = 'active';
                                                $runningLabel = 'Activo';
                                                $runningClass = 'success';
                                            } elseif ($tesis->container_status === 'stopped') {
                                                $runningStatus = 'stopped';
                                                $runningLabel = 'Detenido';
                                                $runningClass = 'warning';
                                            } elseif ($tesis->container_status === 'deploying') {
                                                $runningStatus = 'deploying';
                                                $runningLabel = 'Iniciando...';
                                                $runningClass = 'info';
                                            }
                                        }
                                    @endphp
                                    
                                    <span class="badge badge-{{ $runningClass }}">
                                        @if($runningStatus === 'active')
                                            <i class="fas fa-play-circle mr-1"></i>
                                        @elseif($runningStatus === 'stopped')
                                            <i class="fas fa-pause-circle mr-1"></i>
                                        @elseif($runningStatus === 'deploying')
                                            <i class="fas fa-spinner fa-spin mr-1"></i>
                                        @endif
                                        {{ $runningLabel }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <i class="far fa-clock bg-gray"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
    $(document).ready(function() {
        // Form submission handling - show spinner
        $('form').submit(function() {
            $(this).find('button[type="submit"]').prop('disabled', true);
            $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
        });
        
        // Auto-refresh page when deployment is in progress
        @if(!empty($tesis->container_status) && $tesis->container_status === 'deploying')
            const refreshInterval = 5000; // 5 seconds
            let progressValue = 0;
            
            // Update progress animation
            const updateProgress = function() {
                progressValue = (progressValue + 5) % 100;
                $('.progress-bar').css('width', progressValue + '%');
            };
            
            // Set interval for progress animation
            const progressInterval = setInterval(updateProgress, 500);
            
            // Set interval for page refresh
            setTimeout(function() {
                window.location.reload();
            }, refreshInterval);
        @endif
        
        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>
@stop
