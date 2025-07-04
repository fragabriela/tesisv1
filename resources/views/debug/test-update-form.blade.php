@extends('adminlte::page')

@section('title', 'Test Update Form')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Test Update Form</h1>
        </div>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Form Method Test</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <p>This is a test form to debug the update functionality</p>
            </div>
            
            <form id="normalForm" action="/debug/log-form-data" method="POST">
                @csrf
                <div class="form-group">
                    <label>Normal POST Form</label>
                    <input type="text" class="form-control" name="test_value" value="Test Value">
                </div>
                <button type="submit" class="btn btn-primary mb-4">Submit POST</button>
            </form>
            
            <hr>
            
            <form id="putForm" action="/debug/log-form-data" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Form with PUT Method</label>
                    <input type="text" class="form-control" name="test_value" value="Test PUT Value">
                </div>
                <button type="submit" class="btn btn-warning mb-4">Submit PUT</button>
            </form>
            
            <hr>
            
            <div class="card bg-light">
                <div class="card-header">
                    AJAX Tests
                </div>
                <div class="card-body">
                    <button id="testAjaxPut" class="btn btn-success">Test AJAX PUT</button>
                    <div id="ajaxResult" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h3 class="card-title">Test Alumno Update</h3>
        </div>
        <div class="card-body">
            @if(isset($alumno))
            <form id="alumnoUpdateForm" action="{{ route('alumno.update', ['alumno' => $alumno->id]) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="form-group">
                    <label>Nombre</label>
                    <input type="text" class="form-control" name="nombre" value="{{ $alumno->nombre }} (updated)">
                </div>
                <div class="form-group">
                    <label>Apellido</label>
                    <input type="text" class="form-control" name="apellido" value="{{ $alumno->apellido }} (updated)">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" class="form-control" name="email" value="{{ $alumno->email }}">
                </div>
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" class="form-control" name="telefono" value="{{ $alumno->telefono }}">
                </div>
                <div class="form-group">
                    <label>Cédula</label>
                    <input type="text" class="form-control" name="cedula" value="{{ $alumno->cedula }}">
                </div>
                <div class="form-group">
                    <label>Matrícula</label>
                    <input type="text" class="form-control" name="matricula" value="{{ $alumno->matricula }}">
                </div>
                <div class="form-group">
                    <label>Fecha de Nacimiento</label>
                    <input type="date" class="form-control" name="fecha_nacimiento" value="{{ is_object($alumno->fecha_nacimiento) ? $alumno->fecha_nacimiento->format('Y-m-d') : $alumno->fecha_nacimiento }}">
                </div>
                <div class="form-group">
                    <label>Carrera</label>
                    <select class="form-control" name="id_carrera">
                        @foreach($carreras as $carrera)
                            <option value="{{ $carrera->id }}" {{ $alumno->id_carrera == $carrera->id ? 'selected' : '' }}>
                                {{ $carrera->nombre }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label>Estado</label>
                    <select class="form-control" name="estado">
                        <option value="activo" {{ $alumno->estado == 'activo' ? 'selected' : '' }}>Activo</option>
                        <option value="inactivo" {{ $alumno->estado == 'inactivo' ? 'selected' : '' }}>Inactivo</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update Alumno</button>
            </form>
            @else
            <div class="alert alert-warning">
                No alumno data provided for testing
            </div>
            @endif
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    $('#testAjaxPut').click(function() {
        $('#ajaxResult').html('<div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div>');
        
        $.ajax({
            url: '/debug/log-form-data',
            type: 'PUT',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                test_ajax: 'AJAX PUT Test',
                timestamp: new Date().getTime()
            },
            success: function(response) {
                $('#ajaxResult').html('<div class="alert alert-success">Success! Check logs for details.</div>');
                console.log(response);
            },
            error: function(error) {
                $('#ajaxResult').html('<div class="alert alert-danger">Error: ' + JSON.stringify(error) + '</div>');
                console.error(error);
            }
        });
    });
});
</script>
@stop
