<!DOCTYPE html>
<html>
<head>
    <title>Debug Carrera Form</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h1>Debug Carrera Form</h1>
        
        <div class="card">
            <div class="card-header">
                Simple Form without AdminLTE
            </div>
            <div class="card-body">
                <form action="{{ url('/debug-carrera-store') }}" method="POST">
                    @csrf
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="Test Name">
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3">Test Description</textarea>
                    </div>
                    
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="activo" name="activo" value="1" checked>
                        <label class="form-check-label" for="activo">Activo</label>
                    </div>
                    
                    <button type="submit" class="btn btn-primary mt-3">Submit</button>
                </form>
            </div>
        </div>
        
        <div class="card mt-4">
            <div class="card-header">
                Form Data Debug
            </div>
            <div class="card-body">
                <p>Request Method: {{ request()->method() }}</p>
                <p>CSRF Token: {{ csrf_token() }}</p>
                <p>Current URL: {{ request()->url() }}</p>
            </div>
        </div>
    </div>
</body>
</html>
