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
                        <form action="{{ route('proyectos.do-deploy', $tesis->id) }}" method="POST" enctype="multipart/form-data">
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
                            
                            <!-- Panel de Backups -->
                            <div class="card card-info mb-3">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-archive"></i> Restaurar desde Backup (Opcional)
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="text-muted">Puedes cargar un archivo de backup para restaurar el proyecto después del despliegue.</p>
                                    
                                    <div class="alert alert-info">
                                        <strong>Tipos de archivo soportados:</strong>
                                        <ul class="mb-0 mt-1">
                                            <li><strong>.zip, .tar, .tar.gz:</strong> Backup completo (archivos + base de datos)</li>
                                            <li><strong>.sql:</strong> Solo base de datos (restauración rápida)</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-8">
                                            <div class="form-group">
                                                <label for="backup_file">Cargar archivo de backup:</label>
                                                <div class="input-group">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="backup_file" name="backup_file" 
                                                               accept=".zip,.tar.gz,.tar,.sql" onchange="handleBackupFile(this)">
                                                        <label class="custom-file-label" for="backup_file" id="backup_file_label">
                                                            Seleccionar archivo de backup (.zip, .sql)
                                                        </label>
                                                    </div>
                                                    <div class="input-group-append">
                                                        <button class="btn btn-outline-secondary" type="button" id="clear_backup" 
                                                                onclick="clearBackupFile()" style="display: none;">
                                                            <i class="fas fa-times"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <small class="form-text text-muted">
                                                    Formatos soportados: .zip, .tar.gz, .tar, .sql (máximo 100MB)
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="form-group">
                                                <label for="backup_description">Descripción (opcional):</label>
                                                <input type="text" class="form-control" id="backup_description" name="backup_description" 
                                                       placeholder="Ej: Backup v1.2.0" maxlength="255">
                                                <small class="form-text text-muted">
                                                    Para identificar este backup
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Información del archivo seleccionado -->
                                    <div id="backup-file-info" class="alert alert-light d-none">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <strong>Archivo:</strong> <span id="file-name">-</span><br>
                                                <strong>Tamaño:</strong> <span id="file-size">-</span><br>
                                                <strong>Tipo:</strong> <span id="file-type">-</span>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <i class="fas fa-file-archive fa-3x text-info"></i>
                                                    <br>
                                                    <span class="badge badge-success">Archivo listo</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- Alternativa: usar backups existentes -->
                                    @if($tesis->backups()->count() > 0)
                                        <div class="mt-3">
                                            <button type="button" class="btn btn-outline-info btn-sm" data-toggle="collapse" 
                                                    data-target="#existing-backups" aria-expanded="false">
                                                <i class="fas fa-list"></i> O usar backup existente ({{ $tesis->backups()->count() }} disponibles)
                                            </button>
                                            
                                            <div class="collapse mt-2" id="existing-backups">
                                                <div class="form-group">
                                                    <label for="existing_backup_id">Backups existentes:</label>
                                                    <select class="form-control" name="existing_backup_id" id="existing_backup_id">
                                                        <option value="">-- Seleccionar backup existente --</option>
                                                        @foreach($tesis->backups()->orderBy('created_at', 'desc')->get() as $backup)
                                                            <option value="{{ $backup->id }}" data-description="{{ $backup->description }}" 
                                                                    data-type="{{ $backup->type }}" data-date="{{ $backup->created_at->format('d/m/Y H:i') }}"
                                                                    data-size="{{ $backup->file_size }}" data-version="{{ $backup->version }}">
                                                                {{ $backup->description ?? 'Backup sin descripción' }} 
                                                                ({{ $backup->created_at->format('d/m/Y H:i') }})
                                                                @if($backup->version) - v{{ $backup->version }} @endif
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <small class="form-text text-muted">
                                                        Si seleccionas un backup existente, se ignorará el archivo subido.
                                                    </small>
                                                </div>
                                                
                                                <div id="existing-backup-info" class="alert alert-light d-none">
                                                    <div class="row">
                                                        <div class="col-md-8">
                                                            <strong>Descripción:</strong> <span id="existing-backup-description">-</span><br>
                                                            <strong>Tipo:</strong> <span id="existing-backup-type">-</span><br>
                                                            <strong>Fecha:</strong> <span id="existing-backup-date">-</span>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <strong>Tamaño:</strong> <span id="existing-backup-size">-</span><br>
                                                            <strong>Versión:</strong> <span id="existing-backup-version">-</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                    
                                    @if($tesis->backups()->count() == 0)
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle"></i>
                                            <strong>No hay backups guardados</strong><br>
                                            Este proyecto no tiene backups previos. Puedes cargar un archivo de backup desde tu computadora 
                                            o crear nuevos backups después del despliegue.
                                        </div>
                                    @endif
                                </div>
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
        // Existing backups selection handler
        const backups = @json($tesis->backups()->get()->toArray());
        
        $('#existing_backup_id').change(function() {
            const backupId = $(this).val();
            const backupInfo = $('#existing-backup-info');
            
            if (backupId && backupId !== '') {
                const backup = backups.find(b => b.id == backupId);
                if (backup) {
                    $('#existing-backup-description').text(backup.description || 'Sin descripción');
                    $('#existing-backup-type').html('<span class="badge badge-' + (backup.type === 'full' ? 'primary' : 'secondary') + '">' + backup.type + '</span>');
                    $('#existing-backup-date').text(new Date(backup.created_at).toLocaleString());
                    $('#existing-backup-size').text(backup.file_size ? formatFileSize(backup.file_size) : 'No disponible');
                    $('#existing-backup-version').text(backup.version || 'Sin versión');
                    backupInfo.removeClass('d-none');
                    
                    // Clear file upload when selecting existing backup
                    clearBackupFile();
                    updateDeployButton();
                } else {
                    backupInfo.addClass('d-none');
                }
            } else {
                backupInfo.addClass('d-none');
                updateDeployButton();
            }
        });
        
        // Initialize tooltips and file input
        $('[data-toggle="tooltip"]').tooltip();
        
        // Form submission handling
        $('form').submit(function(e) {
            const form = $(this);
            const submitBtn = form.find('button[type="submit"]');
            const backupFile = $('#backup_file')[0].files[0];
            const existingBackupId = $('#existing_backup_id').val();
            
            // Validate file size (100MB max)
            if (backupFile && backupFile.size > 100 * 1024 * 1024) {
                e.preventDefault();
                Swal.fire({
                    title: 'Archivo muy grande',
                    text: 'El archivo de backup no puede superar los 100MB.',
                    icon: 'error'
                });
                return false;
            }
            
            // Update button based on backup selection
            if (backupFile || existingBackupId) {
                submitBtn.prop('disabled', true);
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Desplegando y restaurando...');
            } else {
                submitBtn.prop('disabled', true);
                submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Desplegando...');
            }
        });
        
        // Auto-refresh page when deployment is in progress
        @if(!empty($tesis->container_status) && $tesis->container_status === 'deploying')
            const refreshInterval = 5000; // 5 seconds
            let progressValue = 0;
            
            const updateProgress = function() {
                progressValue = (progressValue + 5) % 100;
                $('.progress-bar').css('width', progressValue + '%');
            };
            
            const progressInterval = setInterval(updateProgress, 500);
            
            setTimeout(function() {
                window.location.reload();
            }, refreshInterval);
        @endif
    });

    // Handle backup file selection
    function handleBackupFile(input) {
        const file = input.files[0];
        const fileInfo = $('#backup-file-info');
        const clearBtn = $('#clear_backup');
        const label = $('#backup_file_label');
        
        if (file) {
            // Validate file type
            const allowedTypes = ['.zip', '.tar.gz', '.tar', '.sql'];
            const fileName = file.name.toLowerCase();
            const isValidType = allowedTypes.some(type => fileName.endsWith(type.toLowerCase()));
            
            if (!isValidType) {
                Swal.fire({
                    title: 'Tipo de archivo no válido',
                    text: 'Solo se permiten archivos .zip, .tar.gz, .tar o .sql',
                    icon: 'error'
                });
                clearBackupFile();
                return;
            }
            
            // Validate file size (100MB max)
            if (file.size > 100 * 1024 * 1024) {
                Swal.fire({
                    title: 'Archivo muy grande',
                    text: 'El archivo no puede superar los 100MB.',
                    icon: 'error'
                });
                clearBackupFile();
                return;
            }
            
            // Update file info with backup type detection
            $('#file-name').text(file.name);
            $('#file-size').text(formatFileSize(file.size));
            
            const fileType = getFileType(file.name);
            const isSqlFile = fileName.endsWith('.sql');
            const backupTypeLabel = isSqlFile ? 
                '<span class="badge badge-warning">Solo Base de Datos</span>' : 
                '<span class="badge badge-primary">Backup Completo</span>';
            
            $('#file-type').html(fileType + ' ' + backupTypeLabel);
            
            label.text(file.name);
            fileInfo.removeClass('d-none');
            clearBtn.show();
            
            // Update icon based on file type
            const iconClass = isSqlFile ? 'fas fa-database fa-3x text-warning' : 'fas fa-file-archive fa-3x text-info';
            fileInfo.find('i').attr('class', iconClass);
            
            // Clear existing backup selection when file is uploaded
            $('#existing_backup_id').val('');
            $('#existing-backup-info').addClass('d-none');
            
            updateDeployButton();
        }
    }
    
    // Clear backup file selection
    function clearBackupFile() {
        $('#backup_file').val('');
        $('#backup_file_label').text('Seleccionar archivo de backup (.zip, .sql)');
        $('#backup-file-info').addClass('d-none');
        $('#clear_backup').hide();
        updateDeployButton();
    }
    
    // Update deploy button text
    function updateDeployButton() {
        const hasFile = $('#backup_file')[0].files.length > 0;
        const hasExistingBackup = $('#existing_backup_id').val() !== '';
        const deployBtn = $('#deployButton');
        
        if (hasFile) {
            const fileName = $('#backup_file')[0].files[0].name.toLowerCase();
            const isSqlFile = fileName.endsWith('.sql');
            const buttonText = isSqlFile ? 
                '<i class="fas fa-rocket"></i> Desplegar y Restaurar BD' : 
                '<i class="fas fa-rocket"></i> Desplegar y Restaurar Backup';
            deployBtn.html(buttonText);
        } else if (hasExistingBackup) {
            deployBtn.html('<i class="fas fa-rocket"></i> Desplegar y Restaurar Backup');
        } else {
            deployBtn.html('<i class="fas fa-rocket"></i> Desplegar Proyecto');
        }
    }
    
    // Get file type from extension
    function getFileType(filename) {
        const extension = filename.toLowerCase().split('.').pop();
        switch(extension) {
            case 'zip': return 'ZIP Archive';
            case 'gz': return filename.toLowerCase().endsWith('.tar.gz') ? 'TAR.GZ Archive' : 'GZIP Archive';
            case 'tar': return 'TAR Archive';
            case 'sql': return 'SQL Database';
            default: return 'Unknown';
        }
    }
    
    // Format file size helper
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
</script>
@stop
