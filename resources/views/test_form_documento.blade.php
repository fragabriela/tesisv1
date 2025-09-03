<!DOCTYPE html>
<html>
<head>
    <title>Test Formulario Documento</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h2>Test directo de formulario de documento</h2>

    @if($errors->any())
        <div style="color: red;">
            <h3>Errores de validación:</h3>
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if(session('success'))
        <div style="color: green;">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if(session('error'))
        <div style="color: red;">
            <p>{{ session('error') }}</p>
        </div>
    @endif

    <form action="{{ route('documento.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div>
            <label for="tesis_id">Seleccionar Tesis:</label>
            <select name="tesis_id" id="tesis_id" required>
                <option value="">Seleccionar...</option>
                @foreach(App\Models\Tesis::all() as $tesis)
                    <option value="{{ $tesis->id }}">{{ $tesis->titulo }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label for="descripcion">Descripción:</label>
            <textarea name="descripcion" id="descripcion" required></textarea>
        </div>

        <div>
            <label for="archivo">Archivo Word (opcional):</label>
            <input type="file" name="archivo" id="archivo" accept=".doc,.docx">
        </div>

        <button type="submit">Guardar Documento</button>
    </form>

    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            console.log('Formulario enviado');
            console.log('Tesis ID:', document.getElementById('tesis_id').value);
            console.log('Descripción:', document.getElementById('descripcion').value);
        });
    </script>
</body>
</html>