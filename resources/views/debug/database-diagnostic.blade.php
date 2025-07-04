<!DOCTYPE html>
<html>
<head>
    <title>Database Diagnostic Page</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Database Structure Diagnostic</h3>
                    </div>
                    <div class="card-body">
                        <h4>Database Schema Information</h4>
                        <div id="schema-info" class="mb-4 p-3 bg-light">
                            Loading...
                        </div>
                        
                        <h4>Fix Database Structure</h4>
                        <button id="fix-button" class="btn btn-danger mb-3">
                            Run Database Fix
                        </button>
                        <div id="fix-result" class="mb-4 p-3 bg-light d-none">
                        </div>
                        
                        <h4>Test Form Submission</h4>
                        <form id="test-form" class="border p-3 mb-3">
                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre</label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="Test">
                            </div>
                            <div class="mb-3">
                                <label for="apellido" class="form-label">Apellido</label>
                                <input type="text" class="form-control" id="apellido" name="apellido" value="User">
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="test@example.com">
                            </div>
                            <div class="mb-3">
                                <label for="telefono" class="form-label">Teléfono</label>
                                <input type="text" class="form-control" id="telefono" name="telefono" value="123456789">
                            </div>
                            <div class="mb-3">
                                <label for="cedula" class="form-label">Cédula</label>
                                <input type="text" class="form-control" id="cedula" name="cedula" value="TEST-<?php echo time(); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="matricula" class="form-label">Matrícula</label>
                                <input type="text" class="form-control" id="matricula" name="matricula" value="MAT-<?php echo time(); ?>">
                            </div>
                            <div class="mb-3">
                                <label for="fecha_nacimiento" class="form-label">Fecha de Nacimiento</label>
                                <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="2000-01-01">
                            </div>
                            <div class="mb-3">
                                <label for="direccion" class="form-label">Dirección</label>
                                <input type="text" class="form-control" id="direccion" name="direccion" value="Test Address">
                            </div>
                            <div class="mb-3">
                                <label for="id_carrera" class="form-label">Carrera</label>
                                <select class="form-control" id="id_carrera" name="id_carrera">
                                    <?php 
                                    $carreras = \App\Models\Carrera::all();
                                    foreach ($carreras as $carrera) {
                                        echo "<option value='{$carrera->id}'>{$carrera->nombre}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-control" id="estado" name="estado">
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary">Submit Test Form</button>
                        </form>
                        <div id="form-result" class="p-3 bg-light d-none">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Load database schema info
            $.get('/api/schema/alumnos', function(response) {
                var html = '<pre>' + JSON.stringify(response, null, 2) + '</pre>';
                $('#schema-info').html(html);
            }).fail(function() {
                $('#schema-info').html('<div class="alert alert-danger">Failed to load schema information</div>');
            });
            
            // Fix button handler
            $('#fix-button').click(function() {
                $(this).attr('disabled', true).html('<span class="spinner-border spinner-border-sm"></span> Running...');
                
                $.get('/fix-database', function(response) {
                    var html = '<div class="alert alert-success">Fix completed</div>';
                    html += '<pre>' + JSON.stringify(response, null, 2) + '</pre>';
                    $('#fix-result').html(html).removeClass('d-none');
                    
                    $('#fix-button').removeAttr('disabled').text('Run Database Fix Again');
                }).fail(function(error) {
                    var html = '<div class="alert alert-danger">Fix failed</div>';
                    html += '<pre>' + JSON.stringify(error, null, 2) + '</pre>';
                    $('#fix-result').html(html).removeClass('d-none');
                    
                    $('#fix-button').removeAttr('disabled').text('Run Database Fix Again');
                });
            });
            
            // Test form submission
            $('#test-form').submit(function(e) {
                e.preventDefault();
                
                $.post('/api/test-alumno-create', $(this).serialize(), function(response) {
                    var html = '<div class="alert alert-success">Form submitted successfully!</div>';
                    html += '<pre>' + JSON.stringify(response, null, 2) + '</pre>';
                    $('#form-result').html(html).removeClass('d-none');
                }).fail(function(error) {
                    var html = '<div class="alert alert-danger">Form submission failed</div>';
                    html += '<pre>' + JSON.stringify(error.responseJSON, null, 2) + '</pre>';
                    $('#form-result').html(html).removeClass('d-none');
                });
            });
        });
    </script>
</body>
</html>
