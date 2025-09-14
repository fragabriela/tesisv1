@extends('adminlte::page')

@section('title', 'Gestión de Carreras')

@section('content_header')
    <h1>Gestión de Carreras</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between">
                <h3 class="card-title">Lista de Carreras</h3>
                <div>
                    <!-- Import Excel Button -->
                    <button type="button" class="btn btn-info" data-toggle="modal" data-target="#importModal">
                        <i class="fas fa-file-import"></i> Importar Excel
                    </button>
                    <a href="{{ route('carrera.export.pdf') }}" class="btn btn-danger">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="{{ route('carrera.export.excel') }}" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <a href="{{ route('carrera.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Carrera
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
            
            <table id="carreras-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Estado</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" role="dialog" aria-labelledby="importModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('carrera.import.excel') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="importModalLabel">Importar Carreras desde Excel</h5>
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
                                <li><strong>nombre</strong>: Nombre de la carrera (obligatorio)</li>
                                <li><strong>descripcion</strong>: Descripción de la carrera (opcional)</li>
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
    <link rel="stylesheet" href="/css/admin_custom.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#carreras-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('carrera.index') }}",
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'nombre', name: 'nombre'},
                    {data: 'descripcion', name: 'descripcion'},
                    {data: 'activo', name: 'activo'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ],
                language: {
                    "url": "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                }
            });
        });

        function eliminarCarrera(id) {
            if(confirm('¿Estás seguro de que deseas eliminar esta carrera?')) {
                $.ajax({
                    url: `/carrera/${id}`,
                    type: 'DELETE',
                    data: {
                        "_token": "{{ csrf_token() }}",
                    },
                    success: function(response) {
                        if(response.success) {
                            $('#carreras-table').DataTable().ajax.reload();
                            toastr.success(response.message);
                        } else {
                            toastr.error(response.message);
                        }
                    },
                    error: function() {
                        toastr.error('Ha ocurrido un error al eliminar la carrera');
                    }
                });
            }
        }
    </script>
@stop
