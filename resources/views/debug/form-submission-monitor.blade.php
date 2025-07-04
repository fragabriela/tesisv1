@extends('adminlte::page')

@section('title', 'Monitor de Formularios')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Monitor de Envío de Formularios</h1>
        </div>
        <div class="col-sm-6">
            <a href="{{ route('alumno.index') }}" class="btn btn-secondary float-sm-right">
                <i class="fas fa-arrow-left"></i> Volver a Alumnos
            </a>
        </div>
    </div>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Monitor de Formularios en Tiempo Real</h3>
        </div>
        <div class="card-body">
            <div class="alert alert-info">
                <i class="fas fa-info-circle"></i> 
                Esta herramienta muestra información sobre envíos recientes de formularios para ayudar a diagnosticar problemas.
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-header">
                            <h4 class="card-title">Últimos Envíos</h4>
                        </div>
                        <div class="card-body" id="form-submissions">
                            <div class="d-flex justify-content-center">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Cargando...</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card bg-light">
                        <div class="card-header">
                            <h4 class="card-title">Herramientas de Prueba</h4>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <h5>Probar Actualización de Alumno</h5>
                                <form id="test-update-form" action="{{ route('debug.test.alumno.update') }}" method="POST">
                                    @csrf
                                    <div class="form-group">
                                        <label for="alumno_id">Seleccionar Alumno</label>
                                        <select name="alumno_id" id="alumno_id" class="form-control">
                                            <option value="">Seleccionar alumno...</option>
                                            <!-- Se llenará vía AJAX -->
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="field">Campo a modificar</label>
                                        <select name="field" id="field" class="form-control">
                                            <option value="nombre">Nombre</option>
                                            <option value="apellido">Apellido</option>
                                            <option value="telefono">Teléfono</option>
                                        </select>
                                    </div>
                                    <div class="form-group">
                                        <label for="value">Nuevo valor</label>
                                        <input type="text" name="value" id="value" class="form-control" placeholder="Nuevo valor">
                                    </div>
                                    <button type="submit" class="btn btn-primary">Probar Actualización</button>
                                </form>
                            </div>
                            
                            <div>
                                <h5>Estado de la Base de Datos</h5>
                                <a href="{{ route('debug.database.info') }}" class="btn btn-info">Ver Estructura DB</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4 bg-light">
                <div class="card-header">
                    <h4 class="card-title">Resultados de Pruebas</h4>
                </div>
                <div class="card-body">
                    <div id="test-results">
                        <div class="alert alert-secondary">
                            No hay resultados de pruebas aún. Realiza una prueba para ver resultados.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
    pre {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 4px;
        padding: 10px;
        max-height: 400px;
        overflow: auto;
    }
    .submission-entry {
        border-bottom: 1px solid #dee2e6;
        padding: 10px 0;
    }
    .submission-entry:last-child {
        border-bottom: none;
    }
</style>
@stop

@section('js')
<script>
    $(function () {
        // Cargar alumnos para el select
        $.ajax({
            url: '{{ route("debug.get.alumnos") }}',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                var select = $('#alumno_id');
                if (data.alumnos && data.alumnos.length > 0) {
                    $.each(data.alumnos, function(index, alumno) {
                        select.append($('<option></option>').val(alumno.id).text(alumno.nombre + ' ' + alumno.apellido));
                    });
                } else {
                    select.append($('<option></option>').val('').text('No hay alumnos disponibles'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Error cargando alumnos:', error);
                $('#alumno_id').append($('<option></option>').val('').text('Error cargando alumnos'));
            }
        });
        
        // Cargar envíos recientes
        loadRecentSubmissions();
        
        // Manejar el envío de formulario de prueba
        $('#test-update-form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var submitBtn = form.find('button[type="submit"]');
            var originalBtnText = submitBtn.html();
            
            submitBtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Probando...');
            submitBtn.prop('disabled', true);
            
            $.ajax({
                url: form.attr('action'),
                type: 'POST',
                data: form.serialize(),
                dataType: 'json',
                success: function(data) {
                    var resultHtml = '<div class="alert alert-success">' + 
                        '<h5><i class="icon fas fa-check"></i> ¡Prueba exitosa!</h5>' +
                        '<p>La actualización se completó correctamente.</p>' +
                        '<pre>' + JSON.stringify(data, null, 2) + '</pre>' +
                        '</div>';
                    $('#test-results').html(resultHtml);
                    
                    // Recargar envíos recientes
                    loadRecentSubmissions();
                },
                error: function(xhr, status, error) {
                    var errorData = xhr.responseJSON || { error: 'Error desconocido' };
                    var resultHtml = '<div class="alert alert-danger">' + 
                        '<h5><i class="icon fas fa-ban"></i> Error en la prueba</h5>' +
                        '<p>' + (errorData.message || error) + '</p>' +
                        '<pre>' + JSON.stringify(errorData, null, 2) + '</pre>' +
                        '</div>';
                    $('#test-results').html(resultHtml);
                },
                complete: function() {
                    submitBtn.html(originalBtnText);
                    submitBtn.prop('disabled', false);
                }
            });
        });
    });
    
    function loadRecentSubmissions() {
        $.ajax({
            url: '{{ route("debug.get.form.submissions") }}',
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                var submissionsHtml = '';
                
                if (data.submissions && data.submissions.length > 0) {
                    $.each(data.submissions, function(index, submission) {
                        submissionsHtml += '<div class="submission-entry">' +
                            '<h5>' + submission.route + ' <small class="text-muted">' + submission.method + '</small></h5>' +
                            '<p><strong>Tiempo:</strong> ' + submission.time + '</p>' +
                            '<p><strong>IP:</strong> ' + submission.ip + '</p>' +
                            '<button class="btn btn-sm btn-info mb-2" type="button" data-toggle="collapse" data-target="#submission-data-' + index + '">Ver datos</button>' +
                            '<div class="collapse" id="submission-data-' + index + '">' +
                            '<pre>' + JSON.stringify(submission.data, null, 2) + '</pre>' +
                            '</div>' +
                            '</div>';
                    });
                } else {
                    submissionsHtml = '<div class="alert alert-warning">No hay envíos de formularios recientes.</div>';
                }
                
                $('#form-submissions').html(submissionsHtml);
            },
            error: function(xhr, status, error) {
                $('#form-submissions').html('<div class="alert alert-danger">' +
                    '<i class="fas fa-exclamation-circle"></i> Error cargando envíos: ' + error +
                    '</div>');
            }
        });
    }
</script>
@stop
