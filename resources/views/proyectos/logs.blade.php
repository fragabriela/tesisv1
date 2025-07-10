@extends('adminlte::page')

@section('title', 'Logs del Proyecto')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Logs del Proyecto</h1>
        </div>
        <div class="col-sm-6">
            <div class="float-sm-right">
                <a href="{{ route('proyectos.show', $tesis->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al proyecto
                </a>
                <button id="refresh-logs" class="btn btn-primary">
                    <i class="fas fa-sync"></i> Actualizar
                </button>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Logs del Contenedor</h3>
                    <div class="card-tools">
                        <span class="badge badge-{{ $tesis->container_status === 'running' ? 'success' : 'danger' }}">
                            {{ ucfirst($tesis->container_status) }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="icon fas fa-info-circle"></i> Información del Contenedor</h5>
                        <p><strong>ID:</strong> <code>{{ $tesis->container_id }}</code></p>
                        <p><strong>Tipo de Proyecto:</strong> {{ ucfirst($tesis->project_type) }}</p>
                        <p><strong>Última Actualización:</strong> {{ $tesis->last_deployed ? $tesis->last_deployed->format('d/m/Y H:i:s') : 'Nunca' }}</p>
                    </div>
                    
                    <div class="form-group">
                        <label>Filtro de Logs:</label>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" class="form-control" id="log-filter" placeholder="Filtrar logs...">
                        </div>
                    </div>
                    
                    <div class="log-container bg-dark p-3 text-light" style="height: 500px; overflow: auto; font-family: monospace; font-size: 0.9rem;">
                        @foreach($logs as $log)
                            <div class="log-line">{{ $log }}</div>
                        @endforeach
                    </div>
                </div>
                <div class="card-footer">
                    <div class="float-right">
                        <button class="btn btn-default" id="clear-logs">
                            <i class="fas fa-eraser"></i> Limpiar Filtros
                        </button>
                        <button class="btn btn-default" id="download-logs">
                            <i class="fas fa-download"></i> Descargar Logs
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop

@section('js')
<script>
$(document).ready(function() {
    // Filtrar logs
    $('#log-filter').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.log-line').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // Limpiar filtros
    $('#clear-logs').click(function() {
        $('#log-filter').val('');
        $('.log-line').show();
    });
    
    // Actualizar logs
    $('#refresh-logs').click(function() {
        window.location.reload();
    });
    
    // Descargar logs
    $('#download-logs').click(function() {
        var logs = '';
        $('.log-line').each(function() {
            logs += $(this).text() + "\n";
        });
        
        var blob = new Blob([logs], { type: 'text/plain' });
        var link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = 'container-logs-{{ $tesis->id }}.txt';
        link.click();
    });
    
    // Scroll al final de los logs
    var logContainer = $('.log-container');
    logContainer.scrollTop(logContainer[0].scrollHeight);
    
    // Destacar errores y advertencias
    $('.log-line').each(function() {
        var text = $(this).text().toLowerCase();
        if (text.indexOf('error') !== -1 || text.indexOf('exception') !== -1) {
            $(this).addClass('text-danger');
        } else if (text.indexOf('warning') !== -1 || text.indexOf('warn') !== -1) {
            $(this).addClass('text-warning');
        } else if (text.indexOf('info') !== -1) {
            $(this).addClass('text-info');
        }
    });
});
</script>
@stop

@section('css')
<style>
    .log-line {
        line-height: 1.5;
        white-space: pre-wrap;
        word-break: break-all;
    }
    
    .log-container::-webkit-scrollbar {
        width: 8px;
    }
    
    .log-container::-webkit-scrollbar-track {
        background: #343a40;
    }
    
    .log-container::-webkit-scrollbar-thumb {
        background: #6c757d;
        border-radius: 4px;
    }
    
    .log-container::-webkit-scrollbar-thumb:hover {
        background: #5a6268;
    }
</style>
@stop
