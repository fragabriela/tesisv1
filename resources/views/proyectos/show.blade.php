@extends('adminlte::page')

@section('title', 'Proyecto en Ejecución')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Proyecto en Ejecución</h1>
        </div>
        <div class="col-sm-6">
            <div class="float-sm-right">
                <a href="{{ route('proyectos.deploy', $tesis->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a despliegue
                </a>
                <a href="{{ route('tesis.show', $tesis->id) }}" class="btn btn-info">
                    <i class="fas fa-file-alt"></i> Detalles de la tesis
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
                    <h3 class="card-title">Proyecto: {{ $tesis->titulo }}</h3>
                    <div class="card-tools">
                        <span class="badge badge-{{ $tesis->container_status === 'running' ? 'success' : 'warning' }}">
                            {{ $tesis->container_status === 'running' ? 'En ejecución' : 'Detenido' }}
                        </span>
                    </div>
                </div>
                <div class="card-body">                    <div class="text-center mb-4">
                        <div class="btn-group">
                            <a href="{{ route('proyectos.proxy', $tesis->id) }}" target="_blank" class="btn btn-primary btn-lg">
                                <i class="fas fa-external-link-alt"></i> Abrir Proyecto
                            </a>
                            
                            <button type="button" class="btn btn-primary btn-lg dropdown-toggle dropdown-toggle-split" data-toggle="dropdown">
                                <span class="sr-only">Opciones</span>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="{{ route('proyectos.proxy', $tesis->id) }}" target="_blank">
                                    <i class="fas fa-external-link-alt"></i> Abrir en nueva pestaña
                                </a>
                                <a class="dropdown-item" href="#" id="refresh-iframe">
                                    <i class="fas fa-sync"></i> Recargar
                                </a>
                            </div>
                        </div>
                        
                        <div class="btn-group ml-2">
                            <form action="{{ route('proyectos.stop', $tesis->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-danger btn-lg" {{ $tesis->container_status !== 'running' ? 'disabled' : '' }}>
                                    <i class="fas fa-stop"></i> Detener
                                </button>
                            </form>
                            
                            <form action="{{ route('proyectos.restart', $tesis->id) }}" method="POST" class="ml-2">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-lg">
                                    <i class="fas fa-sync"></i> Reiniciar
                                </button>
                            </form>
                            
                            <a href="{{ route('proyectos.logs', $tesis->id) }}" class="btn btn-info btn-lg ml-2">
                                <i class="fas fa-file-alt"></i> Ver Logs
                            </a>
                        </div>
                    </div>
                      <div class="embed-responsive embed-responsive-16by9 border">
                        <iframe id="project-iframe" class="embed-responsive-item" src="{{ route('proyectos.proxy', $tesis->id) }}"></iframe>
                    </div>                        <div class="alert alert-info mt-3">
                            <h5><i class="icon fas fa-info-circle"></i> Acceso al proyecto</h5>
                            <p>Este proyecto está disponible para todos los usuarios con acceso al sistema. URL pública:</p>
                            <div class="input-group">
                                <input type="text" class="form-control" value="{{ route('proyectos.proxy', $tesis->id) }}" readonly>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary copy-url" type="button">
                                        <i class="fas fa-copy"></i> Copiar
                                    </button>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Información Técnica</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table">
                                <tr>
                                    <th>Tipo de Proyecto</th>
                                    <td>{{ ucfirst($tesis->project_type) }}</td>
                                </tr>
                                <tr>
                                    <th>ID Contenedor</th>
                                    <td><code>{{ substr($tesis->container_id, 0, 12) }}</code></td>
                                </tr>
                                <tr>
                                    <th>Puerto Externo</th>
                                    <td>
                                        @if(!empty($tesis->project_config) && isset($tesis->project_config['external_port']))
                                            <code>{{ $tesis->project_config['external_port'] }}</code>
                                        @else
                                            <span class="text-muted">No asignado</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Puerto Interno</th>
                                    <td>
                                        @if(!empty($tesis->project_config) && isset($tesis->project_config['internal_port']))
                                            <code>{{ $tesis->project_config['internal_port'] }}</code>
                                        @else
                                            <span class="text-muted">No asignado</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Repositorio</th>
                                    <td>
                                        <a href="{{ $tesis->github_repo }}" target="_blank">
                                            {{ basename($tesis->github_repo, '.git') }}
                                            <i class="fas fa-external-link-alt"></i>
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Estado del Contenedor</h3>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-center">
                                <div class="c100 p{{ $tesis->container_status === 'running' ? '100' : '0' }} {{ $tesis->container_status === 'running' ? 'green' : 'orange' }}">
                                    <span>{{ $tesis->container_status === 'running' ? '100%' : '0%' }}</span>
                                    <div class="slice">
                                        <div class="bar"></div>
                                        <div class="fill"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="text-center mt-3">
                                <h5>
                                    Estado: 
                                    <span class="badge badge-{{ $tesis->container_status === 'running' ? 'success' : 'warning' }}">
                                        {{ $tesis->container_status === 'running' ? 'En ejecución' : 'Detenido' }}
                                    </span>
                                </h5>
                                <p>Última actualización: {{ now()->format('d/m/Y H:i:s') }}</p>
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
                    <h3 class="card-title">Monitoreo de Recursos</h3>
                </div>
                <div class="card-body">
                    <p><strong>CPU</strong></p>
                    <div class="progress mb-3">
                        <div class="progress-bar bg-primary" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">25%</div>
                    </div>
                    
                    <p><strong>Memoria</strong></p>
                    <div class="progress mb-3">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 40%" aria-valuenow="40" aria-valuemin="0" aria-valuemax="100">40%</div>
                    </div>
                    
                    <p><strong>Almacenamiento</strong></p>
                    <div class="progress mb-3">
                        <div class="progress-bar bg-info" role="progressbar" style="width: 15%" aria-valuenow="15" aria-valuemin="0" aria-valuemax="100">15%</div>
                    </div>
                    
                    <p><strong>Red</strong></p>
                    <div class="progress mb-3">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 10%" aria-valuenow="10" aria-valuemin="0" aria-valuemax="100">10%</div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <button class="btn btn-sm btn-default refresh-stats">
                            <i class="fas fa-sync"></i> Actualizar
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Actividad Reciente</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="products-list product-list-in-card pl-2 pr-2">
                        <li class="item">
                            <div class="product-info">
                                <a href="javascript:void(0)" class="product-title">Proyecto iniciado
                                    <span class="badge badge-success float-right">Éxito</span>
                                </a>
                                <span class="product-description">
                                    {{ $tesis->last_deployed ? $tesis->last_deployed->format('d/m/Y H:i:s') : 'Fecha desconocida' }}
                                </span>
                            </div>
                        </li>
                        <li class="item">
                            <div class="product-info">
                                <a href="javascript:void(0)" class="product-title">Construcción de imagen
                                    <span class="badge badge-success float-right">Éxito</span>
                                </a>
                                <span class="product-description">
                                    {{ $tesis->last_deployed ? $tesis->last_deployed->subMinutes(2)->format('d/m/Y H:i:s') : 'Fecha desconocida' }}
                                </span>
                            </div>
                        </li>
                        <li class="item">
                            <div class="product-info">
                                <a href="javascript:void(0)" class="product-title">Clonación de repositorio
                                    <span class="badge badge-success float-right">Éxito</span>
                                </a>
                                <span class="product-description">
                                    {{ $tesis->last_deployed ? $tesis->last_deployed->subMinutes(5)->format('d/m/Y H:i:s') : 'Fecha desconocida' }}
                                </span>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    /* Circle Progress Bar */
    .c100 {
        position: relative;
        font-size: 120px;
        width: 1em;
        height: 1em;
        border-radius: 50%;
        float: left;
        margin: 0 0.1em 0.1em 0;
        background-color: #cccccc;
    }
    
    .c100 *,
    .c100 *:before,
    .c100 *:after {
        box-sizing: content-box;
    }
    
    .c100.green .bar,
    .c100.green .fill {
        border-color: #4CAF50 !important;
    }
    
    .c100.green:hover > span {
        color: #4CAF50;
    }
    
    .c100.orange .bar,
    .c100.orange .fill {
        border-color: #FF9800 !important;
    }
    
    .c100.orange:hover > span {
        color: #FF9800;
    }
    
    .c100 > span {
        position: absolute;
        width: 100%;
        z-index: 1;
        left: 0;
        top: 0;
        width: 5em;
        line-height: 5em;
        font-size: 0.2em;
        color: #333333;
        display: block;
        text-align: center;
        white-space: nowrap;
        transition: all 0.2s ease-out;
    }
    
    .c100 .slice {
        position: absolute;
        width: 1em;
        height: 1em;
        clip: rect(0em, 1em, 1em, 0.5em);
    }
    
    .c100.p100 .slice {
        clip: rect(0em, 1em, 1em, 0em);
    }
    
    .c100 .bar {
        position: absolute;
        border: 0.08em solid #307bbb;
        width: 0.84em;
        height: 0.84em;
        clip: rect(0em, 0.5em, 1em, 0em);
        border-radius: 50%;
        transform: rotate(0deg);
    }
    
    .c100.p100 .bar {
        transform: rotate(180deg);
    }
    
    .c100 .fill {
        position: absolute;
        border: 0.08em solid #307bbb;
        width: 0.84em;
        height: 0.84em;
        clip: rect(0em, 0.5em, 1em, 0em);
        border-radius: 50%;
        transform: rotate(180deg);
    }
</style>
@stop

@section('js')
<script>
    $(document).ready(function() {
        // Refresh iframe
        $('#refresh-iframe').on('click', function(e) {
            e.preventDefault();
            $('#project-iframe').attr('src', $('#project-iframe').attr('src'));
        });
        
        // Copy URL to clipboard
        $('.copy-url').on('click', function() {
            var $input = $(this).parent().prev('input');
            $input.select();
            document.execCommand('copy');
            $(this).html('<i class="fas fa-check"></i> Copiado');
            setTimeout(function() {
                $('.copy-url').html('<i class="fas fa-copy"></i> Copiar');
            }, 2000);
        });
        
        // Simulate refresh stats
        $('.refresh-stats').on('click', function() {
            var $button = $(this);
            $button.prop('disabled', true);
            $button.html('<i class="fas fa-spinner fa-spin"></i> Actualizando...');
            
            // Simulate loading
            setTimeout(function() {
                // Generate random values
                var cpu = Math.floor(Math.random() * 70) + 10;
                var memory = Math.floor(Math.random() * 60) + 20;
                var disk = Math.floor(Math.random() * 30) + 5;
                var network = Math.floor(Math.random() * 50) + 5;
                
                // Update progress bars
                $('.progress-bar').eq(0).css('width', cpu + '%').text(cpu + '%');
                $('.progress-bar').eq(1).css('width', memory + '%').text(memory + '%');
                $('.progress-bar').eq(2).css('width', disk + '%').text(disk + '%');
                $('.progress-bar').eq(3).css('width', network + '%').text(network + '%');
                
                // Reset button
                $button.prop('disabled', false);
                $button.html('<i class="fas fa-sync"></i> Actualizar');
            }, 1000);
        });
    });
</script>
@stop
