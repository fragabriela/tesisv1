<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sistema de Gestión Académica</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .welcome-container {
            background-color: white;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 1.5rem;
        }
        p {
            color: #555;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            border-radius: 4px;
            text-decoration: none;
            font-weight: 600;
            margin: 0 0.5rem;
            transition: all 0.2s;
        }
        .btn-primary {
            background-color: #3490dc;
            color: white;
        }
        .btn-primary:hover {
            background-color: #2779bd;
        }
        .btn-secondary {
            background-color: #f3f4f6;
            color: #333;
        }
        .btn-secondary:hover {
            background-color: #e2e4e8;
        }
        .logo {
            margin-bottom: 2rem;
            max-width: 100px;
        }
    </style>
</head>
<body>
    <div class="welcome-container">
        <h1>Sistema de Gestión Académica</h1>
        <p>
            Bienvenido al sistema de gestión académica. Esta plataforma permite administrar 
            la información de carreras, alumnos, tutores y tesis de la institución.
        </p>
        <div>
            @if (Route::has('login'))
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-primary">Dashboard</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-primary">Iniciar Sesión</a>
                @endauth
            @endif
        </div>
    </div>
</body>
</html>