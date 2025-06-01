<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MiFormularioController extends Controller
{
    public function guardar(Request $request)
    {
        //logica de negocio
        $validatedDate = $_request->validate([
            'nombre' => 'required|string|max:255',
            'email' => 'required|email',
            return view('tesisv3', compact('nombre'));
        ]);
    // Ahora puedes usar $validatedData['nombre'] y $validatedDate['email']
    }
}