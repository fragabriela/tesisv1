@extends('adminlte::page')

@section('title', 'Crear Nuevo Proyecto')

@section('content_header')
    <h1>Crear Nuevo Proyecto</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Nuevo Proyecto de Tesis</h3>
        </div>
        <div class="card-body">
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                    <h5><i class="icon fas fa-ban"></i> Error</h5>
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('proyectos.store') }}" method="POST">
                @csrf
                
                <div class="form-group">
                    <label for="titulo">Título del Proyecto *</label>
                    <input type="text" class="form-control" id="titulo" name="titulo" value="{{ old('titulo') }}" required>
                </div>
                
                <div class="form-group">
                    <label for="descripcion">Descripción *</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required>{{ old('descripcion') }}</textarea>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="fecha_inicio">Fecha de Inicio *</label>
                            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="{{ old('fecha_inicio', date('Y-m-d')) }}" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="fecha_fin">Fecha de Finalización</label>
                            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="{{ old('fecha_fin') }}">
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="alumno_id">Alumno *</label>
                            <select class="form-control" id="alumno_id" name="alumno_id" required>
                                <option value="">Seleccione un alumno</option>
                                @foreach($alumnos as $alumno)
                                    <option value="{{ $alumno->id }}" {{ old('alumno_id') == $alumno->id ? 'selected' : '' }}>
                                        {{ $alumno->nombre }} {{ $alumno->apellido }} ({{ $alumno->matricula }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="tutor_id">Tutor *</label>
                            <select class="form-control" id="tutor_id" name="tutor_id" required>
                                <option value="">Seleccione un tutor</option>
                                @foreach($tutores as $tutor)
                                    <option value="{{ $tutor->id }}" {{ old('tutor_id') == $tutor->id ? 'selected' : '' }}>
                                        {{ $tutor->nombre }} {{ $tutor->apellido }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="estado">Estado *</label>                            <select class="form-control" id="estado" name="estado" required>
                                <option value="pendiente" {{ old('estado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                                <option value="en_progreso" {{ old('estado') == 'en_progreso' ? 'selected' : '' }}>En Progreso</option>
                                <option value="completado" {{ old('estado') == 'completado' ? 'selected' : '' }}>Completado</option>
                                <option value="rechazado" {{ old('estado') == 'rechazado' ? 'selected' : '' }}>Rechazado</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="is_visible">Visibilidad</label>
                            <div class="custom-control custom-switch mt-2">
                                <input type="checkbox" class="custom-control-input" id="is_visible" name="is_visible" value="1" {{ old('is_visible', 1) ? 'checked' : '' }}>
                                <label class="custom-control-label" for="is_visible">Proyecto visible</label>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="observaciones">Observaciones</label>
                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3">{{ old('observaciones') }}</textarea>
                </div>
                
                <div class="form-group">
                    <label for="github_repo">URL del Repositorio GitHub <small class="text-muted">(opcional)</small></label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="github_repo" name="github_repo" 
                               placeholder="https://github.com/username/repo.git" value="{{ old('github_repo') }}">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary" type="button" id="check-github-url">
                                <i class="fas fa-check"></i> Verificar
                            </button>
                        </div>
                    </div>
                    <div id="github-url-feedback"></div>
                    <small class="form-text text-muted">
                        Si ya tienes un repositorio GitHub para este proyecto, puedes indicarlo aquí. También podrás configurarlo después de crear el proyecto.
                    </small>
                </div>
                
                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">Guardar Proyecto</button>
                    <a href="{{ route('proyectos.index') }}" class="btn btn-secondary">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Inicializar Select2 si está disponible
            if ($.fn.select2) {
                $('#alumno_id, #tutor_id').select2({
                    placeholder: 'Seleccione una opción',
                    width: '100%'
                });
            }
            
            // Validación del formato de URL de GitHub
            $('form').submit(function(e) {
                var githubUrl = $('#github_repo').val();
                if (githubUrl && !githubUrl.match(/^https:\/\/github\.com\/[^\/]+\/[^\/]+(.git)?$/i)) {
                    e.preventDefault();
                    $('#github-url-feedback').html('<div class="text-danger mt-2"><i class="fas fa-times-circle"></i> El formato de la URL no es válido. Debe ser https://github.com/username/repo</div>');
                    return false;
                }
            });
              // Verificar URL de GitHub con AJAX
            $('#check-github-url').click(function() {
                var url = $('#github_repo').val();
                if (!url) {
                    $('#github-url-feedback').html('<div class="text-warning mt-2"><i class="fas fa-exclamation-triangle"></i> Introduce una URL para verificar</div>');
                    return;
                }
                
                // Verificar formato básico antes de la petición AJAX
                if (!url.match(/^https:\/\/github\.com\/[^\/]+\/[^\/]+(.git)?$/i)) {
                    $('#github-url-feedback').html('<div class="text-danger mt-2"><i class="fas fa-times-circle"></i> El formato de la URL no es válido. Debe ser https://github.com/username/repo</div>');
                    return;
                }
                
                // Mostrar indicador de carga
                $('#github-url-feedback').html('<div class="text-info mt-2"><i class="fas fa-spinner fa-spin"></i> Verificando repositorio...</div>');
                
                // Realizar la verificación AJAX
                $.ajax({
                    url: '{{ route("proyectos.check-github-url") }}',
                    type: 'POST',
                    data: {
                        url: url,
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        if (response.valid) {
                            var repoDetails = '';
                            
                            if (response.repoDetails) {
                                var privacy = response.repoDetails.isPrivate ? 'Privado' : 'Público';
                                var description = response.repoDetails.description ? response.repoDetails.description : 'Sin descripción';
                                
                                repoDetails = '<div class="mt-2"><strong>Nombre:</strong> ' + response.repoDetails.fullName + 
                                              '<br><strong>Descripción:</strong> ' + description +
                                              '<br><strong>Tipo:</strong> ' + privacy + '</div>';
                            }
                            
                            $('#github-url-feedback').html(
                                '<div class="alert alert-success mt-2">' +
                                '<i class="fas fa-check-circle"></i> ' + response.message + repoDetails +
                                '</div>'
                            );
                        } else {
                            $('#github-url-feedback').html(
                                '<div class="alert alert-danger mt-2">' +
                                '<i class="fas fa-times-circle"></i> ' + response.message +
                                '<hr><strong>Sugerencias:</strong>' +
                                '<ul>' +
                                '<li>Verifica que el repositorio exista y sea público.</li>' +
                                '<li>Comprueba que has escrito correctamente el nombre de usuario y repositorio.</li>' +
                                '<li>Si el repositorio es privado, asegúrate de tener permisos y usar HTTPS para clonar.</li>' +
                                '</ul></div>'
                            );
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#github-url-feedback').html(
                            '<div class="alert alert-danger mt-2">' +
                            '<i class="fas fa-times-circle"></i> Error al verificar el repositorio. ' +
                            'Detalle: ' + error +
                            '</div>'
                        );
                    }
                });
            });
        });
    </script>
@stop
