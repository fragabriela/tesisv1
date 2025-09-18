@extends('adminlte::page')

@section('title', 'Sin Permisos')

@section('content_header')
    <h1>Sin Permisos de Acceso</h1>
@stop

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-warning">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-exclamation-triangle"></i>
                    Acceso Restringido
                </h3>
            </div>
            <div class="card-body">
                <div class="text-center">
                    <i class="fas fa-lock fa-5x text-warning mb-4"></i>
                    <h4>No tienes permisos para acceder a ningún módulo del sistema</h4>
                    <p class="text-muted">
                        Tu cuenta no tiene permisos asignados para acceder a los módulos disponibles. 
                        Por favor, contacta al administrador del sistema para solicitar los permisos necesarios.
                    </p>
                    
                    <div class="mt-4">
                        <h5>Información de tu cuenta:</h5>
                        <div class="bg-light p-3 rounded">
                            <strong>Usuario:</strong> {{ auth()->user()->name }}<br>
                            <strong>Email:</strong> {{ auth()->user()->email }}<br>
                            <strong>Rol:</strong> 
                            @if(auth()->user()->roles->count() > 0)
                                {{ auth()->user()->roles->pluck('name')->join(', ') }}
                            @else
                                <span class="text-muted">Sin rol asignado</span>
                            @endif
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <a href="{{ route('logout') }}" 
                           class="btn btn-danger"
                           onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                        
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
<style>
    .card-warning {
        border-color: #ffc107;
    }
    .card-warning .card-header {
        background-color: #ffc107;
        color: #212529;
    }
</style>
@stop