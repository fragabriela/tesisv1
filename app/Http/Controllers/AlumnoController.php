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
            // return $data;

            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255',
                'email' => 'required|string|max:255',
                'telefono' => 'required|string|max:20',
                'cedula' => 'required|string|max:20',
                'matricula' => 'required|string|max:20',
                'fecha_nacimiento' => 'required|date|',
                'carrera_id' => 'required|exists:carreras,id', // opcional si usas relación
            ]);

            if ($validator->fails()) {
                // return "Ocurrió un error de validación";
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $respuesta = \DB::table('alumnos')->insert([
                "nombre" => $data["nombre"],
                "email" => $data["email"],
                "telefono" => $data["telefono"],
                "cedula" => $data["cedula"],
                "matricula" => $data["matricula"],
                "fecha_nacimiento" => $data["fecha_nacimiento"],
                "carrera_id" => $data["carrera_id"],
            ]);

            $data = \DB::table('alumnos')->get(); //traemos todos los datos de la base de datos
            $carreras = DB::table('carreras')->get();

            return view('alumno', compact('data', 'carreras'))
            ->with('success', 'Alumno guardado correctamente');

        } catch (\Exception $e) {

            \Log::debug('Error al guardar alumno: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ocurrió un error al guardar el alumno');
        }
    }
   
}
