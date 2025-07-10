@extends('adminlte::page')

@section('title', 'Panel de Monitoreo')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Panel de Monitoreo de Proyectos</h1>
        </div>
        <div class="col-sm-6">
            <div class="float-sm-right">
                <a href="{{ route('tesis.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a tesis
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalProyectos }}</h3>
                    <p>Proyectos Configurados</p>
                </div>
                <div class="icon">
                    <i class="fas fa-code-branch"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $proyectosActivos }}</h3>
                    <p>Proyectos Activos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-rocket"></i>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $proyectosDetenidos }}</h3>
                    <p>Proyectos Detenidos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-pause"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Estado de los Proyectos</h3>
            <div class="card-tools">
                <form action="{{ route('proyectos.monitor') }}" method="GET" class="form-inline">
                    <div class="input-group input-group-sm">
                        <select name="filter" class="form-control mr-2">
                            <option value="all" {{ request('filter') == 'all' ? 'selected' : '' }}>Todos</option>
                            <option value="running" {{ request('filter') == 'running' ? 'selected' : '' }}>En ejecución</option>
                            <option value="stopped" {{ request('filter') == 'stopped' ? 'selected' : '' }}>Detenidos</option>
                        </select>
                        <button type="submit" class="btn btn-default">Filtrar</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tesis</th>
                            <th>Alumno</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Última Actualización</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($proyectos as $proyecto)
                            <tr>
                                <td>{{ $proyecto->id }}</td>
                                <td>
                                    <a href="{{ route('tesis.show', $proyecto->id) }}">
                                        {{ Str::limit($proyecto->titulo, 30) }}
                                    </a>
                                </td>
                                <td>{{ $proyecto->alumno->nombre }} {{ $proyecto->alumno->apellido }}</td>
                                <td>{{ ucfirst($proyecto->project_type) }}</td>
                                <td>
                                    @php
                                        $statusClass = '';
                                        switch($proyecto->container_status) {
                                            case 'running': $statusClass = 'success'; break;
                                            case 'stopped': $statusClass = 'danger'; break;
                                            default: $statusClass = 'secondary'; break;
                                        }
                                    @endphp
                                    <span class="badge badge-{{ $statusClass }}">
                                        {{ ucfirst($proyecto->container_status) }}
                                    </span>
                                </td>
                                <td>{{ $proyecto->last_deployed ? $proyecto->last_deployed->format('d/m/Y H:i:s') : 'N/A' }}</td>
                                <td>
                                    <div class="btn-group">
                                        <a href="{{ route('proyectos.show', $proyecto->id) }}" class="btn btn-sm btn-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        
                                        @if($proyecto->container_status === 'running')
                                            <a href="{{ $proyecto->project_url }}" target="_blank" class="btn btn-sm btn-success">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                            <form action="{{ route('proyectos.stop', $proyecto->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-stop"></i>
                                                </button>
                                            </form>
                                        @elseif($proyecto->container_status === 'stopped')
                                            <form action="{{ route('proyectos.do-deploy', $proyecto->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-success">
                                                    <i class="fas fa-play"></i>
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('proyectos.deploy', $proyecto->id) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-rocket"></i>
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center">No hay proyectos configurados</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $proyectos->links() }}
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Uso de Recursos</h3>
                </div>
                <div class="card-body">
                    <canvas id="resourceChart" height="250"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Distribución por Tipo de Proyecto</h3>
                </div>
                <div class="card-body">
                    <canvas id="projectTypeChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    $(function() {
        // Gráfico de uso de recursos
        var resourceCtx = document.getElementById('resourceChart').getContext('2d');
        var resourceChart = new Chart(resourceCtx, {
            type: 'bar',
            data: {
                labels: ['CPU', 'Memoria', 'Almacenamiento', 'Red'],
                datasets: [{
                    label: 'Uso (%)',
                    data: [
                        {{ $resourceUsage['cpu'] ?? 0 }}, 
                        {{ $resourceUsage['memory'] ?? 0 }}, 
                        {{ $resourceUsage['disk'] ?? 0 }}, 
                        {{ $resourceUsage['network'] ?? 0 }}
                    ],
                    backgroundColor: [
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 206, 86, 0.6)',
                        'rgba(255, 99, 132, 0.6)'
                    ],
                    borderColor: [
                        'rgba(75, 192, 192, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(255, 99, 132, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
        
        // Gráfico de distribución por tipo de proyecto
        var projectTypeCtx = document.getElementById('projectTypeChart').getContext('2d');
        var projectTypeChart = new Chart(projectTypeCtx, {
            type: 'pie',
            data: {
                labels: {!! json_encode($projectTypeLabels) !!},
                datasets: [{
                    data: {!! json_encode($projectTypeCounts) !!},
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 206, 86, 0.6)',
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(153, 102, 255, 0.6)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            }
        });
    });
</script>
@stop
