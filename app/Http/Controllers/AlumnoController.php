<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

class AlumnoController extends Controller
{
    //
    public function index(){
        $data=\DB::table('alumnos')->get();

         $carreras = \DB::table('carreras')->get(); // Agregamos esto

         return view('alumno', compact('data', 'carreras'));
    }

    public function guardar(Request $request)
    {
        try {

            $data = $request->all();
             return $data;

            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255',
                'email' => 'required|string|',
                'telefono' => 'required|string|',
                'cedula' => 'required|string|',
                'matricula' => 'required|string|',
                'fecha_nacimiento' => 'required|date|',
            ]);

            if ($validator->fails()) {
                return "Ocurrió un error de validación";
            }

            $respuesta = \DB::table('alumnos')->insert([
                "nombre" => $data["nombre"],
                "email" => $data["email"],
                "telefono" => $data["telefono"],
                "cedula" => $data["cedula"],
                "matricula" => $data["matricula"],
                "fecha_nacimiento" => $data["fecha_nacimiento"],
            ]);

            $data = \DB::table('alumnos')->get(); //traemos todos los datos de la base de datos

            return view('alumnos', compact('data'));
        } catch (\Exception $e) {

            \Log::debug('Ocurrio un error');
        }
    }
   
}
