@extends('adminlte::page')

@section('title', 'Crear Documento')

@section('content_header')
    <h1>Crear Nuevo Documento</h1>
@stop

@section('content')
<!-- Mensajes de alerta -->
@if(session('success'))
    <div class="alert alert-success alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-check"></i> ¡Éxito!</h5>
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

@if($errors->any())
    <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-ban"></i> Errores de validación</h5>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Información del Documento</h3>
            </div>
            <form action="{{ route('documento.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="titulo">Título del Documento <small class="text-muted">(opcional)</small></label>
                                <input type="text" class="form-control @error('titulo') is-invalid @enderror" 
                                       id="titulo" name="titulo" value="{{ old('titulo') }}" 
                                       placeholder="Se generará automáticamente si está vacío">
                                @error('titulo')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                                <small class="form-text text-muted">Si subes un archivo Word, se usará su nombre como título</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="tesis_id">Tesis Asociada <span class="text-danger">*</span></label>
                                @if($tesis->count() > 0)
                                    <select class="form-control @error('tesis_id') is-invalid @enderror" 
                                            id="tesis_id" name="tesis_id" required>
                                        <option value="">Seleccionar Tesis</option>
                                        @foreach($tesis as $t)
                                            <option value="{{ $t->id }}" 
                                                {{ (old('tesis_id', $tesisSeleccionada ?? '') == $t->id) ? 'selected' : '' }}>
                                                {{ $t->titulo }} - {{ $t->alumno->nombre }} {{ $t->alumno->apellido }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('tesis_id')
                                        <span class="invalid-feedback">{{ $message }}</span>
                                    @enderror
                                @else
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <strong>No hay tesis disponibles.</strong><br>
                                        @if(auth()->user()->hasRole('alumno'))
                                            Como alumno, solo puedes crear documentos para tus propias tesis. 
                                            Contacta a tu coordinador si necesitas que se te asigne una tesis.
                                        @elseif(auth()->user()->hasRole('tutor'))
                                            Como tutor, solo puedes crear documentos para las tesis donde eres tutor asignado.
                                        @else
                                            No hay tesis registradas en el sistema.
                                        @endif
                                    </div>
                                    <input type="hidden" name="tesis_id" value="">
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="descripcion">Descripción</label>
                                <textarea class="form-control @error('descripcion') is-invalid @enderror" 
                                          id="descripcion" name="descripcion" rows="3">{{ old('descripcion') }}</textarea>
                                @error('descripcion')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="form-group">
                                <label for="archivo_word">Archivo Word (Opcional)</label>
                                <div class="input-group">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input @error('archivo_word') is-invalid @enderror" 
                                               id="archivo_word" name="archivo_word" accept=".doc,.docx">
                                        <label class="custom-file-label" for="archivo_word">Seleccionar archivo...</label>
                                    </div>
                                </div>
                                <small class="form-text text-muted">
                                    Formatos soportados: DOC, DOCX. Tamaño máximo: 10MB.
                                    Si carga un archivo, el contenido se convertirá automáticamente para edición en línea.
                                </small>
                                @error('archivo_word')
                                    <span class="invalid-feedback">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info-circle"></i> Información:</h5>
                        <ul class="mb-0">
                            <li>Una vez creado el documento, podrá editarlo en línea usando nuestro editor colaborativo</li>
                            <li>Los profesores podrán agregar comentarios y sugerencias directamente en el texto</li>
                            <li>Los estudiantes recibirán notificaciones de nuevos comentarios</li>
                            <li>Se mantendrá un historial de versiones del documento</li>
                        </ul>
                    </div>
                </div>

                <div class="card-footer">
                    @if($tesis->count() > 0)
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Crear Documento
                        </button>
                    @else
                        <button type="button" class="btn btn-primary" disabled>
                            <i class="fas fa-save"></i> Crear Documento
                        </button>
                        <small class="text-muted ml-2">No se puede crear un documento sin una tesis asociada</small>
                    @endif
                    <a href="{{ route('documento.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Actualizar label del archivo cuando se selecciona
            $('.custom-file-input').on('change', function() {
                let fileName = $(this).val().split('\\').pop();
                $(this).siblings('.custom-file-label').addClass('selected').html(fileName);
            });

            // Mostrar información de alumno y tutor cuando se selecciona una tesis
            $('#tesis_id').on('change', function() {
                let selectedOption = $(this).find('option:selected');
                if (selectedOption.val()) {
                    // Aquí podrías mostrar más información de la tesis seleccionada
                    console.log('Tesis seleccionada: ' + selectedOption.text());
                }
            });
        });
    </script>
@stop