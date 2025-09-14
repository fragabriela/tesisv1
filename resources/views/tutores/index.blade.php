@extends('adminlte::page')

@section('title', 'Gestión de Tutores')

@section('content_header')
    <h1>Gestión de Tutores</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between">
                <h3 class="card-title">Lista de Tutores</h3>
                <div>
                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#importTutoresModal">
                        <i class="fas fa-file-import"></i> Importar Excel
                    </button>
                    <a href="{{ route('tutor.export.pdf') }}" class="btn btn-danger">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="{{ route('tutor.export.excel') }}" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="{{ route('tutor.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Tutor
                    </a>
                </div>
            </div>
        </div>
        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-check"></i> Éxito!</h5>
                    {{ session('success') }}
                </div>
            @endif
            
            @if(session('warning'))
                <div class="alert alert-warning alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-exclamation-triangle"></i> Advertencia!</h5>
                    {{ session('warning') }}
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-ban"></i> Error!</h5>
                    {{ session('error') }}
                </div>
            @endif
            
            <table id="tutores-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Email</th>
                        <th>Teléfono</th>
                        <th>Especialidad</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importTutoresModal" tabindex="-1" role="dialog" aria-labelledby="importTutoresModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form action="{{ route('tutor.import.excel') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="importTutoresModalLabel">Importar Tutores desde Excel</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="archivo_excel">Archivo Excel:</label>
                            <input type="file" class="form-control-file" id="archivo_excel" name="archivo_excel" 
                                   accept=".xlsx,.xls,.csv" required>
                            <small class="form-text text-muted">
                                Formatos permitidos: .xlsx, .xls, .csv (máximo 2MB)
                            </small>
                        </div>
                        <div class="alert alert-info">
                            <h6><i class="icon fas fa-info"></i> Formato del archivo:</h6>
                            <p>El archivo Excel debe contener las siguientes columnas:</p>
                            <ul>
                                <li><strong>nombre</strong>: Nombre del tutor (obligatorio)</li>
                                <li><strong>apellido</strong>: Apellido del tutor (obligatorio)</li>
                                <li><strong>email</strong>: Correo electrónico (obligatorio)</li>
                                <li><strong>telefono</strong>: Número de teléfono (opcional)</li>
                                <li><strong>especialidad</strong>: Área de especialidad (opcional)</li>
                                <li><strong>biografia</strong>: Biografía del tutor (opcional)</li>
                                <li><strong>activo</strong>: Estado activo (1 para activo, 0 para inactivo, opcional)</li>
                            </ul>
                            <p><small>La primera fila debe contener los nombres de las columnas.</small></p>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Importar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.25/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/1.7.1/css/buttons.bootstrap4.min.css">
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.10.25/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.25/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/1.7.1/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            $('#tutores-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('tutor.index') }}",
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'nombre', name: 'nombre'},
                    {data: 'apellido', name: 'apellido'},
                    {data: 'email', name: 'email'},
                    {data: 'telefono', name: 'telefono'},
                    {data: 'especialidad', name: 'especialidad'},
                    {data: 'activo', name: 'activo'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
                }
            });
        });
        
        function eliminarTutor(id) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "¡No podrás revertir esto!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/tutor/${id}`,
                        type: 'DELETE',
                        data: {
                            "_token": "{{ csrf_token() }}"
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire(
                                    '¡Eliminado!',
                                    response.message,
                                    'success'
                                );
                                $('#tutores-table').DataTable().ajax.reload();
                            } else {
                                Swal.fire(
                                    'Error',
                                    response.message,
                                    'error'
                                );
                            }
                        },
                        error: function() {
                            Swal.fire(
                                'Error',
                                'Ocurrió un error al eliminar el tutor',
                                'error'
                            );
                        }
                    });
                }
            });
        }
    </script>
@stop
