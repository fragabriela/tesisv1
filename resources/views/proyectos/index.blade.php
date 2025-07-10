@extends('adminlte::page')

@section('title', 'Gestión de Proyectos')

@section('content_header')
    <h1>Gestión de Proyectos</h1>
@stop

@section('content')    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Proyectos de Tesis</h3>
            @can('crear proyectos')
                <div class="float-right">
                    <a href="{{ route('proyectos.create') }}" class="btn btn-success">
                        <i class="fas fa-plus"></i> Nuevo Proyecto
                    </a>
                </div>
            @endcan
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-check"></i> Éxito</h5>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> Error</h5>
                    {{ session('error') }}
                </div>
            @endif
            
            <div class="alert alert-info alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <h5><i class="icon fas fa-info"></i> Flujo de Trabajo de Proyectos</h5>
                <ol>
                    <li><strong>Crear Proyecto</strong>: Añade la información básica y opcionalmente la URL de GitHub.</li>
                    <li><strong>Configurar GitHub</strong>: Vincula un repositorio GitHub al proyecto.</li>
                    <li><strong>Configurar Proyecto</strong>: Clona y detecta el tipo de proyecto.</li>
                    <li><strong>Desplegar</strong>: Pone en marcha el proyecto en un contenedor.</li>
                </ol>
            </div>

            <table id="proyectos-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>                        <th>Título</th>
                        <th>Alumno</th>
                        <th>Tutor</th>
                        <th>Estado del Proyecto</th>
                        <th>Visibilidad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($proyectos as $proyecto)
                        <tr>
                            <td>{{ $proyecto->id }}</td>
                            <td>{{ $proyecto->titulo }}</td>
                            <td>{{ optional($proyecto->alumno)->nombre }} {{ optional($proyecto->alumno)->apellido }}</td>
                            <td>{{ optional($proyecto->tutor)->nombre }} {{ optional($proyecto->tutor)->apellido }}</td>
                            <td>
                                @if($proyecto->container_status == 'running')
                                    <span class="badge badge-success">En ejecución</span>
                                @elseif($proyecto->container_status == 'stopped')
                                    <span class="badge badge-warning">Detenido</span>
                                @elseif($proyecto->container_status == 'removed')
                                    <span class="badge badge-danger">Eliminado</span>
                                @elseif($proyecto->github_repo)
                                    <span class="badge badge-info">Configurado</span>                                @else
                                    <span class="badge badge-secondary">Sin configurar</span>
                                @endif
                            </td>
                            <td>
                                @if($proyecto->is_visible)
                                    <span class="badge badge-success">Visible</span>
                                @else
                                    <span class="badge badge-warning">No Visible</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group">
                                    @can('ver proyectos')
                                        @if($proyecto->container_status == 'running')
                                            <a href="{{ route('proyectos.show', $proyecto->id) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> Ver proyecto
                                            </a>
                                        @endif
                                    @endcan

                                    @can('configurar proyectos')
                                        @if(!$proyecto->github_repo)
                                            <a href="{{ route('proyectos.github-config', $proyecto->id) }}" class="btn btn-sm btn-primary">
                                                <i class="fab fa-github"></i> Configurar GitHub
                                            </a>
                                        @elseif(!$proyecto->project_repo_path)
                                            <a href="{{ route('proyectos.setup', $proyecto->id) }}" class="btn btn-sm btn-primary">
                                                <i class="fas fa-cogs"></i> Configurar proyecto
                                            </a>
                                        @endif
                                    @endcan

                                    @can('desplegar proyectos')
                                        @if($proyecto->project_repo_path && $proyecto->container_status != 'running')
                                            <a href="{{ route('proyectos.deploy', $proyecto->id) }}" class="btn btn-sm btn-success">
                                                <i class="fas fa-rocket"></i> Desplegar
                                            </a>
                                        @endif
                                    @endcan

                                    @can('ver proyectos')
                                        @if($proyecto->container_status == 'running')
                                            <a href="{{ route('proyectos.logs', $proyecto->id) }}" class="btn btn-sm btn-secondary">
                                                <i class="fas fa-list"></i> Logs
                                            </a>
                                        @endif
                                    @endcan                                    @can('gestionar proyectos')
                                        @if($proyecto->container_status == 'running')
                                            <form action="{{ route('proyectos.stop', $proyecto->id) }}" method="POST" style="display: inline-block;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-warning" onclick="return confirm('¿Está seguro de detener este proyecto?')">
                                                    <i class="fas fa-stop"></i> Detener
                                                </button>
                                            </form>
                                        @elseif($proyecto->container_status == 'stopped')
                                            <form action="{{ route('proyectos.restart', $proyecto->id) }}" method="POST" style="display: inline-block;">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-play"></i> Reiniciar
                                                </button>
                                            </form>
                                        @endif
                                    @endcan
                                    
                                    @can('configurar proyectos')
                                        <form action="{{ route('proyectos.toggle-visibility', $proyecto->id) }}" method="POST" style="display: inline-block;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm {{ $proyecto->is_visible ? 'btn-dark' : 'btn-light' }}">
                                                <i class="fas {{ $proyecto->is_visible ? 'fa-eye-slash' : 'fa-eye' }}"></i> 
                                                {{ $proyecto->is_visible ? 'Ocultar' : 'Mostrar' }}
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No hay proyectos configurados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @can('monitorear proyectos')
        <div class="mt-4">
            <a href="{{ route('proyectos.monitor') }}" class="btn btn-lg btn-primary">
                <i class="fas fa-tachometer-alt"></i> Panel de Monitoreo
            </a>
        </div>
    @endcan
@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap4.min.css">
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#proyectos-table').DataTable({
                "paging": true,
                "lengthChange": true,
                "searching": true,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json"
                }
            });
        });
    </script>
@stop
