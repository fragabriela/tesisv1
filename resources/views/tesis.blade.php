@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Bienvenido</h1>
@endsection

@section('content')
    <p>Contenido de tesis de {{$nombre}}.</p>
    <ul>
        <li>Sidebar en la carperta config, en el archivo adminlte.php </li>
        <li>Entrar en la carpeta Htpp, en controllers, en el archivo tesiscontroller.php y agregar la funcion del controlador</li>
        <li>Entrar en la carperta route, buscar web.php y agregar la ruta y el controlador </li>
        <li>Crear la vista en la carpeta resources, en la carpeta views, agregar siempre blade.php  </li>
    </ul>
@endsection