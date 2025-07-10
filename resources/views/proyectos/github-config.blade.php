@extends('adminlte::page')

@section('title', 'Configurar Repositorio GitHub')

@section('content_header')    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Configurar Repositorio GitHub para Proyecto</h1>
        </div>
        <div class="col-sm-6">
            <div class="float-sm-right">
                <a href="{{ route('proyectos.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a lista de proyectos
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Repositorio GitHub</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('proyectos.save-github-config', $tesis->id) }}" method="POST">
                        @csrf
                          <div class="form-group">
                            <label for="github_repo">URL del Repositorio GitHub</label>
                            <div class="input-group">
                                <input type="text" name="github_repo" id="github_repo" class="form-control @error('github_repo') is-invalid @enderror" 
                                    placeholder="https://github.com/username/repo.git" 
                                    value="{{ old('github_repo', $tesis->github_repo) }}">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="check-github-url">
                                        <i class="fas fa-check"></i> Verificar
                                    </button>
                                </div>
                            </div>
                            
                            @error('github_repo')
                                <span class="invalid-feedback">{{ $message }}</span>
                            @enderror
                            
                            <div id="github-url-feedback" class="mt-2"></div>
                            
                            <small class="form-text text-muted">
                                Introduce la URL completa del repositorio GitHub (formato: https://github.com/username/repo). Asegúrate de que sea un repositorio público o que tengas permisos de acceso.
                            </small>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <i class="fab fa-github"></i> Guardar Configuración
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Instrucciones</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info"></i> ¿Qué es un repositorio GitHub?</h5>
                        <p>GitHub es una plataforma de desarrollo colaborativo para alojar proyectos utilizando el sistema de control de versiones Git.</p>
                    </div>
                    
                    <h5>Pasos para configurar tu proyecto:</h5>
                    <ol>
                        <li>Crea un repositorio en GitHub para tu proyecto.</li>
                        <li>Sube el código de tu proyecto al repositorio.</li>
                        <li>Copia la URL del repositorio (termina en .git).</li>
                        <li>Pega la URL en el campo de arriba y guarda la configuración.</li>
                    </ol>
                    
                    <div class="alert alert-warning">
                        <h5><i class="icon fas fa-exclamation-triangle"></i> Importante</h5>
                        <p>Para proyectos de Laravel, asegúrate de incluir los archivos .env.example y composer.json en tu repositorio.</p>
                        <p>Para proyectos de Java, incluye el pom.xml (Maven) o build.gradle (Gradle) según corresponda.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Información de la Tesis</h3>
                </div>
                <div class="card-body">
                    <dl>
                        <dt>Título</dt>
                        <dd>{{ $tesis->titulo }}</dd>
                        
                        <dt>Alumno</dt>
                        <dd>{{ $tesis->alumno->nombre }} {{ $tesis->alumno->apellido }}</dd>
                        
                        <dt>Tutor</dt>
                        <dd>{{ $tesis->tutor->nombre }} {{ $tesis->tutor->apellido }}</dd>
                        
                        <dt>Estado</dt>
                        <dd>
                            @php
                                $badgeClass = '';
                                switch($tesis->estado) {
                                    case 'pendiente': $badgeClass = 'warning'; break;
                                    case 'en_progreso': $badgeClass = 'info'; break;
                                    case 'completado': $badgeClass = 'success'; break;
                                    case 'rechazado': $badgeClass = 'danger'; break;
                                }
                            @endphp
                            <span class="badge badge-{{ $badgeClass }}">
                                {{ ucfirst(str_replace('_', ' ', $tesis->estado)) }}
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
    $(document).ready(function() {
        // Script para validar formato de URL de GitHub
        $('form').submit(function(e) {
            var githubUrl = $('#github_repo').val();
            if (githubUrl && !githubUrl.match(/^https:\/\/github\.com\/[^\/]+\/[^\/]+(.git)?$/i)) {
                e.preventDefault();
                $('#github-url-feedback').html('<div class="alert alert-danger"><i class="fas fa-times-circle"></i> El formato de la URL no es válido. Debe ser https://github.com/username/repo</div>');
                return false;
            }
        });
        
        // Verificar URL de GitHub con AJAX
        $('#check-github-url').click(function() {
            var url = $('#github_repo').val();
            if (!url) {
                $('#github-url-feedback').html('<div class="alert alert-warning"><i class="fas fa-exclamation-triangle"></i> Introduce una URL para verificar</div>');
                return;
            }
            
            // Verificar formato básico antes de la petición AJAX
            if (!url.match(/^https:\/\/github\.com\/[^\/]+\/[^\/]+(.git)?$/i)) {
                $('#github-url-feedback').html('<div class="alert alert-danger"><i class="fas fa-times-circle"></i> El formato de la URL no es válido. Debe ser https://github.com/username/repo</div>');
                return;
            }
            
            // Mostrar indicador de carga
            $('#github-url-feedback').html('<div class="text-info"><i class="fas fa-spinner fa-spin"></i> Verificando repositorio...</div>');
            
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
                            '<div class="alert alert-success">' +
                            '<i class="fas fa-check-circle"></i> ' + response.message + repoDetails +
                            '</div>'
                        );
                    } else {
                        $('#github-url-feedback').html(
                            '<div class="alert alert-danger">' +
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
                        '<div class="alert alert-danger">' +
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
