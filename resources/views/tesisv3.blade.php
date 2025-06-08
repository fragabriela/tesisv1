@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1 class="tesisheader">Bienvenido</h1>
@endsection

@section('content')

    <form action="{{ route('formulario.guardar') }}" method="POST">
        @csrf

        <div class="container">
            <div class="row ">

                
                <div class="col-6">

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" id="nombre"
                                placeholder="Ingrese su nombre">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Apellido</label>
                            <input type="text" name="apellido" class="form-control" id="apellido"
                                placeholder="Ingrese su apellido">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Email address</label>
                            <input type="email" name="email" class="form-control" id="email"
                                placeholder="name@example.com">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Contraseña</label>
                            <input type="password" name="password" class="form-control" id="password"
                                placeholder="Ingrese su contraseña">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Direccion</label>
                            <input type="text" name="direccion" class="form-control" id="direccion"
                                placeholder="Ingrese su direccion">
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Barrio</label>
                            <input type="text" name="barrio" class="form-control" id="barrio"
                                placeholder="Ingrese su barrio">
                        </div>
                    </div>

                    <button class="btn btn-primary" type="submit">Enviar</button>

                </div>

                <div class="col-6">


                    <table class="table">
                        <thead>
                            <tr>
                                <th scope="col">id</th>
                                <th scope="col">nombre</th>
                                <th scope="col">apellido</th>
                                <th scope="col">email</th>
                                <th scope="col">direccion</th>
                                <th scope="col">barrio</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($data as $datos )
                                    <tr>
                                        <th scope="row">{{ $datos->id  }}</th>
                                        <td>{{ $datos->name  }}</td>
                                        <td>{{ $datos->apellido  }}</td>
                                        <td>{{ $datos->email  }}</td> 
                                        <td>{{ $datos->direccion }}</td>
                                        <td>{{ $datos->barrio}}</td>
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
