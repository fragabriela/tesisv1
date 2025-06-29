@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1 class="tesisheader">Bienvenido</h1>
@endsection

@section('content')

    @if ($errors->any())
        <div style="color: red;">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <h1> Alumnos </h1>


    <div class="container-fluid">
        <div class="row ">

            <div class="col-md-2 column-form">
                <form action="{{ route('alumno.guardar') }}" method="POST">
                    @csrf
                    <div class="">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Nombre</label>
                            <input type="text" name="nombre" class="form-control" id="nombre"
                                placeholder="Ingrese su nombre: ">
                        </div>
                    </div>

                    <div class="">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Email</label>
                            <input type="text" name="email" class="form-control" id="email"
                                placeholder="Ingrese su email:">
                        </div>
                    </div>
                    <div class="">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Telefono</label>
                            <input type="text" name="telefono" class="form-control" id="telefono"
                                placeholder="Ingrese su telefono:">
                        </div>
                    </div>
                    <div class="">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Cedula</label>
                            <input type="string" name="cedula" class="form-control" id="cedula"
                                placeholder="Ingrese su cedula:">
                        </div>
                    </div>
                    <div class="">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Matricula</label>
                            <input type="string" name="matricula" class="form-control" id="matricula"
                                placeholder="Ingrese su matricula:">
                        </div>
                    </div>
                    <div class="">
                        <div class="mb-3">
                            <label for="exampleFormControlInput1" class="form-label">Fecha Nacimiento</label>
                            <input type="date" name="fecha_nacimiento" class="form-control" id="fecha_nacimiento"
                                placeholder="Ingrese su fecha de nacimiento:">
                        </div>
                    </div>

                    
                    <div class="mb-3">
                        <label for="carreraSelect" class="form-label custom-label">Selecciona una carrera</label>
                        <select id="carreraSelect" name="carrera_id" class="form-select form-select-lg custom-select"
                            aria-label="Selecciona una carrera">
                            <option selected disabled>-- Elige una carrera --</option>
                            @foreach ($carreras as $carrera)
                                <option value="{{ $carrera->id }}">{{ $carrera->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    
                    <button class="btn btn-primary" type="submit">Enviar</button>
                </form>
            </div>

            <div class="col-md-8 column-alumnos">


                <table class="table">
                    <thead>
                        <tr>
                            {{-- <th scope="col">id</th> --}}
                            <th scope="col">nombre</th>
                            <th scope="col">email</th>
                            <th scope="col">telefono</th>
                            <th scope="col">cedula</th>
                            <th scope="col">matricula</th>
                            {{-- <th scope="col">fecha_nacimiento</th> --}}
                            <th scope="col">carrera</th>
                            <th scope="col">Acciones </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($data as $datos)
                            <tr>
                                {{-- <th scope="row">{{ $datos->id }}</th> --}}
                                <td>{{ $datos->nombre }}</td>
                                <td>{{ $datos->email }}</td>
                                <td>{{ $datos->telefono }}</td>
                                <td>{{ $datos->cedula }}</td>
                                <td>{{ $datos->matricula }}</td>
                                {{-- <td>{{ $datos->fecha_nacimiento }}</td> --}}
                                <td>{{ $datos->carrera_nombre }}</td>
                                <td>
                                    <a class="btn btn-danger"
                                        href="{{ route('alumno.delete', ['id' => $datos->id]) }}">Eliminar</a>
                                    <a class="btn btn-info"
                                        href="{{ route('alumno.update', ['id' => $datos->id]) }}">Editar</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    </body>

    </html>

@endsection

@section('css')
    <style>

        .column-form{
            padding: 0% !important;
            margin: 0% !important;
        }
        .column-alumnos{
            padding: 0% !important;
            margin-left: 0% !important;
        }
        /* Estilo para el label */
        .custom-label {
            font-weight: bold;
            font-size: 1.2rem;
            color: #495057;
            margin-bottom: 0.5rem;
        }

        /* Estilo para el select */
        .custom-select {
            border: 2px solid #0d6efd;
            border-radius: 0.5rem;
            /* padding: 0.75rem; */
            font-size: 1rem;
            transition: box-shadow 0.3s ease;
        }

        .custom-select:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            border-color: #0d6efd;
            outline: none;
        }
    </style>
@endsection
