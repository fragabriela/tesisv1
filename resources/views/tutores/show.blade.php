@extends('adminlte::page')

@section('title', 'Detalle de Tutor')

@section('content_header')
    <h1>Detalle de Tutor</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between">
                <h3 class="card-title">Información del Tutor</h3>
                <div>
                    <a href="{{ route('tutor.edit', $tutor->id) }}" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Editar
                    </a>
                    <a href="{{ route('tutor.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <dl class="row">
                        <dt class="col-sm-4">Nombre completo:</dt>
                        <dd class="col-sm-8">{{ $tutor->nombre }} {{ $tutor->apellido }}</dd>
                        
                        <dt class="col-sm-4">Email:</dt>
                        <dd class="col-sm-8">{{ $tutor->email }}</dd>
                        
                        <dt class="col-sm-4">Teléfono:</dt>
                        <dd class="col-sm-8">{{ $tutor->telefono }}</dd>
                        
                        <dt class="col-sm-4">Especialidad:</dt>
                        <dd class="col-sm-8">{{ $tutor->especialidad }}</dd>
                        
                        <dt class="col-sm-4">Estado:</dt>
                        <dd class="col-sm-8">
                            @if($tutor->activo)
                                <span class="badge badge-success">Activo</span>
                            @else
                                <span class="badge badge-danger">Inactivo</span>
                            @endif
                        </dd>
                        
                        <dt class="col-sm-4">Fecha de registro:</dt>
                        <dd class="col-sm-8">{{ $tutor->created_at->format('d/m/Y H:i') }}</dd>
                    </dl>
                </div>
                <div class="col-md-6">
                    <h5>Biografía</h5>
                    <p>{{ $tutor->biografia ?? 'No hay biografía disponible' }}</p>
                </div>
            </div>
            
            @if($tutor->tesis->count() > 0)
            <div class="mt-4">
                <h4>Tesis supervisadas</h4>
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Alumno</th>
                            <th>Estado</th>
                            <th>Fecha de inicio</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tutor->tesis as $tesis)
                        <tr>
                            <td>{{ $tesis->id }}</td>
                            <td>{{ $tesis->titulo }}</td>
                            <td>{{ $tesis->alumno->nombre }} {{ $tesis->alumno->apellido }}</td>
                            <td>
                                @switch($tesis->estado)
                                    @case('pendiente')
                                        <span class="badge badge-warning">Pendiente</span>
                                        @break
                                    @case('en_progreso')
                                        <span class="badge badge-info">En progreso</span>
                                        @break
                                    @case('completado')
                                        <span class="badge badge-success">Completado</span>
                                        @break
                                    @case('rechazado')
                                        <span class="badge badge-danger">Rechazado</span>
                                        @break
                                @endswitch
                            </td>
                            <td>{{ $tesis->fecha_inicio->format('d/m/Y') }}</td>
                            <td>
                                <a href="#" class="btn btn-sm btn-info">Ver</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>
@stop
