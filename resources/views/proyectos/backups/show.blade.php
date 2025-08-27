@extends('adminlte::page')

@section('title', 'Detalles del Backup')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Detalles del Backup</h1>
            <small class="text-muted">{{ $backup->description }}</small>
        </div>
        <div class="col-sm-6">
            <div class="float-sm-right">
                <a href="{{ route('proyectos.backups.index', $tesis->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a backups
                </a>
                @if($backup->file_exists)
                    <a href="{{ route('proyectos.backups.download', [$tesis->id, $backup->id]) }}" class="btn btn-success">
                        <i class="fas fa-download"></i> Descargar
                    </a>
                @endif
            </div>
        </div>
    </div>
@stop

@section('content')
    <div class="row">
        <!-- Información del backup -->
        <div class="col-md-6">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Información General</h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Descripción:</dt>
                        <dd class="col-sm-8">{{ $backup->description }}</dd>
                        
                        <dt class="col-sm-4">Tipo:</dt>
                        <dd class="col-sm-8">
                            <span class="badge badge-{{ $backup->type === 'full' ? 'primary' : 'secondary' }}">
                                {{ $backup->type === 'full' ? 'Completo' : 'Base de datos' }}
                            </span>
                        </dd>
                        
                        @if($backup->version)
                            <dt class="col-sm-4">Versión:</dt>
                            <dd class="col-sm-8">{{ $backup->version }}</dd>
                        @endif
                        
                        <dt class="col-sm-4">Creado:</dt>
                        <dd class="col-sm-8">
                            {{ $backup->created_at->format('d/m/Y H:i:s') }}
                            <br><small class="text-muted">{{ $backup->created_at->diffForHumans() }}</small>
                        </dd>
                        
                        <dt class="col-sm-4">Archivo:</dt>
                        <dd class="col-sm-8">
                            @if($backup->file_exists)
                                <span class="badge badge-success">
                                    <i class="fas fa-check"></i> Disponible
                                </span>
                                <br><code>{{ $backup->file_path }}</code>
                            @else
                                <span class="badge badge-danger">
                                    <i class="fas fa-times"></i> No encontrado
                                </span>
                            @endif
                        </dd>
                        
                        @if($backup->file_size)
                            <dt class="col-sm-4">Tamaño:</dt>
                            <dd class="col-sm-8">
                                {{ $backup->formatted_size }}
                                <small class="text-muted">({{ number_format($backup->file_size) }} bytes)</small>
                            </dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <!-- Información del proyecto -->
        <div class="col-md-6">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Proyecto Asociado</h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-4">Título:</dt>
                        <dd class="col-sm-8">{{ $tesis->titulo }}</dd>
                        
                        <dt class="col-sm-4">Alumno:</dt>
                        <dd class="col-sm-8">
                            {{ $tesis->alumno->nombres }} {{ $tesis->alumno->apellidos }}
                        </dd>
                        
                        <dt class="col-sm-4">Estado:</dt>
                        <dd class="col-sm-8">
                            <span class="badge badge-{{ $tesis->container_status === 'running' ? 'success' : 'warning' }}">
                                {{ $tesis->container_status === 'running' ? 'En ejecución' : 'Detenido' }}
                            </span>
                        </dd>
                        
                        @if($tesis->container_id)
                            <dt class="col-sm-4">Container ID:</dt>
                            <dd class="col-sm-8">
                                <code>{{ Str::limit($tesis->container_id, 12) }}</code>
                            </dd>
                        @endif
                    </dl>

                    <div class="mt-3">
                        <a href="{{ route('proyectos.show', $tesis->id) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye"></i> Ver proyecto
                        </a>
                        <a href="{{ route('tesis.show', $tesis->id) }}" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-file-alt"></i> Ver tesis
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($backup->file_exists)
        <!-- Acciones -->
        <div class="row">
            <div class="col-12">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">Acciones Disponibles</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="text-center">
                                    <a href="{{ route('proyectos.backups.download', [$tesis->id, $backup->id]) }}" class="btn btn-success btn-lg">
                                        <i class="fas fa-download fa-2x"></i>
                                        <br><strong>Descargar</strong>
                                        <br><small>Archivo ZIP</small>
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <button type="button" class="btn btn-warning btn-lg" onclick="confirmRestore()">
                                        <i class="fas fa-undo fa-2x"></i>
                                        <br><strong>Restaurar</strong>
                                        <br><small>Al contenedor</small>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <button type="button" class="btn btn-danger btn-lg" onclick="confirmDelete()">
                                        <i class="fas fa-trash fa-2x"></i>
                                        <br><strong>Eliminar</strong>
                                        <br><small>Permanente</small>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle"></i>
                            <strong>Información:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>Descargar:</strong> Descarga el archivo ZIP del backup a tu computadora.</li>
                                <li><strong>Restaurar:</strong> Restaura este backup al contenedor Docker actual (detiene el proyecto temporalmente).</li>
                                <li><strong>Eliminar:</strong> Elimina permanentemente este backup del servidor.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-12">
                <div class="card card-danger">
                    <div class="card-header">
                        <h3 class="card-title">Archivo No Disponible</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i>
                            <strong>¡Atención!</strong> El archivo de este backup no se encuentra disponible.
                            Esto puede deberse a que:
                        </div>
                        <ul>
                            <li>El archivo fue movido o eliminado del servidor</li>
                            <li>Hubo un error durante la creación del backup</li>
                            <li>El espacio de almacenamiento fue limpiado</li>
                        </ul>
                        
                        <div class="mt-3">
                            <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                                <i class="fas fa-trash"></i> Eliminar registro
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@stop

@section('css')
<style>
    .btn-lg i.fa-2x {
        display: block;
        margin-bottom: 0.5rem;
    }
    .card .dl-horizontal dt {
        text-align: left;
    }
</style>
@stop

@section('js')
<script>
    // Confirmación para restaurar backup
    function confirmRestore() {
        Swal.fire({
            title: '¿Restaurar este Backup?',
            html: `
                <div class="text-left">
                    <p><strong>Backup:</strong> {{ $backup->description }}</p>
                    <p><strong>Fecha:</strong> {{ $backup->created_at->format('d/m/Y H:i:s') }}</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>¡Atención!</strong> Esta acción:
                        <ul class="mt-2">
                            <li>Detendrá el contenedor actual</li>
                            <li>Restaurará los archivos del backup</li>
                            <li>Reiniciará el proyecto</li>
                            <li>Puede tomar varios minutos</li>
                            <li>Se perderán los cambios no guardados</li>
                        </ul>
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f39c12',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-undo"></i> Sí, restaurar',
            cancelButtonText: 'Cancelar',
            showLoaderOnConfirm: true,
            preConfirm: () => {
                return fetch('{{ route('proyectos.backups.restore', [$tesis->id, $backup->id]) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                }).then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la restauración');
                    }
                    return response.json();
                });
            },
            allowOutsideClick: () => !Swal.isLoading()
        }).then((result) => {
            if (result.isConfirmed) {
                Swal.fire({
                    title: '¡Restauración Completada!',
                    text: 'El backup se ha restaurado exitosamente.',
                    icon: 'success',
                    confirmButtonText: 'Ver proyecto'
                }).then(() => {
                    window.location.href = '{{ route('proyectos.show', $tesis->id) }}';
                });
            }
        }).catch(error => {
            Swal.fire({
                title: 'Error',
                text: 'Hubo un problema al restaurar el backup.',
                icon: 'error'
            });
        });
    }

    // Confirmación para eliminar backup
    function confirmDelete() {
        Swal.fire({
            title: '¿Eliminar este Backup?',
            html: `
                <div class="text-left">
                    <p><strong>Backup:</strong> {{ $backup->description }}</p>
                    <p><strong>Fecha:</strong> {{ $backup->created_at->format('d/m/Y H:i:s') }}</p>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>¡Atención!</strong> Esta acción es irreversible.
                        El backup se eliminará permanentemente del servidor.
                    </div>
                </div>
            `,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="fas fa-trash"></i> Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route('proyectos.backups.destroy', [$tesis->id, $backup->id]) }}';
                
                const methodInput = document.createElement('input');
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';
                
                const tokenInput = document.createElement('input');
                tokenInput.type = 'hidden';
                tokenInput.name = '_token';
                tokenInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                
                form.appendChild(methodInput);
                form.appendChild(tokenInput);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
@stop