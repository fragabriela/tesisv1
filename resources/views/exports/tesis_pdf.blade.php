<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Tesis</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 100px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 5px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .pendiente {
            background-color: #fff3cd;
        }
        .en_progreso {
            background-color: #d1ecf1;
        }
        .completado {
            background-color: #d4edda;
        }
        .rechazado {
            background-color: #f8d7da;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Lista de Tesis</h1>
        <p>Fecha de generación: {{ date('d/m/Y H:i:s') }}</p>
    </div>
    
    <table>
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
            </tr>
        </thead>
        <tbody>
            @foreach($tesis as $t)
                <tr class="{{ $t->estado }}">
                    <td>{{ $t->id }}</td>
                    <td>{{ $t->titulo }}</td>
                    <td>{{ $t->alumno->nombre }} {{ $t->alumno->apellido }}</td>
                    <td>{{ $t->tutor->nombre }} {{ $t->tutor->apellido }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $t->estado)) }}</td>
                    <td>{{ $t->fecha_inicio->format('d/m/Y') }}</td>
                    <td>{{ $t->fecha_fin ? $t->fecha_fin->format('d/m/Y') : 'N/A' }}</td>
                    <td>{{ $t->calificacion ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="footer">
        <p>Sistema de Gestión Académica - {{ date('Y') }}</p>
    </div>
</body>
</html>
