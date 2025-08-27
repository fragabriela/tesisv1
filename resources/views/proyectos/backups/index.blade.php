@extends('adminlte::page')

@section('title', 'Gestión de Backups')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Gestión de Backups</h1>
            <small class="text-muted">Proyecto: {{ $tesis->titulo }}</small>
        </div>
        <div class="col-sm-6">
            <div class="float-sm-right">
                <a href="{{ route('proyectos.show', $tesis->id) }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver al proyecto
                </a>
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#createBackupModal">
                    <i class="fas fa-plus"></i> Crear Backup
                </button>
            </div>
        </div>
    </div>
@stop

@section('content')
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <i class="fas fa-exclamation-circle"></i> {{ session('error') }}
        </div>
    @endif

    <div class="row">
        <!-- Información del proyecto -->
        <div class="col-md-4">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Información del Proyecto</h3>
                </div>
                <div class="card-body">
                    <dl class="row">
                        <dt class="col-sm-5">Título:</dt>
                        <dd class="col-sm-7">{{ $tesis->titulo }}</dd>
                        
                        <dt class="col-sm-5">Estado:</dt>
                        <dd class="col-sm-7">
                            <span class="badge badge-{{ $tesis->container_status === 'running' ? 'success' : 'warning' }}">
                                {{ $tesis->container_status === 'running' ? 'En ejecución' : 'Detenido' }}
                            </span>
                        </dd>
                        
                        @if($tesis->container_id)
                            <dt class="col-sm-5">Container ID:</dt>
                            <dd class="col-sm-7">
                                <code>{{ Str::limit($tesis->container_id, 12) }}</code>
                            </dd>
                        @endif
                        
                        @if($tesis->container_port)
                            <dt class="col-sm-5">Puerto:</dt>
                            <dd class="col-sm-7">{{ $tesis->container_port }}</dd>
                        @endif
                        
                        <dt class="col-sm-5">Total Backups:</dt>
                        <dd class="col-sm-7">
                            <span class="badge badge-info">{{ $backups->count() }}</span>
                        </dd>
                    </dl>
                </div>
            </div>

            <!-- Estadísticas -->
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">Estadísticas</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <strong>{{ $backups->where('type', 'full')->count() }}</strong>
                                <div class="text-muted">Completos</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <strong>{{ $backups->where('type', 'database')->count() }}</strong>
                                <div class="text-muted">Base de Datos</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de backups -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Backups Disponibles</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" id="refresh-backups">
                            <i class="fas fa-sync"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    @if($backups->count() > 0)
                        <table class="table table-hover text-nowrap">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Descripción</th>
                                    <th>Tipo</th>
                                    <th>Tamaño</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($backups as $backup)
                                    <tr>
                                        <td>
                                            <span class="text-muted">{{ $backup->created_at->format('d/m/Y') }}</span><br>
                                            <small>{{ $backup->created_at->format('H:i:s') }}</small>
                                        </td>
                                        <td>
                                            <strong>{{ $backup->description }}</strong>
                                            @if($backup->version)
                                                <br><small class="text-muted">v{{ $backup->version }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge badge-{{ $backup->type === 'full' ? 'primary' : 'secondary' }}">
                                                {{ ucfirst($backup->type) }}
                                            </span>
                                        </td>
                                        <td>
                                            @if($backup->file_size)
                                                {{ $backup->formatted_size }}
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($backup->file_exists)
                                                <span class="badge badge-success">Disponible</span>
                                            @else
                                                <span class="badge badge-danger">Archivo faltante</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <div class="dropdown-menu">
                                                    @if($backup->file_exists)
                                                        <a class="dropdown-item" href="{{ route('proyectos.backups.download', [$tesis->id, $backup->id]) }}">
                                                            <i class="fas fa-download"></i> Descargar
                                                        </a>
                                                        <div class="dropdown-divider"></div>
                                                        <a class="dropdown-item text-warning" href="#" 
                                                           onclick="confirmRestore({{ $backup->id }}, '{{ $backup->description }}')">
                                                            <i class="fas fa-undo"></i> Restaurar
                                                        </a>
                                                        <div class="dropdown-divider"></div>
                                                    @endif
                                                    <a class="dropdown-item" href="{{ route('proyectos.backups.show', [$tesis->id, $backup->id]) }}">
                                                        <i class="fas fa-eye"></i> Ver detalles
                                                    </a>
                                                    <div class="dropdown-divider"></div>
                                                    <a class="dropdown-item text-danger" href="#" 
                                                       onclick="confirmDelete({{ $backup->id }}, '{{ $backup->description }}')">
                                                        <i class="fas fa-trash"></i> Eliminar
                                                    </a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-4">
                            <i class="fas fa-archive fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay backups disponibles</h5>
                            <p class="text-muted">Crea tu primer backup para comenzar.</p>
                            <button type="button" class="btn btn-success" data-toggle="modal" data-target="#createBackupModal">
                                <i class="fas fa-plus"></i> Crear primer backup
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop

<!-- Modal para crear backup -->
<div class="modal fade" id="createBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('proyectos.backups.store', $tesis->id) }}" method="POST" id="createBackupForm">
                @csrf
                <div class="modal-header">
                    <h4 class="modal-title">Crear Nuevo Backup</h4>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="description">Descripción *</label>
                        <input type="text" class="form-control" id="description" name="description" 
                               placeholder="Ej: Backup antes de actualización" required maxlength="255">
                        <small class="form-text text-muted">Describe el propósito de este backup.</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="type">Tipo de Backup</label>
                        <select class="form-control" id="type" name="type">
                            <option value="full" selected>Completo (Archivos + Base de datos)</option>
                            <option value="database">Solo Base de datos</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="version">Versión (opcional)</label>
                        <input type="text" class="form-control" id="version" name="version" 
                               placeholder="Ej: 1.0.0" maxlength="50">
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i>
                        <strong>Nota:</strong> El proceso de backup puede tomar varios minutos dependiendo del tamaño del proyecto.
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Crear Backup
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@stop

@section('css')
<style>
    .card-header .card-tools .badge {
        font-size: 0.875rem;
    }
    .table td {
        vertical-align: middle;
    }
    .btn-group-sm > .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
    }
</style>
@stop

@section('js')
<script>
    // Actualizar lista de backups
    $('#refresh-backups').click(function() {
        location.reload();
    });

    // Confirmación para restaurar backup
    function confirmRestore(backupId, description) {
        Swal.fire({
            title: '¿Restaurar Backup?',
            html: `
                <div class="text-left">
                    <p><strong>Backup:</strong> ${description}</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>¡Atención!</strong> Esta acción:
                        <ul class="mt-2">
                            <li>Detendrá el contenedor actual</li>
                            <li>Restaurará los archivos del backup</li>
                            <li>Reiniciará el proyecto</li>
                            <li>Puede tomar varios minutos</li>
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
                return fetch(`{{ route('proyectos.backups.restore', [$tesis->id, ':backupId']) }}`.replace(':backupId', backupId), {
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
                    confirmButtonText: 'Entendido'
                }).then(() => {
                    location.reload();
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
    function confirmDelete(backupId, description) {
        Swal.fire({
            title: '¿Eliminar Backup?',
            html: `<strong>Backup:</strong> ${description}`,
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
                form.action = `{{ route('proyectos.backups.destroy', [$tesis->id, ':backupId']) }}`.replace(':backupId', backupId);
                
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

    // Validación del formulario
    $('#createBackupForm').on('submit', function() {
        const submitBtn = $(this).find('button[type="submit"]');
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creando...');
        
        setTimeout(() => {
            if (submitBtn.prop('disabled')) {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Crear Backup');
            }
        }, 30000); // Reset after 30 seconds if something goes wrong
    });
</script>
@stop