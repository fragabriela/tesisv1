@extends('adminlte::page')

@section('title', 'Diagnóstico de Docker')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Diagnóstico de Docker</h1>
        </div>
        <div class="col-sm-6">
            <div class="float-sm-right">
                <a href="{{ route('proyectos.monitor') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al Monitor
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Estado de Docker en el Sistema</h3>
                </div>
                <div class="card-body">
                    @if($dockerInstalled)
                        <div class="alert alert-success">
                            <h5><i class="icon fas fa-check"></i> Docker Instalado</h5>
                            <p>Docker está correctamente instalado en el sistema: <strong>{{ $dockerVersion }}</strong></p>
                        </div>
                    @else
                        <div class="alert alert-danger">
                            <h5><i class="icon fas fa-times"></i> Docker No Instalado</h5>
                            <p>Docker no está instalado o no es accesible en el sistema.</p>
                        </div>
                    @endif
                    
                    @if($dockerRunning)
                        <div class="alert alert-success">
                            <h5><i class="icon fas fa-check"></i> Docker en Ejecución</h5>
                            <p>El daemon de Docker está en ejecución correctamente.</p>
                        </div>
                    @else
                        <div class="alert alert-danger">
                            <h5><i class="icon fas fa-times"></i> Docker No Está en Ejecución</h5>
                            <p>El daemon de Docker no está en ejecución. Inicie Docker Desktop o el servicio Docker.</p>
                        </div>
                    @endif
                    
                    @if($composeAvailable)
                        <div class="alert alert-success">
                            <h5><i class="icon fas fa-check"></i> Docker Compose Disponible</h5>
                            <p>Docker Compose está correctamente instalado: <strong>{{ $composeVersion }}</strong></p>
                        </div>
                    @else
                        <div class="alert alert-danger">
                            <h5><i class="icon fas fa-times"></i> Docker Compose No Disponible</h5>
                            <p>Docker Compose no está disponible en el sistema.</p>
                        </div>
                    @endif
                    
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h3 class="card-title">Solución de Problemas</h3>
                        </div>
                        <div class="card-body">                            @if(!$dockerInstalled)
                                <div class="mb-4">
                                    <h5>Instalar Docker</h5>
                                    <div class="alert alert-info">
                                        <p><i class="fas fa-info-circle mr-1"></i> Hemos creado una guía detallada de instalación para ayudarle:</p>
                                        <a href="{{ route('proyectos.docker-install') }}" class="btn btn-info btn-lg btn-block">
                                            <i class="fas fa-book-open mr-1"></i> Ver Guía de Instalación Completa
                                        </a>
                                    </div>
                                    <p>Pasos básicos:</p>
                                    <ol>
                                        <li>Descargue Docker Desktop desde <a href="https://www.docker.com/products/docker-desktop/" target="_blank">https://www.docker.com/products/docker-desktop/</a></li>
                                        <li>Siga las instrucciones de instalación para su sistema operativo</li>
                                        <li>Asegúrese de que su usuario tenga permisos para ejecutar Docker</li>
                                    </ol>
                                </div>
                            @endif
                            
                            @if($dockerInstalled && !$dockerRunning)
                                <div class="mb-4">
                                    <h5>Iniciar Docker</h5>
                                    <ol>
                                        <li>Abra Docker Desktop desde el menú de inicio</li>
                                        <li>Espere a que el servicio se inicie completamente</li>
                                        <li>Si hay errores durante el inicio, consulte los logs de Docker para más detalles</li>
                                    </ol>
                                </div>
                            @endif
                            
                            @if(!$composeAvailable && $dockerInstalled)
                                <div class="mb-4">
                                    <h5>Configurar Docker Compose</h5>
                                    <ol>
                                        <li>Docker Compose debería venir incluido con Docker Desktop</li>
                                        <li>Si está usando Docker Engine sin Docker Desktop, instale Docker Compose separadamente siguiendo las instrucciones en <a href="https://docs.docker.com/compose/install/" target="_blank">https://docs.docker.com/compose/install/</a></li>
                                        <li>Asegúrese de que Docker Compose esté en su PATH del sistema</li>
                                    </ol>
                                </div>
                            @endif
                            
                            @if($dockerInstalled && $dockerRunning && $composeAvailable)
                                <div class="alert alert-success">
                                    <h5><i class="icon fas fa-check"></i> ¡Todo Correcto!</h5>
                                    <p>Docker y Docker Compose están correctamente instalados y configurados. Puede desplegar proyectos sin problemas.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
