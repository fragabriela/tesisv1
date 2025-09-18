@extends('adminlte::page')

@section('title', 'Gestión de Roles y Permisos')

@section('content_header')
    <h1>Gestión de Roles y Permisos</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between">
                <h3 class="card-title">Lista de Roles</h3>
                <div>
                    <button type="button" class="btn btn-success" onclick="createRole()">
                        <i class="fas fa-plus"></i> Nuevo Rol
                    </button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                        <i class="fas fa-users"></i> Gestionar Usuarios
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
            
            <table id="roles-table" class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre del Rol</th>
                        <th>Usuarios</th>
                        <th>Permisos</th>
                        <th>Fecha Creación</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Role Modal -->
    <div class="modal fade" id="createRoleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <form id="createRoleForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Crear Nuevo Rol</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="create_role_name">Nombre del Rol</label>
                            <input type="text" class="form-control" id="create_role_name" name="name" required>
                            <small class="form-text text-muted">Ejemplo: editor, supervisor, etc.</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Permisos:</label>
                            <div class="row" id="create_permissions_container">
                                <!-- Permisos se cargarán aquí -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Crear Rol</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Role Modal -->
    <div class="modal fade" id="editRoleModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="editRoleForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Rol</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="edit_role_id">
                        <div class="form-group">
                            <label for="edit_role_name">Nombre del Rol</label>
                            <input type="text" class="form-control" id="edit_role_name" name="name" required>
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

    <!-- Manage Role Permissions Modal -->
    <div class="modal fade" id="manageRolePermissionsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <form id="manageRolePermissionsForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Gestionar Permisos del Rol</h5>
                        <button type="button" class="close" data-dismiss="modal">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="permissions_role_id">
                        <div class="form-group">
                            <label>Rol:</label>
                            <p id="permissions_role_info" class="font-weight-bold text-primary"></p>
                        </div>
                        
                        <div class="form-group">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <label>Permisos Disponibles:</label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-success" onclick="selectAllPermissions()">
                                        <i class="fas fa-check-square"></i> Seleccionar Todos
                                    </button>
                                    <button type="button" class="btn btn-sm btn-warning" onclick="deselectAllPermissions()">
                                        <i class="fas fa-square"></i> Deseleccionar Todos
                                    </button>
                                </div>
                            </div>
                            <div class="row" id="permissions_container">
                                <!-- Permisos se cargarán aquí organizados por categoría -->
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Actualizar Permisos</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <style>
        .permission-category {
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background-color: #f8f9fa;
        }
        .permission-category h6 {
            color: #495057;
            font-weight: 600;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            font-size: 0.875rem;
        }
        .form-check {
            margin-bottom: 0.5rem;
        }
        .form-check-label {
            font-size: 0.9rem;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTable
            var table = $('#roles-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('admin.roles.index') }}",
                columns: [
                    {data: 'id', name: 'id'},
                    {data: 'name', name: 'name'},
                    {data: 'users_count', name: 'users_count', orderable: false, searchable: false},
                    {data: 'permissions_count', name: 'permissions_count', orderable: false, searchable: false},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'action', name: 'action', orderable: false, searchable: false}
                ],
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.10.25/i18n/Spanish.json'
                }
            });

            // Create Role Form
            $('#createRoleForm').on('submit', function(e) {
                e.preventDefault();
                var selectedPermissions = [];
                
                $('#create_permissions_container input[type="checkbox"]:checked').each(function() {
                    selectedPermissions.push($(this).val());
                });

                var formData = {
                    name: $('#create_role_name').val(),
                    permissions: selectedPermissions,
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                $.ajax({
                    url: '/admin/roles',
                    method: 'POST',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#createRoleModal').modal('hide');
                            $('#createRoleForm')[0].reset();
                            Swal.fire('Éxito', response.message, 'success');
                            table.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        var error = xhr.responseJSON.error || 'Error al crear rol';
                        Swal.fire('Error', error, 'error');
                    }
                });
            });

            // Edit Role Form
            $('#editRoleForm').on('submit', function(e) {
                e.preventDefault();
                var roleId = $('#edit_role_id').val();
                var formData = {
                    name: $('#edit_role_name').val(),
                    _token: $('meta[name="csrf-token"]').attr('content')
                };

                $.ajax({
                    url: `/admin/roles/${roleId}`,
                    method: 'PUT',
                    data: formData,
                    success: function(response) {
                        if (response.success) {
                            $('#editRoleModal').modal('hide');
                            Swal.fire('Éxito', response.message, 'success');
                            table.ajax.reload();
                        }
                    },
                    error: function(xhr) {
                        var error = xhr.responseJSON.error || 'Error al actualizar rol';
                        Swal.fire('Error', error, 'error');
                    }
                });
            });

            // Manage Role Permissions Form
            $('#manageRolePermissionsForm').on('submit', function(e) {
                e.preventDefault();
                var roleId = $('#permissions_role_id').val();
                var selectedPermissions = [];
                
                $('#permissions_container input[type="checkbox"]:checked').each(function() {
                    selectedPermissions.push($(this).val());
                });

                $.ajax({
                    url: `/admin/roles/${roleId}/permissions`,
                    method: 'PUT',
                    data: {
                        permissions: selectedPermissions,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#manageRolePermissionsModal').modal('hide');
                            
                            // Show debug info if available
                            let message = response.message;
                            if (response.debug) {
                                message += `\n\nPermisos aplicados: ${response.debug.permissions_count}`;
                                console.log('Permisos actualizados:', response.debug.permissions);
                            }
                            
                            Swal.fire('Éxito', message, 'success');
                            table.ajax.reload();
                            
                            // Force permission cache clear
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                        }
                    },
                    error: function(xhr) {
                        var error = xhr.responseJSON.error || 'Error al actualizar permisos';
                        Swal.fire('Error', error, 'error');
                    }
                });
            });
        });

        function createRole() {
            $.get('/admin/permissions', function(permissions) {
                renderPermissions(permissions, '#create_permissions_container', []);
                $('#createRoleModal').modal('show');
            });
        }

        function editRole(id) {
            $.get(`/admin/roles/${id}`, function(data) {
                $('#edit_role_id').val(data.role.id);
                $('#edit_role_name').val(data.role.name);
                $('#editRoleModal').modal('show');
            });
        }

        function manageRolePermissions(id) {
            Promise.all([
                $.get(`/admin/roles/${id}`),
                $.get('/admin/permissions')
            ]).then(function(responses) {
                var roleData = responses[0];
                var permissionsData = responses[1];
                
                $('#permissions_role_id').val(roleData.role.id);
                $('#permissions_role_info').text(roleData.role.name.charAt(0).toUpperCase() + roleData.role.name.slice(1));
                
                renderPermissions(permissionsData, '#permissions_container', roleData.permissions);
                $('#manageRolePermissionsModal').modal('show');
            });
        }

        function renderPermissions(permissions, containerId, selectedPermissions = []) {
            var html = '';
            
            Object.keys(permissions).forEach(function(category) {
                html += `<div class="col-md-6">
                    <div class="permission-category">
                        <h6>${category.charAt(0).toUpperCase() + category.slice(1)}</h6>`;
                
                permissions[category].forEach(function(permission) {
                    var checked = selectedPermissions.includes(permission.name) ? 'checked' : '';
                    var permissionLabel = permission.name.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                    
                    html += `
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="${permission.name}" 
                                   id="perm_${permission.id}" ${checked}>
                            <label class="form-check-label" for="perm_${permission.id}">
                                ${permissionLabel}
                            </label>
                        </div>
                    `;
                });
                
                html += `</div></div>`;
            });
            
            $(containerId).html(html);
        }

        function selectAllPermissions() {
            $('#permissions_container input[type="checkbox"]').prop('checked', true);
        }

        function deselectAllPermissions() {
            $('#permissions_container input[type="checkbox"]').prop('checked', false);
        }

        function deleteRole(id) {
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
                        url: `/admin/roles/${id}`,
                        method: 'DELETE',
                        data: {
                            _token: $('meta[name="csrf-token"]').attr('content')
                        },
                        success: function(response) {
                            if (response.success) {
                                Swal.fire('Eliminado', response.message, 'success');
                                $('#roles-table').DataTable().ajax.reload();
                            }
                        },
                        error: function(xhr) {
                            var error = xhr.responseJSON.error || 'Error al eliminar rol';
                            Swal.fire('Error', error, 'error');
                        }
                    });
                }
            });
        }
    </script>
@stop