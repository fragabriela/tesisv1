@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1 class="tesisheader">Bienvenido</h1>
@endsection

@section('content')

    <h1> Editar Carreras </h1>
    <form action="{{ route('carrera.editar') }}" method="POST">
        @csrf

        <div class="container">
            <div class="row ">
                <div class="col-12">

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Nombre</label>
                            <input type="text" value="{{$data->nombre}}" name="nombre" class="form-control" id="nombre"
                                placeholder="Ingrese su carrera: ">
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Descripcion</label>
                            <input type="text" value="{{$data->descripcion}}" name="descripcion" class="form-control" id="descripcion"
                                placeholder="Descripcion de su carrera:">
                        </div>
                    </div>

                    <input type="hidden" value="{{$data->id}}" name="id" class="form-control" id="nombre">
                    <button class="btn btn-primary" type="submit">Enviar</button>
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
