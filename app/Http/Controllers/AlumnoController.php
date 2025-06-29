<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

class AlumnoController extends Controller
{
    //
    public function index(){

        $data = \DB::table('alumnos as a')
        ->join('carreras as c', 'a.id_carrera', '=', 'c.id')
        ->select('a.*', 'c.nombre as carrera_nombre')
        ->get();


        $carreras = \DB::table('carreras')->get(); // Agregamos esto

        return view('alumno', compact('data', 'carreras'));
    }

    public function guardar(Request $request)
    {
        try {

            $data = $request->all();

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
                "id_carrera" => $data["carrera_id"],
                "id_carrera" => $data["carrera_id"],
            ]);

               
        $data = \DB::table('alumnos as a')
        ->join('carreras as c', 'a.id_carrera', '=', 'c.id')
        ->select('a.*', 'c.nombre as carrera_nombre')
        ->get();
            $carreras = \DB::table('carreras')->get();

            return view('alumno', compact('data', 'carreras'))
            ->with('success', 'Alumno guardado correctamente');

        } catch (\Exception $e) {

            \Log::debug('Error al guardar alumno: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ocurrió un error al guardar el alumno');
        }
    }
   
   public function delete($id)
    {

        $resultado = \DB::table('alumnos')->where('id', $id)->delete();

           
        $data = \DB::table('alumnos as a')
        ->join('carreras as c', 'a.id_carrera', '=', 'c.id')
        ->select('a.*', 'c.nombre as carrera_nombre')
        ->get();

        $carreras = \DB::table('carreras')->get(); // Agregamos esto

        return view('alumno', compact('data','carreras'));
    }
    public function update($id)
    {
        try {

            $data = \DB::table('alumnos')->where('id', $id)->first(); //traemos todos los datos de la base de datos
            $carreras = \DB::table('carreras')->get(); // Agregamos esto
            return view('alumnoedit', compact('data','carreras'));
        } catch (\Exception $e) {

            \Log::debug($e->getMessage());
        }
    }

    public function editar(Request $request)
    {
        try {

            $data = $request->all();

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

            $respuesta = \DB::table('alumnos')->where('id', $data["id"])->update([
                "nombre" => $data["nombre"],
                "email" => $data["email"],
                "telefono" => $data["telefono"],
                "cedula" => $data["cedula"],
                "matricula" => $data["matricula"],
                "fecha_nacimiento" => $data["fecha_nacimiento"],
            ]);

               
            $data = \DB::table('alumnos as a')
            ->join('carreras as c', 'a.id_carrera', '=', 'c.id')
            ->select('a.*', 'c.nombre as carrera_nombre')
            ->get();

            $carreras = \DB::table('carreras')->get(); // Agregamos esto

            return view('alumno', compact('data','carreras'));

        } catch (\Exception $e) {

            \Log::debug('Ocurrio un error');
        }
    }
}