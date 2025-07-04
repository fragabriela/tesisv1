@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1 class="tesisheader">Gestión de Alumnos</h1>
@endsection

@section('content')

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    
    <div class="container-fluid">
        <div class="row">

            <div class="col-md-3 column-form">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><i class="fas fa-user-plus mr-2"></i> Registrar Nuevo Alumno</h5>
                    </div>
                    <div class="card-body">
                        <form action="{{ route('alumno.guardar') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" name="nombre" class="form-control" id="nombre"
                                        placeholder="Ingrese el nombre" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="apellido" class="form-label">Apellido</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                    <input type="text" name="apellido" class="form-control" id="apellido"
                                        placeholder="Ingrese el apellido" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" name="email" class="form-control" id="email"
                                        placeholder="correo@ejemplo.com" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="text" name="telefono" class="form-control" id="telefono"
                                        placeholder="Ingrese el teléfono">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="cedula" class="form-label">Cédula</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card"></i></span>
                                    <input type="text" name="cedula" class="form-control" id="cedula"
                                        placeholder="Ingrese la cédula" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="matricula" class="form-label">Matrícula</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                    <input type="text" name="matricula" class="form-control" id="matricula"
                                        placeholder="Ingrese la matrícula" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                    <input type="date" name="fecha_nacimiento" class="form-control" id="fecha_nacimiento">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <input type="text" name="direccion" class="form-control" id="direccion"
                                        placeholder="Ingrese la dirección">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="carreraSelect" class="form-label">Carrera</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-graduation-cap"></i></span>
                                    <select id="carreraSelect" name="id_carrera" class="form-select custom-select" required>
                                        <option value="" selected disabled>-- Seleccione una carrera --</option>
                                        @foreach ($carreras as $carrera)
                                            <option value="{{ $carrera->id }}">{{ $carrera->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-toggle-on"></i></span>
                                    <select name="estado" class="form-select custom-select" id="estado" required>
                                        <option value="activo" selected>Activo</option>
                                        <option value="inactivo">Inactivo</option>
                                    </select>
                                </div>
                            </div>

                    
                            <div class="mt-4 d-grid">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-save mr-2"></i> Guardar Alumno
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-9 column-alumnos">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-user-graduate mr-2"></i> Listado de Alumnos</h3>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped table-hover" id="alumnos-table">
                            <thead>
                                <tr>
                                    {{-- <th scope="col">id</th> --}}
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Email</th>
                                    <th scope="col">Teléfono</th>
                                    <th scope="col">Cédula</th>
                                    <th scope="col">Matrícula</th>
                                    {{-- <th scope="col">fecha_nacimiento</th> --}}
                                    <th scope="col">Carrera</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col">Acciones</th>
                                </tr>
                            </thead>
                    <tbody>
                        @foreach ($data as $datos)
                            <tr>
                                {{-- <th scope="row">{{ $datos->id }}</th> --}}
                                <td>{{ $datos->nombre }}</td>
                                <td>{{ $datos->email }}</td>
                                <td>{{ $datos->telefono }}</td>
                                <td>{{ $datos->cedula }}</td>
                                <td>{{ $datos->matricula }}</td>
                                {{-- <td>{{ $datos->fecha_nacimiento }}</td> --}}
                                <td>{{ $datos->carrera_nombre }}</td>
                                <td>
                                    @if($datos->estado == 'activo')
                                        <span class="badge badge-success">Activo</span>
                                    @else
                                        <span class="badge badge-danger">Inactivo</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group" role="group">
                                        <a class="btn btn-sm btn-info me-2" title="Editar"
                                            href="{{ route('alumno.edit', ['alumno' => $datos->id]) }}">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a class="btn btn-sm btn-danger" title="Eliminar"
                                            href="{{ route('alumno.delete', ['id' => $datos->id]) }}"
                                            onclick="return confirm('¿Está seguro que desea eliminar este alumno?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    </body>

    </html>

@endsection

@section('css')
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.4.1/css/responsive.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.3/css/buttons.bootstrap4.min.css">
    <!-- Toastr CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css">
    <style>
        /* Estilos generales */
        h1 {
            color: #3c4b64;
            margin-bottom: 1.5rem;
            font-weight: 700;
            border-left: 5px solid #0d6efd;
            padding-left: 15px;
        }

        /* Estilos para la sección de formularios */
        .column-form {
            padding: 20px !important;
            margin: 0% !important;
            background-color: #f8f9fa;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .column-alumnos {
            padding: 20px !important;
            margin-left: 0% !important;
        }
        
        /* Estilos para formularios */
        .form-label {
            font-weight: 600;
            color: #495057;
        }
        
        .form-control {
            border: 1px solid #ced4da;
            border-radius: 5px;
            padding: 8px 12px;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        /* Estilo para el label */
        .custom-label {
            font-weight: bold;
            font-size: 1.1rem;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        /* Estilo para el select */
        .custom-select {
            border: 1px solid #ced4da;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: box-shadow 0.3s ease;
            height: 38px;
        }

        .custom-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            border-color: #0d6efd;
            outline: none;
        }
        
        /* Estilos para la tabla */
        #alumnos-table {
            border-collapse: separate;
            border-spacing: 0;
            width: 100%;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }
        
        #alumnos-table thead th {
            background-color: #3c4b64;
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            padding: 12px 15px;
            border: none;
        }
        
        #alumnos-table tbody td {
            padding: 10px 15px;
            border-bottom: 1px solid #e9ecef;
            vertical-align: middle;
        }
        
        #alumnos-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Estilos para botones */
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            padding: 8px 16px;
            font-weight: 600;
            border-radius: 5px;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            background-color: #0b5ed7;
            border-color: #0a58ca;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        
        .btn-info {
            background-color: #17a2b8;
            border-color: #17a2b8;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.875rem;
            transition: all 0.2s;
        }
        
        .btn-info:hover {
            background-color: #138496;
            border-color: #117a8b;
        }
        
        /* Estilos para búsqueda y paginación */
        .dataTables_filter input {
            border: 1px solid #ced4da;
            border-radius: 5px;
            padding: 6px 10px;
            margin-left: 10px;
        }
        
        .dataTables_length select {
            border: 1px solid #ced4da;
            border-radius: 5px;
            padding: 5px;
            margin: 0 5px;
        }
        
        .page-item.active .page-link {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
    </style>
@endsection

@section('js')
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.4.1/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.2.3/js/buttons.colVis.min.js"></script>
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Initialize DataTable with enhanced features
            $('#alumnos-table').DataTable({
                responsive: true,
                autoWidth: false,
                processing: true,
                pageLength: 10,
                lengthMenu: [[5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"]],
                dom: '<"top"lBf>rt<"bottom"ip><"clear">',
                buttons: [
                    {
                        extend: 'copy',
                        text: '<i class="fas fa-copy"></i> Copiar',
                        className: 'btn btn-secondary btn-sm'
                    },
                    {
                        extend: 'excel',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        className: 'btn btn-success btn-sm'
                    },
                    {
                        extend: 'pdf',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        className: 'btn btn-danger btn-sm'
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> Imprimir',
                        className: 'btn btn-info btn-sm'
                    }
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
                }
            });
            
            // Configuración de Toastr
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-top-right",
                "showDuration": "300",
                "hideDuration": "1000",
                "timeOut": "5000",
                "extendedTimeOut": "1000",
                "showEasing": "swing",
                "hideEasing": "linear",
                "showMethod": "fadeIn",
                "hideMethod": "fadeOut"
            };
            
            // Display flash messages with Toastr
            @if(session('success'))
                toastr.success("{{ session('success') }}", "¡Éxito!");
            @endif
            
            @if(session('error'))
                toastr.error("{{ session('error') }}", "Error");
            @endif
        });
    </script>
@endsection
