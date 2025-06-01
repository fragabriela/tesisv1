@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Bienvenido</h1>
@endsection

@section('content')
    <p>Contenido de tesis de {{$nombre}}.</p>
    <ul>
        <li>Laravel y php</li>
        <li>Laragon</li>
        <li>HTML, CSS, JS</li>
        <button type="button" class="btn btn-inicio">Inicio</button>
        <button type ="buton" class="btn btn-salir">Salir</button>
        <button type="button" class="btn btn-outline-primary">Borrar</button>
        <button type="button" class="btn btn-outline-success">Success</button>
    </ul>
@endsection