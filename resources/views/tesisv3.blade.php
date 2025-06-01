@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Bienvenido</h1>
@endsection

@section('content')
    <p>Contenido de tesis de {{$nombre}}.</p>
    <!DOCTYPE html>
<html>
<head>
    <title>Formulario</title>
</head>
<body>

@if(session('success'))
    <p>{{ session('success') }}</p>
@endif

<form action="{{ route('formulario.guardar') }}" method="POST">
    @csrf

    <label>Nombre:</label>
    <input type="text" name="nombre" required><br>

    <label>Email:</label>
    <input type="email" name="email" required><br>

    <button type="submit">Enviar</button>
</form>

</body>
</html>
@endsection