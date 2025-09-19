@extends('adminlte::page')

@section('title', 'Documentos Colaborativos')

@section('content_header')
    <h1>Documentos Colaborativos</h1>
@stop

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Lista de Documentos</h3>
                <div class="card-tools">
                    @can('crear documentos')
                        <a href="{{ route('documento.create') }}" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Documento
                        </a>
                    @endcan
                </div>
            </div>
            <div class="card-body">
                <table id="documentos-table" class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Tesis</th>
                            <th>Alumno</th>
                            <th>Tutor</th>
                            <th>Estado</th>
                            <th>Comentarios</th>
                            <th>Versión</th>
                            <th>Última Edición</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/dataTables.bootstrap4.min.css">
    <style>
        .badge {
            font-size: 0.8em;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        $(document).ready(function() {
            $('#documentos-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('documento.data') }}",
                columns: [
                    {data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false},
                    {data: 'titulo', name: 'titulo'},
                    {data: 'tesis', name: 'tesis.titulo'},
                    {data: 'alumno', name: 'alumno.nombre'},
                    {data: 'tutor', name: 'tutor.nombre'},
                    {data: 'estado_badge', name: 'estado', orderable: false, searchable: false},
                    {data: 'comentarios_count', name: 'comentarios_count', orderable: false, searchable: false},
                    {data: 'version', name: 'version'},
                    {data: 'fecha_ultima_modificacion', name: 'fecha_ultima_modificacion'},
                    {data: 'actions', name: 'actions', orderable: false, searchable: false}
                ],
                language: {
                    url: '{{ asset("assets/datatables/i18n/es-ES.json") }}'
                }
            });
        });

        function eliminarDocumento(id) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción no se puede revertir. Se eliminarán todos los comentarios asociados.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/documento/${id}`,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if(response.success) {
                                Swal.fire('¡Eliminado!', response.message, 'success');
                                $('#documentos-table').DataTable().ajax.reload();
                            } else {
                                Swal.fire('Error', response.message, 'error');
                            }
                        },
                        error: function() {
                            Swal.fire('Error', 'Ocurrió un error al eliminar el documento', 'error');
                        }
                    });
                }
            });
        }
    </script>
@stop