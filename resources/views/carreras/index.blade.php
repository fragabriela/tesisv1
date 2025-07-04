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
