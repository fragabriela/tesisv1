@extends('adminlte::page')

@section('title', 'Detalles del Alumno')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Detalles del Alumno</h1>
        </div>
        <div class="col-sm-6">
            <div class="float-sm-right">
                <a href="{{ route('alumno.edit', $alumno->id) }}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Editar
                </a>
                <a href="{{ route('alumno.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-4">
            <div class="card card-primary card-outline">
                <div class="card-body box-profile">
                    <div class="text-center">
                        <i class="fas fa-user-graduate fa-5x text-primary"></i>
                    </div>

                    <h3 class="profile-username text-center">{{ $alumno->nombre }} {{ $alumno->apellido }}</h3>
                    <p class="text-muted text-center">{{ $alumno->carrera->nombre }}</p>

                    <ul class="list-group list-group-unbordered mb-3">
                        <li class="list-group-item">
                            <b>Matrícula</b> <a class="float-right">{{ $alumno->matricula }}</a>
                        </li>
                        <li class="list-group-item">
                            <b>Cédula</b> <a class="float-right">{{ $alumno->cedula }}</a>
                        </li>
                        <li class="list-group-item">
                            <b>Estado</b> 
                            <a class="float-right">
                                @if($alumno->estado == 'activo')
                                    <span class="badge badge-success">Activo</span>
                                @else
                                    <span class="badge badge-danger">Inactivo</span>
                                @endif
                            </a>
                        </li>
                        <li class="list-group-item">
                            <b>Fecha de Nacimiento</b> <a class="float-right">{{ $alumno->fecha_nacimiento->format('d/m/Y') }}</a>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Información de Contacto</h3>
                </div>
                <div class="card-body">
                    <strong><i class="fas fa-envelope mr-1"></i> Email</strong>
                    <p class="text-muted">{{ $alumno->email }}</p>
                    <hr>
                    <strong><i class="fas fa-phone mr-1"></i> Teléfono</strong>
                    <p class="text-muted">{{ $alumno->telefono }}</p>
                    <hr>
                    <strong><i class="fas fa-map-marker-alt mr-1"></i> Dirección</strong>
                    <p class="text-muted">{{ $alumno->direccion ?? 'No registrada' }}</p>
                </div>
            </div>
        </div>
        
        <div class="col-md-8">
            <div class="card">
                <div class="card-header p-2">
                    <h3 class="card-title">Tesis</h3>
                </div>
                <div class="card-body">
                    @if($alumno->tesis->isEmpty())
                        <div class="alert alert-info">
                            <i class="icon fas fa-info"></i> Este alumno no tiene tesis registradas.
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>Título</th>
                                        <th>Tutor</th>
                                        <th>Estado</th>
                                        <th>Fecha Inicio</th>
                                        <th>Calificación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($alumno->tesis as $tesis)
                                        <tr>
                                            <td>{{ $tesis->titulo }}</td>
                                            <td>{{ $tesis->tutor->nombre }} {{ $tesis->tutor->apellido }}</td>
                                            <td>
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
                                            </td>
                                            <td>{{ $tesis->fecha_inicio->format('d/m/Y') }}</td>
                                            <td>{{ $tesis->calificacion ?? 'N/A' }}</td>
                                            <td>
                                                <a href="{{ route('tesis.show', $tesis->id) }}" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                    
                    <div class="mt-3">
                        <a href="{{ route('tesis.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Crear Nueva Tesis
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Información Adicional</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Fecha de Registro</strong>
                            <p>{{ $alumno->created_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                        <div class="col-md-6">
                            <strong>Última Actualización</strong>
                            <p>{{ $alumno->updated_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
