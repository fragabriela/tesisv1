@extends('adminlte::page')

@section('title', 'Gestión de Tesis')

@section('content_header')
    <div class="row mb-2">
        <div class="col-sm-6">
            <h1>Gestión de Tesis</h1>
        </div>
        <div class="col-sm-6">
            <div class="float-sm-right">
                @can('crear tesis')
                    <a href="{{ route('tesis.create') }}" class="btn btn-success">
                        <i class="fas fa-plus"></i> Nueva Tesis
                    </a>
                @endcan
                
                @can('exportar tesis')
                    <div class="btn-group">
                        <button type="button" class="btn btn-info dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="{{ route('tesis.export.pdf') }}">
                                <i class="far fa-file-pdf text-danger"></i> Exportar a PDF
                            </a>
                            <a class="dropdown-item" href="{{ route('tesis.export.excel') }}">
                                <i class="far fa-file-excel text-success"></i> Exportar a Excel
                            </a>
                        </div>
                    </div>
                @endcan
            </div>
        </div>
    </div>
@stop

@section('content')
    @if(auth()->user()->hasRole('tutor'))
        @php
            $tutorAlumnos = auth()->user()->tutor ? auth()->user()->tutor->alumnos : collect();
        @endphp
        
        @if($tutorAlumnos->count() > 0)
            <div class="alert alert-info">
                <h5><i class="icon fas fa-info-circle"></i> Información para Tutores</h5>
                <p>Como tutor, puedes ver las tesis de tus <strong>{{ $tutorAlumnos->count() }} alumno(s) asociado(s)</strong>:</p>
                <ul class="mb-0">
                    @foreach($tutorAlumnos as $alumno)
                        <li>{{ $alumno->nombre }} {{ $alumno->apellido }}</li>
                    @endforeach
                </ul>
                <small class="text-muted">Si no ves tesis en la tabla, significa que estos alumnos aún no tienen tesis creadas.</small>
            </div>
        @else
            <div class="alert alert-warning">
                <h5><i class="icon fas fa-exclamation-triangle"></i> Sin Alumnos Asociados</h5>
                <p>No tienes alumnos asociados como tutor. Contacta al administrador para que te asigne alumnos.</p>
            </div>
        @endif
    @endif

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-striped" id="tesis-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Título</th>
                            <th>Alumno</th>
                            <th>Tutor</th>
                            <th>Estado</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Calificación</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para eliminar -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar eliminación</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    ¿Está seguro que desea eliminar esta tesis?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <form id="deleteForm" method="POST">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script>
        $(function () {
            let table = $('#tesis-table').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: "{{ route('tesis.index') }}",
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'titulo', name: 'titulo'},
                    {data: 'alumno_nombre', name: 'alumno_nombre'},
                    {data: 'tutor_nombre', name: 'tutor_nombre'},
                    {data: 'estado_badge', name: 'estado', searchable: true, orderable: true},
                    {data: 'fecha_inicio', name: 'fecha_inicio'},
                    {data: 'fecha_fin', name: 'fecha_fin'},
                    {data: 'calificacion', name: 'calificacion'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ],
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json"
                }
            });

            // Configurar modal de eliminación
            $('#tesis-table').on('click', '.delete', function (e) {
                e.preventDefault();
                let id = $(this).data('id');
                let url = "{{ route('tesis.destroy', ':id') }}";
                url = url.replace(':id', id);
                $('#deleteForm').attr('action', url);
                $('#deleteModal').modal('show');
            });

            // Mostrar mensaje de éxito
            @if (session('success'))
                toastr.success("{{ session('success') }}");
            @endif

            // Mostrar mensaje de error
            @if (session('error'))
                toastr.error("{{ session('error') }}");
            @endif
        });
    </script>
@stop
