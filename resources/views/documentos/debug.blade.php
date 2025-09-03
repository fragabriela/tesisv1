@extends('adminlte::page')

@section('title', 'Debug - Crear Documento')

@section('content_header')
    <h1>Debug - Estado de Autenticación y Formulario</h1>
@stop

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Estado de Autenticación</h3>
            </div>
            <div class="card-body">
                <p><strong>Usuario autenticado:</strong> {{ Auth::check() ? 'Sí' : 'No' }}</p>
                @if(Auth::check())
                    <p><strong>ID de usuario:</strong> {{ Auth::id() }}</p>
                    <p><strong>Nombre:</strong> {{ Auth::user()->name }}</p>
                    <p><strong>Email:</strong> {{ Auth::user()->email }}</p>
                    <p><strong>Roles:</strong> 
                        @foreach(Auth::user()->roles as $role)
                            {{ $role->name }}{{ !$loop->last ? ', ' : '' }}
                        @endforeach
                    </p>
                    <p><strong>Permisos de documento:</strong></p>
                    <ul>
                        @foreach(Auth::user()->getAllPermissions() as $permission)
                            @if(Str::contains($permission->name, 'documento'))
                                <li>{{ $permission->name }}</li>
                            @endif
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Datos Disponibles</h3>
            </div>
            <div class="card-body">
                <p><strong>Cantidad de Tesis:</strong> {{ App\Models\Tesis::count() }}</p>
                <p><strong>Primera Tesis:</strong></p>
                @php
                    $primeratesis = App\Models\Tesis::first();
                @endphp
                @if($primeratesis)
                    <ul>
                        <li><strong>ID:</strong> {{ $primeratesis->id }}</li>
                        <li><strong>Título:</strong> {{ $primeratesis->titulo }}</li>
                        <li><strong>Alumno ID:</strong> {{ $primeratesis->alumno_id }}</li>
                        <li><strong>Tutor ID:</strong> {{ $primeratesis->tutor_id }}</li>
                        <li><strong>Alumno:</strong> {{ $primeratesis->alumno ? $primeratesis->alumno->nombre . ' ' . $primeratesis->alumno->apellido : 'No encontrado' }}</li>
                        <li><strong>Tutor:</strong> {{ $primeratesis->tutor ? $primeratesis->tutor->nombre . ' ' . $primeratesis->tutor->apellido : 'No encontrado' }}</li>
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Test de Formulario Simplificado</h3>
            </div>
            <form action="{{ route('documento.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <div class="form-group">
                        <label for="titulo">Título del Documento</label>
                        <input type="text" class="form-control" id="titulo" name="titulo" value="{{ old('titulo', 'Documento de Prueba') }}" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="tesis_id">Tesis Asociada</label>
                        <select class="form-control" id="tesis_id" name="tesis_id" required>
                            <option value="1" selected>Tesis ID 1 (Test)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="descripcion">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3">{{ old('descripcion', 'Esta es una descripción de prueba para verificar que el formulario funciona correctamente.') }}</textarea>
                    </div>
                </div>

                <div class="card-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Crear Documento (Prueba)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@stop