@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Panel de Control</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-lg-3 col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>{{ $totalAlumnos }}</h3>
                    <p>Alumnos</p>
                </div>
                <div class="icon">
                    <i class="fas fa-user-graduate"></i>
                </div>                <a href="{{ route('alumno.index') }}" class="small-box-footer">
                    Más información <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $totalTesis }}</h3>
                    <p>Tesis</p>
                </div>
                <div class="icon">
                    <i class="fas fa-book"></i>
                </div>                <a href="{{ route('tesis.index') }}" class="small-box-footer">
                    Más información <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $totalTutores }}</h3>
                    <p>Tutores</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <a href="{{ route('tutor.index') }}" class="small-box-footer">
                    Más información <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $totalCarreras }}</h3>
                    <p>Carreras</p>
                </div>
                <div class="icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>                <a href="{{ route('carrera.index') }}" class="small-box-footer">
                    Más información <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Tesis por Estado</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="tesisByStatusChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Alumnos por Carrera</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <canvas id="alumnosByCarreraChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card card-success">
                <div class="card-header">
                    <h3 class="card-title">Tesis Recientes</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <ul class="products-list product-list-in-card pl-2 pr-2">
                        @forelse($recentTesis as $tesis)
                            <li class="item">
                                <div class="product-img">
                                    <i class="fas fa-book fa-2x text-info"></i>
                                </div>
                                <div class="product-info">
                                    <a href="#" class="product-title">
                                        {{ $tesis->titulo }}
                                        <span class="badge 
                                            @if($tesis->estado == 'pendiente') badge-warning 
                                            @elseif($tesis->estado == 'en_progreso') badge-info 
                                            @elseif($tesis->estado == 'completado') badge-success 
                                            @else badge-danger 
                                            @endif
                                            float-right">
                                            {{ ucfirst(str_replace('_', ' ', $tesis->estado)) }}
                                        </span>
                                    </a>
                                    <span class="product-description">
                                        Alumno: {{ $tesis->alumno->nombre }} {{ $tesis->alumno->apellido }} | 
                                        Tutor: {{ $tesis->tutor->nombre }} {{ $tesis->tutor->apellido }}
                                    </span>
                                </div>
                            </li>
                        @empty
                            <li class="item">
                                <div class="product-info">
                                    <span class="product-description text-center">
                                        No hay tesis recientes
                                    </span>
                                </div>
                            </li>
                        @endforelse
                    </ul>
                </div>
                <div class="card-footer text-center">
                    <a href="#" class="uppercase">Ver todas las tesis</a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-warning">
                <div class="card-header">
                    <h3 class="card-title">Top Tutores</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <ul class="users-list clearfix">
                        @foreach($topTutores as $tutor)
                            <li>
                                <img src="{{ asset('vendor/adminlte/dist/img/user1-128x128.jpg') }}" alt="User Image">
                                <a class="users-list-name" href="{{ route('tutor.show', $tutor->id) }}">{{ $tutor->nombre }} {{ $tutor->apellido }}</a>
                                <span class="users-list-date">{{ $tutor->total_tesis }} tesis</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-footer text-center">
                    <a href="{{ route('tutor.index') }}">Ver todos los tutores</a>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <style>
        /* Desktop view */
        .users-list > li {
            width: 33.33%;
        }
        
        /* Tablet view */
        @media (max-width: 991.98px) {
            .users-list > li {
                width: 50%;
            }
        }
        
        /* Mobile view */
        @media (max-width: 767.98px) {
            .users-list > li {
                width: 100%;
            }
            
            .product-info .product-title {
                font-size: 14px;
            }
            
            .product-description {
                font-size: 12px;
            }
            
            .small-box h3 {
                font-size: 25px;
            }
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tesis por estado chart
            const tesisByStatusColors = {
                'pendiente': '#ffc107',
                'en_progreso': '#17a2b8',
                'completado': '#28a745',
                'rechazado': '#dc3545'
            };
            
            const tesisByStatusData = {
                labels: [
                    'Pendiente',
                    'En Progreso',
                    'Completado',
                    'Rechazado'
                ],
                datasets: [{
                    data: [
                        {{ $tesisByStatus['pendiente'] ?? 0 }},
                        {{ $tesisByStatus['en_progreso'] ?? 0 }},
                        {{ $tesisByStatus['completado'] ?? 0 }},
                        {{ $tesisByStatus['rechazado'] ?? 0 }}
                    ],
                    backgroundColor: Object.values(tesisByStatusColors),
                    hoverBackgroundColor: Object.values(tesisByStatusColors)
                }]
            };
            
            new Chart(document.getElementById('tesisByStatusChart'), {
                type: 'doughnut',
                data: tesisByStatusData,
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            });
            
            // Alumnos por carrera chart
            const alumnosByCarreraLabels = @json($alumnosByCarrera->pluck('nombre'));
            const alumnosByCarreraData = @json($alumnosByCarrera->pluck('total'));
            
            new Chart(document.getElementById('alumnosByCarreraChart'), {
                type: 'bar',
                data: {
                    labels: alumnosByCarreraLabels,
                    datasets: [{
                        label: 'Número de alumnos',
                        data: alumnosByCarreraData,
                        backgroundColor: 'rgba(60, 141, 188, 0.8)',
                        borderColor: 'rgba(60, 141, 188, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        });
    </script>
@stop
