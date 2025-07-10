@extends('adminlte::page')

@section('title', 'Detalles de la Tesis')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Detalles de la Tesis</h1>
        </div>
        <div class="col-sm-6">
            <div class="float-sm-right">
                <a href="{{ route('tesis.edit', $tesis->id) }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="{{ route('tesis.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Información General</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <dl>
                                <dt>Título</dt>
                                <dd>{{ $tesis->titulo }}</dd>
                                
                                <dt>Alumno</dt>
                                <dd>{{ $tesis->alumno->nombre }} {{ $tesis->alumno->apellido }}</dd>
                                
                                <dt>Carrera</dt>
                                <dd>{{ $tesis->alumno->carrera->nombre }}</dd>
                                
                                <dt>Tutor</dt>
                                <dd>{{ $tesis->tutor->nombre }} {{ $tesis->tutor->apellido }}</dd>
                                
                                <dt>Estado</dt>
                                <dd>
                                    @php
                                        $badgeClass = '';
                                        switch($tesis->estado) {
                                            case 'pendiente':
                                                $badgeClass = 'warning';
                                                $estado = 'Pendiente';
                                                break;
                                            case 'en_progreso':
                                                $badgeClass = 'info';
                                                $estado = 'En Progreso';
                                                break;
                                            case 'completado':
                                                $badgeClass = 'success';
                                                $estado = 'Completado';
                                                break;
                                            case 'rechazado':
                                                $badgeClass = 'danger';
                                                $estado = 'Rechazado';
                                                break;
                                        }
                                    @endphp
                                    <span class="badge badge-{{ $badgeClass }}">{{ $estado }}</span>
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <dl>
                                <dt>Fecha de Inicio</dt>
                                <dd>{{ $tesis->fecha_inicio->format('d/m/Y') }}</dd>
                                
                                <dt>Fecha de Fin</dt>
                                <dd>{{ $tesis->fecha_fin ? $tesis->fecha_fin->format('d/m/Y') : 'N/A' }}</dd>
                                
                                <dt>Calificación</dt>
                                <dd>
                                    @if($tesis->calificacion)
                                        <span class="badge badge-primary">{{ $tesis->calificacion }}</span>
                                    @else
                                        <span class="badge badge-secondary">No calificado</span>
                                    @endif
                                </dd>
                                
                                <dt>Fecha de Registro</dt>
                                <dd>{{ $tesis->created_at->format('d/m/Y H:i') }}</dd>
                                
                                <dt>Última Actualización</dt>
                                <dd>{{ $tesis->updated_at->format('d/m/Y H:i') }}</dd>
                            </dl>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="callout callout-info">
                                <h5>Descripción</h5>
                                <p>{{ $tesis->descripcion }}</p>
                            </div>
                            
                            @if($tesis->observaciones)
                                <div class="callout callout-warning">
                                    <h5>Observaciones</h5>
                                    <p>{{ $tesis->observaciones }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            @if($tesis->documento_url)
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Documento</h3>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <i class="fas fa-file-pdf fa-5x text-danger"></i>
                        </div>
                        <a href="{{ Storage::url($tesis->documento_url) }}" target="_blank" class="btn btn-info btn-block">
                            <i class="fas fa-eye"></i> Ver Documento
                        </a>
                        <a href="{{ Storage::url($tesis->documento_url) }}" download class="btn btn-success btn-block mt-2">
                            <i class="fas fa-download"></i> Descargar Documento
                        </a>
                    </div>
                </div>
            @else
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Documento</h3>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <i class="fas fa-file-alt fa-5x text-secondary mb-3"></i>
                            <p>No hay documento adjunto</p>
                        </div>
                    </div>
                </div>
            @endif
            
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Información del Tutor</h3>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <i class="fas fa-user-tie fa-3x text-primary"></i>
                    </div>
                    <dl>
                        <dt>Nombre</dt>
                        <dd>{{ $tesis->tutor->nombre }} {{ $tesis->tutor->apellido }}</dd>
                        
                        <dt>Email</dt>
                        <dd>{{ $tesis->tutor->email }}</dd>
                        
                        <dt>Especialidad</dt>
                        <dd>{{ $tesis->tutor->especialidad }}</dd>
                        
                        <dt>Teléfono</dt>
                        <dd>{{ $tesis->tutor->telefono ?? 'No registrado' }}</dd>
                    </dl>
                    <a href="{{ route('tutor.show', $tesis->tutor->id) }}" class="btn btn-outline-primary btn-block">
                        <i class="fas fa-user"></i> Ver Perfil del Tutor
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Project/GitHub Integration -->
    <div class="row mt-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Proyecto GitHub</h3>
                </div>
                <div class="card-body">
                    @if(!empty($tesis->github_repo))
                        <div class="row">
                            <div class="col-md-6">
                                <dl>
                                    <dt>Repositorio GitHub</dt>
                                    <dd>
                                        <a href="{{ $tesis->github_repo }}" target="_blank">
                                            {{ $tesis->github_repo }} <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </dd>
                                    
                                    @if(!empty($tesis->project_type))
                                        <dt>Tipo de Proyecto</dt>
                                        <dd>{{ ucfirst($tesis->project_type) }}</dd>
                                    @endif
                                    
                                    @if(!empty($tesis->container_status))
                                        <dt>Estado del Contenedor</dt>
                                        <dd>
                                            @php
                                                $containerBadgeClass = '';
                                                switch($tesis->container_status) {
                                                    case 'running': $containerBadgeClass = 'success'; break;
                                                    case 'stopped': $containerBadgeClass = 'danger'; break;
                                                    default: $containerBadgeClass = 'secondary'; break;
                                                }
                                            @endphp
                                            <span class="badge badge-{{ $containerBadgeClass }}">
                                                {{ ucfirst($tesis->container_status) }}
                                            </span>
                                        </dd>
                                        
                                        <dt>Última Actualización</dt>
                                        <dd>{{ $tesis->last_deployed ? $tesis->last_deployed->format('d/m/Y H:i:s') : 'Nunca' }}</dd>
                                    @endif
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <div class="text-center mb-4">
                                    @if(empty($tesis->container_id))
                                        <div class="alert alert-info">
                                            <i class="icon fas fa-info-circle"></i>
                                            Este proyecto aún no ha sido desplegado.
                                        </div>
                                    @elseif($tesis->container_status === 'running')
                                        <div class="alert alert-success">
                                            <i class="icon fas fa-check"></i>
                                            El proyecto está en ejecución y accesible.
                                        </div>
                                    @else
                                        <div class="alert alert-warning">
                                            <i class="icon fas fa-exclamation-triangle"></i>
                                            El proyecto está detenido o en un estado no disponible.
                                        </div>
                                    @endif
                                    
                                    @if(!empty($tesis->project_url) && $tesis->container_status === 'running')
                                        <a href="{{ $tesis->project_url }}" target="_blank" class="btn btn-success mb-2">
                                            <i class="fas fa-external-link-alt"></i> Acceder al Proyecto
                                        </a>
                                    @endif
                                    
                                    <div class="btn-group-vertical w-100">
                                        @if(empty($tesis->github_repo))
                                            <a href="{{ route('proyectos.github-config', $tesis->id) }}" class="btn btn-primary mb-2">
                                                <i class="fab fa-github"></i> Configurar Repositorio GitHub
                                            </a>
                                        @elseif(empty($tesis->project_type))
                                            <a href="{{ route('proyectos.setup', $tesis->id) }}" class="btn btn-primary mb-2">
                                                <i class="fas fa-cogs"></i> Configurar Proyecto
                                            </a>
                                        @elseif(empty($tesis->container_id))
                                            <a href="{{ route('proyectos.deploy', $tesis->id) }}" class="btn btn-primary mb-2">
                                                <i class="fas fa-rocket"></i> Desplegar Proyecto
                                            </a>
                                        @else
                                            <a href="{{ route('proyectos.show', $tesis->id) }}" class="btn btn-primary mb-2">
                                                <i class="fas fa-desktop"></i> Gestionar Proyecto
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center">
                            <div class="mb-3">
                                <i class="fab fa-github fa-4x text-secondary mb-3"></i>
                                <h4>No hay repositorio GitHub configurado</h4>
                                <p>Configura un repositorio GitHub para desplegar y ejecutar el proyecto de tesis.</p>
                            </div>
                            <a href="{{ route('proyectos.github-config', $tesis->id) }}" class="btn btn-primary">
                                <i class="fab fa-github"></i> Configurar Repositorio GitHub
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop
