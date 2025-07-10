@extends('adminlte::page')

@section('title', 'Error del Contenedor')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Error del Contenedor</h1>
        </div>
        <div class="col-sm-6">
            <div class="float-sm-right">
                @if(isset($tesis))
                    <a href="{{ route('tesis.show', $tesis->id) }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a la tesis
                    </a>
                    
                    <a href="{{ route('proyectos.deploy', $tesis->id) }}" class="btn btn-primary">
                        <i class="fas fa-rocket"></i> Gestionar proyecto
                    </a>
                @else
                    <a href="{{ route('tesis.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver a tesis
                    </a>
                @endif
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="error-page">
        <h2 class="headline text-warning">503</h2>

        <div class="error-content">
            <h3><i class="fas fa-exclamation-triangle text-warning"></i> ¡Oops! Algo salió mal.</h3>

            <p>
                {{ $error ?? 'El proyecto no está disponible en este momento.' }}
            </p>

            @if(isset($tesis))
                <div class="alert alert-warning">
                    <h5><i class="icon fas fa-exclamation-triangle"></i> Estado del proyecto</h5>
                    <p>ID del contenedor: <code>{{ substr($tesis->container_id, 0, 12) }}</code></p>
                    <p>Estado: <span class="badge badge-warning">{{ ucfirst($tesis->container_status ?? 'desconocido') }}</span></p>
                    <p>Última actualización: {{ $tesis->last_deployed ? $tesis->last_deployed->format('d/m/Y H:i:s') : 'Nunca' }}</p>
                </div>
                
                <div class="text-center mt-4">
                    <a href="{{ route('proyectos.deploy', $tesis->id) }}" class="btn btn-warning">
                        <i class="fas fa-rocket"></i> Ir a la página de despliegue
                    </a>
                </div>
            @endif
        </div>
    </div>
@stop
