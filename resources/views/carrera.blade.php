@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1 class="tesisheader">Bienvenido</h1>
@endsection

@section('content')

    <h1> Carreras Guardar </h1>
    <form action="{{ route('carrera.guardar') }}" method="POST">
        @csrf

        <div class="container">
            <div class="row ">
                <div class="col-6">

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" id="nombre"
                                placeholder="Ingrese su carrera: ">
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Descripcion</label>
                            <input type="text" name="descripcion" class="form-control" id="descripcion"
                                placeholder="Descripcion de su carrera:">
                        </div>
                    </div>
                    <button class="btn btn-primary" type="submit">Enviar</button>
                </div>
                <div class="col-6">


                    <table class="table">
                        <thead>
                            <tr>
                                {{-- <th scope="col">id</th> --}}
                                <th scope="col">nombre</th>
                                <th scope="col">descripcion</th>
                                <th scope="col">acciones</th>
                            </tr>
                        </thead>
                        <tbody>   
                             @foreach ($data as $datos )
                                <tr>
                                    {{-- <th scope="row">{{ $datos->id  }}</th> --}}
                                    <td>{{ $datos->nombre }}</td>
                                    <td>{{ $datos->descripcion }}</td>
                                    <td>
                                        <a class="btn btn-danger" href="{{ route('carrera.delete', ['id' => $datos->id ]) }}">Eliminar</a> 
                                        <a class="btn btn-info" href="{{ route('carrera.update', ['id' => $datos->id ]) }}">Editar</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </form>

    
    </body>

    </html>

@endsection

@section('css')
    <style>

    </style>
@endsection
