@extends('adminlte::page')

@section('title', 'Crear Carrera')

@section('content_header')
    <h1>Crear Nueva Carrera</h1>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Información de la Carrera</h3>
                </div>
                
                <form action="{{ route('carrera.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="card-body">
                        <!-- Debug info -->
                        <div class="alert alert-info">
                            <p><strong>Debug Info:</strong></p>
                            <p>Form action: {{ route('carrera.store') }}</p>
                            <p>CSRF token: {{ csrf_token() }}</p>
                        </div>
                        
                        <div class="form-group">
                            <label for="nombre">Nombre</label>
                            <input type="text" class="form-control @error('nombre') is-invalid @enderror" id="nombre" name="nombre" value="{{ old('nombre') }}" required>
                            @error('nombre')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion" rows="4" required>{{ old('descripcion') }}</textarea>
                            @error('descripcion')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="activo" name="activo" value="1" checked>
                                <label class="custom-control-label" for="activo">Activo</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                        <a href="{{ route('carrera.index') }}" class="btn btn-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Client-side validation
            $('form').on('submit', function(e) {
                let nombre = $('#nombre').val().trim();
                let descripcion = $('#descripcion').val().trim();
                
                if (nombre === '') {
                    e.preventDefault();
                    $('#nombre').addClass('is-invalid');
                    $('<span class="invalid-feedback">El nombre es obligatorio</span>').insertAfter('#nombre');
                }
                
                if (descripcion === '') {
                    e.preventDefault();
                    $('#descripcion').addClass('is-invalid');
                    $('<span class="invalid-feedback">La descripción es obligatoria</span>').insertAfter('#descripcion');
                }
            });
            
            // Clear validation on input
            $('#nombre, #descripcion').on('input', function() {
                $(this).removeClass('is-invalid');
                $(this).next('.invalid-feedback').remove();
            });
        });
    </script>
@stop
