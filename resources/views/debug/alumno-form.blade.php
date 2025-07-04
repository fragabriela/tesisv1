<!DOCTYPE html>
<html>
<head>
    <title>Debug Alumno Form</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Debug Alumno Form</h1>
        
        <div class="card">
            <div class="card-header">
                Test Alumno Form
            </div>
            <div class="card-body">
                <form action="{{ url('/debug-alumno-store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="Nombre Test">
                    </div>
                    
                    <div class="form-group">
                        <label for="apellido">Apellido</label>
                        <input type="text" class="form-control" id="apellido" name="apellido" value="Apellido Test">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="test{{ time() }}@example.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="telefono">Teléfono</label>
                        <input type="text" class="form-control" id="telefono" name="telefono" value="123456789">
                    </div>
                    
                    <div class="form-group">
                        <label for="cedula">Cédula</label>
                        <input type="text" class="form-control" id="cedula" name="cedula" value="C{{ time() }}">
                    </div>
                    
                    <div class="form-group">
                        <label for="matricula">Matrícula</label>
                        <input type="text" class="form-control" id="matricula" name="matricula" value="M{{ time() }}">
                    </div>
                    
                    <div class="form-group">
                        <label for="fecha_nacimiento">Fecha de Nacimiento</label>
                        <input type="date" class="form-control" id="fecha_nacimiento" name="fecha_nacimiento" value="2000-01-01">
                    </div>
                    
                    <div class="form-group">
                        <label for="direccion">Dirección (opcional)</label>
                        <input type="text" class="form-control" id="direccion" name="direccion" value="Dirección de prueba">
                    </div>
                    
                    <div class="form-group">
                        <label for="id_carrera">Carrera</label>
                        <select class="form-control" id="id_carrera" name="id_carrera">
                            @foreach(App\Models\Carrera::all() as $carrera)
                                <option value="{{ $carrera->id }}">{{ $carrera->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="estado">Estado</label>
                        <select class="form-control" id="estado" name="estado">
                            <option value="activo">Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
