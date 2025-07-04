@extends('adminlte::page')

@section('title', 'Editar Tesis')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Editar Tesis</h1>
        </div>
        <div class="col-sm-6">
            <a href="{{ route('tesis.index') }}" class="btn btn-secondary float-sm-right">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-body">
            <form action="{{ route('tesis.update', $tesis->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="titulo">Título <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('titulo') is-invalid @enderror" id="titulo" name="titulo" value="{{ old('titulo', $tesis->titulo) }}" required>
                            @error('titulo')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="alumno_id">Alumno <span class="text-danger">*</span></label>
                            <select class="form-control select2 @error('alumno_id') is-invalid @enderror" id="alumno_id" name="alumno_id" required>
                                <option value="">Seleccione un alumno</option>
                                @foreach($alumnos as $alumno)
                                    <option value="{{ $alumno->id }}" {{ old('alumno_id', $tesis->alumno_id) == $alumno->id ? 'selected' : '' }}>
                                        {{ $alumno->nombre }} {{ $alumno->apellido }} - {{ $alumno->carrera->nombre }}
                                    </option>
                                @endforeach
                            </select>
                            @error('alumno_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="tutor_id">Tutor <span class="text-danger">*</span></label>
                            <select class="form-control select2 @error('tutor_id') is-invalid @enderror" id="tutor_id" name="tutor_id" required>
                                <option value="">Seleccione un tutor</option>
                                @foreach($tutores as $tutor)
                                    <option value="{{ $tutor->id }}" {{ old('tutor_id', $tesis->tutor_id) == $tutor->id ? 'selected' : '' }}>
                                        {{ $tutor->nombre }} {{ $tutor->apellido }} - {{ $tutor->especialidad }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tutor_id')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="fecha_inicio">Fecha de Inicio <span class="text-danger">*</span></label>
                            <input type="date" class="form-control @error('fecha_inicio') is-invalid @enderror" id="fecha_inicio" name="fecha_inicio" value="{{ old('fecha_inicio', $tesis->fecha_inicio->format('Y-m-d')) }}" required>
                            @error('fecha_inicio')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="fecha_fin">Fecha de Fin</label>
                            <input type="date" class="form-control @error('fecha_fin') is-invalid @enderror" id="fecha_fin" name="fecha_fin" value="{{ old('fecha_fin', $tesis->fecha_fin ? $tesis->fecha_fin->format('Y-m-d') : '') }}">
                            @error('fecha_fin')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="estado">Estado <span class="text-danger">*</span></label>
                            <select class="form-control @error('estado') is-invalid @enderror" id="estado" name="estado" required>
                                <option value="pendiente" {{ old('estado', $tesis->estado) == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="en_progreso" {{ old('estado', $tesis->estado) == 'en_progreso' ? 'selected' : '' }}>En Progreso</option>
                                <option value="completado" {{ old('estado', $tesis->estado) == 'completado' ? 'selected' : '' }}>Completado</option>
                                <option value="rechazado" {{ old('estado', $tesis->estado) == 'rechazado' ? 'selected' : '' }}>Rechazado</option>
                            </select>
                            @error('estado')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="calificacion">Calificación</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control @error('calificacion') is-invalid @enderror" id="calificacion" name="calificacion" value="{{ old('calificacion', $tesis->calificacion) }}">
                            @error('calificacion')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="documento">Documento PDF</label>
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input @error('documento') is-invalid @enderror" id="documento" name="documento" accept="application/pdf">
                                    <label class="custom-file-label" for="documento">Seleccionar archivo</label>
                                </div>
                            </div>
                            @if ($tesis->documento_url)
                                <div class="mt-2">
                                    <a href="{{ Storage::url($tesis->documento_url) }}" target="_blank" class="btn btn-sm btn-info">
                                        <i class="fas fa-file-pdf"></i> Ver documento actual
                                    </a>
                                </div>
                            @endif
                            @error('documento')
                                <span class="text-danger" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="observaciones">Observaciones</label>
                            <textarea class="form-control @error('observaciones') is-invalid @enderror" id="observaciones" name="observaciones" rows="4">{{ old('observaciones', $tesis->observaciones) }}</textarea>
                            @error('observaciones')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('descripcion') is-invalid @enderror" id="descripcion" name="descripcion" rows="4" required>{{ old('descripcion', $tesis->descripcion) }}</textarea>
                            @error('descripcion')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Actualizar
                        </button>
                        <a href="{{ route('tesis.index') }}" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="{{ asset('vendor/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
@stop

@section('js')
    <script src="{{ asset('vendor/select2/js/select2.full.min.js') }}"></script>
    <script src="{{ asset('vendor/bs-custom-file-input/bs-custom-file-input.min.js') }}"></script>
    <script>
        $(function () {
            //Initialize Select2 Elements
            $('.select2').select2({
                theme: 'bootstrap4'
            });

            // Initialize custom file input
            bsCustomFileInput.init();
        });
    </script>
@stop
