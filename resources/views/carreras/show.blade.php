@extends('adminlte::page')

@section('title', 'Detalles de Carrera')

@section('content_header')
    <h1>Detalles de Carrera</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Información de la Carrera</h3>
                    <div class="card-tools">
                        <a href="{{ route('carrera.edit', $carrera->id) }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <button type="button" class="btn btn-danger btn-sm" onclick="eliminarCarrera({{ $carrera->id }})">
                            <i class="fas fa-trash"></i> Eliminar
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-bordered">
                                <tr>
                                    <th style="width: 200px;">ID</th>
                                    <td>{{ $carrera->id }}</td>
                                </tr>
                                <tr>
                                    <th>Nombre</th>
                                    <td>{{ $carrera->nombre }}</td>
                                </tr>
                                <tr>
                                    <th>Descripción</th>
                                    <td>{{ $carrera->descripcion }}</td>
                                </tr>
                                <tr>
                                    <th>Estado</th>
                                    <td>
                                        @if($carrera->activo)
                                            <span class="badge badge-success">Activo</span>
                                        @else
                                            <span class="badge badge-danger">Inactivo</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <th>Fecha de Creación</th>
                                    <td>{{ $carrera->created_at->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Última Actualización</th>
                                    <td>{{ $carrera->updated_at->format('d/m/Y H:i') }}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="{{ route('carrera.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Alumnos en esta Carrera</h3>
                </div>
                <div class="card-body">
                    @if($carrera->alumnos->count() > 0)
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Matrícula</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($carrera->alumnos as $index => $alumno)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $alumno->nombre }} {{ $alumno->apellido }}</td>
                                        <td>{{ $alumno->email }}</td>
                                        <td>{{ $alumno->matricula }}</td>
                                        <td>
                                            @if($alumno->estado == 'activo')
                                                <span class="badge badge-success">Activo</span>
                                            @else
                                                <span class="badge badge-danger">Inactivo</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('alumno.show', $alumno->id) }}" class="btn btn-info btn-sm">Ver</a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <p>No hay alumnos registrados en esta carrera.</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
    <script>
        function eliminarCarrera(id) {
            if(confirm('¿Estás seguro de que deseas eliminar esta carrera? Esta acción no se puede deshacer.')) {
                $.ajax({
                    url: `/carrera/${id}`,
                    type: 'DELETE',
                    data: {
                        "_token": "{{ csrf_token() }}",
                    },
                    success: function(response) {
                        if(response.success) {
                            toastr.success(response.message);
                            window.location.href = "{{ route('carrera.index') }}";
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function() {
                        toastr.error('Ha ocurrido un error al eliminar la carrera');
                    }
                });
            }
        }
    </script>
@stop
