@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1 class="tesisheader">Bienvenido</h1>
@endsection

@section('content')

    <h1> Editar Alumnos </h1>
    <form action="{{ route('alumno.editar') }}" method="POST">
        @csrf

        <div class="container">
            <div class="row ">
                <div class="col-6">

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Nombre</label>
                            <input type="text" value="{{ $data->nombre }}" name="nombre" class="form-control"
                                id="nombre" placeholder="Ingrese su nombre: ">
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Email</label>
                            <input type="text" value="{{ $data->email }}" name="email" class="form-control"
                                id="email" placeholder="Ingrese su email:">
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Telefono</label>
                            <input type="text" value="{{ $data->telefono }}" name="telefono" class="form-control"
                                id="telefono" placeholder="Ingrese su telefono:">
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Cedula</label>
                            <input type="string" value="{{ $data->cedula }}" name="cedula" class="form-control"
                                id="cedula" placeholder="Ingrese su cedula:">
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Matricula</label>
                            <input type="string" value="{{ $data->matricula }}" name="matricula" class="form-control"
                                id="matricula" placeholder="Ingrese su matricula:">
                        </div>
                    </div>

                    <div class="col-6">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Fecha de nacimiento</label>
                            <input type="date" value="{{ $data->fecha_nacimiento }}" name="fecha_nacimiento"
                                class="form-control" id="fecha_nacimiento" placeholder="Ingrese su fecha de nacimiento:">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="carreraSelect" class="form-label custom-label">Selecciona una carrera</label>
                        <select id="carreraSelect" name="carrera_id" class="form-select form-select-lg custom-select"
                            aria-label="Selecciona una carrera">
                            <option disabled {{ old('carrera_id', $data->carrera_id ?? '') == '' ? 'selected' : '' }}>--
                                Elige una carrera --</option>
                            @foreach ($carreras as $carrera)
                                <option value="{{ $carrera->id }}"
                                    {{ old('carrera_id', $data->carrera_id ?? '') == $carrera->id ? 'selected' : '' }}>
                                    {{ $carrera->nombre }}
                                </option>
                            @endforeach
                        </select>

                    </div>

                    <input type="hidden" value="{{ $data->id }}" name="id" class="form-control" id="nombre">
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
