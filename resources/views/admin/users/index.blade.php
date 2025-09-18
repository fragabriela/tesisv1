@extends('adminlte::page')

@section('title', 'Gestión de Usuarios')

@section('content_header')
    <h1>Gestión de Usuarios</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between">
                <h3 class="card-title">Lista de Usuarios</h3>
                <div>
                    <a href="{{ route('admin.roles.index') }}" class="btn btn-info">
                        <i class="fas fa-user-shield"></i> Gestionar Roles
                    </a>
                    <button type="button" class="btn btn-primary" onclick="createUser()">
                        <i class="fas fa-plus"></i> Nuevo Usuario
                    </button>
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
            
            <table id="users-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th>Registros Asociados</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="editUserForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Usuario</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_user_id">
                        <div class="form-group">
                            <label for="edit_name">Nombre</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_email">Email</label>
                            <input type="email" class="form-control" id="edit_email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="edit_password">Nueva Contraseña (opcional)</label>
                            <input type="password" class="form-control" id="edit_password" name="password">
                        </div>
                        <div class="form-group">
                            <label for="edit_password_confirmation">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="edit_password_confirmation" name="password_confirmation">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Manage Roles Modal -->
    <div class="modal fade" id="manageRolesModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="manageRolesForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Gestionar Roles</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="roles_user_id">
                        <div class="form-group">
                            <label>Usuario:</label>
                            <p id="roles_user_info" class="font-weight-bold"></p>
                        </div>
                        <div class="form-group">
                            <label>Roles Disponibles:</label>
                            <div id="available_roles">
                                <!-- Roles will be loaded here -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Roles</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Associate Records Modal -->
    <div class="modal fade" id="associateRecordsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <form id="associateRecordsForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Asociar Registros</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="associate_user_id">
                        <div class="form-group">
                            <label>Usuario:</label>
                            <p id="associate_user_info" class="font-weight-bold"></p>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="alumno_id">Asociar con Alumno:</label>
                                    <select class="form-control" id="alumno_id" name="alumno_id">
                                        <option value="">Sin asociar</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tutor_id">Asociar con Tutor:</label>
                                    <select class="form-control" id="tutor_id" name="tutor_id">
                                        <option value="">Sin asociar</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Nota:</strong> Un usuario puede estar asociado tanto con un alumno como con un tutor si es necesario.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Asociaciones</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div class="modal fade" id="createUserModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="createUserForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Crear Nuevo Usuario</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="create_name">Nombre</label>
                            <input type="text" class="form-control" id="create_name" name="name" required>
                        </div>
                        <div class="form-group">
                            <label for="create_email">Email</label>
                            <input type="email" class="form-control" id="create_email" name="email" required>
                        </div>
                        <div class="form-group">
                            <label for="create_password">Contraseña</label>
                            <input type="password" class="form-control" id="create_password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="create_password_confirmation">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="create_password_confirmation" name="password_confirmation" required>
                        </div>
                        <div class="form-group">
                            <label>Roles:</label>
                            <div id="create_available_roles">
                                <!-- Roles will be loaded here -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Crear Usuario</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#users-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.users.index') }}",
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'name', name: 'name'},
                    {data: 'email', name: 'email'},
                    {data: 'roles', name: 'roles', orderable: false, searchable: false},
                    {data: 'associated_records', name: 'associated_records', orderable: false, searchable: false},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
                }
            });

            // Edit User Form
            $('#editUserForm').on('submit', function(e) {
                e.preventDefault();
                var userId = $('#edit_user_id').val();
                var formData = {
                    name: $('#edit_name').val(),
                    email: $('#edit_email').val(),
                    password: $('#edit_password').val(),
                    password_confirmation: $('#edit_password_confirmation').val(),
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                $.ajax({
                    url: `/admin/users/${userId}`,
                    method: 'PUT',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#editUserModal').modal('hide');
                            Swal.fire('Éxito', response.message, 'success');
                            table.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        var error = xhr.responseJSON.error || 'Error al actualizar usuario';
                        Swal.fire('Error', error, 'error');
                    }
                });
            });

            // Manage Roles Form
            $('#manageRolesForm').on('submit', function(e) {
                e.preventDefault();
                var userId = $('#roles_user_id').val();
                var selectedRoles = [];
                
                $('#available_roles input[type="checkbox"]:checked').each(function() {
                    selectedRoles.push($(this).val());
                });

                $.ajax({
                    url: `/admin/users/${userId}/roles`,
                    method: 'PUT',
                    data: {
                        roles: selectedRoles,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#manageRolesModal').modal('hide');
                            Swal.fire('Éxito', response.message, 'success');
                            table.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        var error = xhr.responseJSON.error || 'Error al actualizar roles';
                        Swal.fire('Error', error, 'error');
                    }
                });
            });

            // Associate Records Form
            $('#associateRecordsForm').on('submit', function(e) {
                e.preventDefault();
                var userId = $('#associate_user_id').val();
                var formData = {
                    alumno_id: $('#alumno_id').val(),
                    tutor_id: $('#tutor_id').val(),
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                $.ajax({
                    url: `/admin/users/${userId}/associate`,
                    method: 'PUT',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#associateRecordsModal').modal('hide');
                            Swal.fire('Éxito', response.message, 'success');
                            table.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        var error = xhr.responseJSON.error || 'Error al actualizar asociaciones';
                        Swal.fire('Error', error, 'error');
                    }
                });
            });

            // Create User Form
            $('#createUserForm').on('submit', function(e) {
                e.preventDefault();
                var selectedRoles = [];
                
                $('#create_available_roles input[type="checkbox"]:checked').each(function() {
                    selectedRoles.push($(this).val());
                });

                var formData = {
                    name: $('#create_name').val(),
                    email: $('#create_email').val(),
                    password: $('#create_password').val(),
                    password_confirmation: $('#create_password_confirmation').val(),
                    roles: selectedRoles,
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                $.ajax({
                    url: '/admin/users',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#createUserModal').modal('hide');
                            $('#createUserForm')[0].reset();
                            Swal.fire('Éxito', response.message, 'success');
                            table.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        var error = xhr.responseJSON.error || 'Error al crear usuario';
                        Swal.fire('Error', error, 'error');
                    }
                });
            });
        });

        function editUser(id) {
            $.get(`/admin/users/${id}`, function(data) {
                $('#edit_user_id').val(data.user.id);
                $('#edit_name').val(data.user.name);
                $('#edit_email').val(data.user.email);
                $('#edit_password').val('');
                $('#edit_password_confirmation').val('');
                $('#editUserModal').modal('show');
            });
        }

        function manageRoles(id) {
            Promise.all([
                $.get(`/admin/users/${id}`),
                $.get('/admin/roles-list')
            ]).then(function(responses) {
                var userData = responses[0];
                var rolesData = responses[1];
                
                $('#roles_user_id').val(userData.user.id);
                $('#roles_user_info').text(`${userData.user.name} (${userData.user.email})`);
                
                var rolesHtml = '';
                rolesData.forEach(function(role) {
                    var checked = userData.roles.includes(role.name) ? 'checked' : '';
                    rolesHtml += `
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="${role.name}" id="role_${role.id}" ${checked}>
                            <label class="form-check-label" for="role_${role.id}">
                                ${role.name.charAt(0).toUpperCase() + role.name.slice(1)}
                            </label>
                        </div>
                    `;
                });
                
                $('#available_roles').html(rolesHtml);
                $('#manageRolesModal').modal('show');
            });
        }

        function associateRecords(id) {
            $.get(`/admin/users/${id}/association-data`, function(data) {
                $('#associate_user_id').val(data.user.id);
                $('#associate_user_info').text(`${data.user.name} (${data.user.email})`);
                
                // Populate alumnos select
                var alumnosHtml = '<option value="">Sin asociar</option>';
                data.alumnos.forEach(function(alumno) {
                    var selected = data.user.alumno && data.user.alumno.id == alumno.id ? 'selected' : '';
                    alumnosHtml += `<option value="${alumno.id}" ${selected}>${alumno.nombre} ${alumno.apellido} (${alumno.email})</option>`;
                });
                $('#alumno_id').html(alumnosHtml);
                
                // Populate tutores select
                var tutoresHtml = '<option value="">Sin asociar</option>';
                data.tutores.forEach(function(tutor) {
                    var selected = data.user.tutor && data.user.tutor.id == tutor.id ? 'selected' : '';
                    tutoresHtml += `<option value="${tutor.id}" ${selected}>${tutor.nombre} ${tutor.apellido} (${tutor.email})</option>`;
                });
                $('#tutor_id').html(tutoresHtml);
                
                $('#associateRecordsModal').modal('show');
            });
        }

        function createUser() {
            $.get('/admin/roles-list', function(rolesData) {
                var rolesHtml = '';
                rolesData.forEach(function(role) {
                    rolesHtml += `
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="${role.name}" id="create_role_${role.id}">
                            <label class="form-check-label" for="create_role_${role.id}">
                                ${role.name.charAt(0).toUpperCase() + role.name.slice(1)}
                            </label>
                        </div>
                    `;
                });
                
                $('#create_available_roles').html(rolesHtml);
                $('#createUserModal').modal('show');
            });
        }

        function deleteUser(id) {
            Swal.fire({
                title: '¿Estás seguro?',
                text: "Esta acción no se puede deshacer",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: `/admin/users/${id}`,
                        method: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Eliminado', response.message, 'success');
                                $('#users-table').DataTable().ajax.reload();
                            }
                        },
                        error: function(xhr) {
                            var error = xhr.responseJSON.error || 'Error al eliminar usuario';
                            Swal.fire('Error', error, 'error');
                        }
                    });
                }
            });
        }
    </script>
@stop