@extends('adminlte::page')

@section('title', 'Configurar Proyecto')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Configuración de Proyecto</h1>
        </div>
        <div class="col-sm-6">
            <div class="float-sm-right">
                <a href="{{ route('proyectos.github-config', $tesis->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a configuración de GitHub
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
                    <h3 class="card-title">Configuración del Proyecto</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info-circle"></i> Repositorio GitHub Configurado</h5>
                        <p>URL del repositorio: <strong>{{ $tesis->github_repo }}</strong></p>
                    </div>
                    
                    @if(isset($tesis->project_type) && !empty($tesis->project_type))
                        <div class="alert alert-success">
                            <h5><i class="icon fas fa-check"></i> Proyecto Configurado</h5>
                            <p>El proyecto ha sido clonado y configurado correctamente.</p>
                            <p>Tipo de proyecto detectado: <strong>{{ ucfirst($tesis->project_type) }}</strong></p>
                            
                            <a href="{{ route('proyectos.deploy', $tesis->id) }}" class="btn btn-primary">
                                <i class="fas fa-rocket"></i> Continuar al Despliegue
                            </a>
                        </div>
                    @else
                        <form action="{{ route('proyectos.clone', $tesis->id) }}" method="POST">
                            @csrf
                            <p>El siguiente paso es clonar el repositorio y detectar automáticamente el tipo de proyecto.</p>
                            <p>Al hacer clic en el botón, el sistema:</p>
                            <ol>
                                <li>Clonará el repositorio desde GitHub</li>
                                <li>Analizará su estructura para detectar el tipo de proyecto</li>
                                <li>Preparará la configuración para el despliegue</li>
                            </ol>
                            
                            <div class="alert alert-warning">
                                <h5><i class="icon fas fa-exclamation-triangle"></i> Importante</h5>
                                <p>Este proceso puede tardar unos minutos dependiendo del tamaño del repositorio.</p>
                                <p>Asegúrate de que el repositorio contiene todos los archivos necesarios para la compilación y ejecución del proyecto.</p>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-download"></i> Clonar y Detectar Proyecto
                            </button>
                        </form>
                    @endif
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Tipos de Proyectos Soportados</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-info"><i class="fab fa-laravel"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Laravel</span>
                                    <span class="info-box-description">Proyectos PHP con framework Laravel</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-danger"><i class="fab fa-java"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Java (Maven)</span>
                                    <span class="info-box-description">Proyectos Java con Maven</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-warning"><i class="fab fa-java"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Java (Gradle)</span>
                                    <span class="info-box-description">Proyectos Java con Gradle</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-success"><i class="fab fa-node-js"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Node.js</span>
                                    <span class="info-box-description">Aplicaciones JavaScript con Node.js</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-box">
                                <span class="info-box-icon bg-primary"><i class="fab fa-python"></i></span>
                                <div class="info-box-content">
                                    <span class="info-box-text">Python</span>
                                    <span class="info-box-description">Aplicaciones Python</span>
                                </div>
                            </div>
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
                    <h3 class="card-title">Proceso de Configuración</h3>
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
                                    <span class="badge badge-{{ isset($tesis->project_type) ? 'success' : 'warning' }}">
                                        {{ isset($tesis->project_type) ? 'Completado' : 'En Progreso' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <i class="fas fa-rocket bg-purple"></i>
                            <div class="timeline-item">
                                <h3 class="timeline-header">Despliegue</h3>
                                <div class="timeline-body">
                                    <span class="badge badge-secondary">Pendiente</span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <i class="fas fa-laptop-code bg-gray"></i>
                            <div class="timeline-item">
                                <h3 class="timeline-header">Aplicación en Ejecución</h3>
                                <div class="timeline-body">
                                    <span class="badge badge-secondary">Pendiente</span>
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
        $('form').submit(function() {
            $(this).find('button[type="submit"]').prop('disabled', true);
            $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin"></i> Procesando...');
        });
    });
</script>
@stop
