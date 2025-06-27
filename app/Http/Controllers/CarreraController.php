<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

class CarreraController extends Controller
{
    public function index(Request $request)
    {
        // ahora puedes validar
        $data = \DB::table('carreras')->get();
        return view('carrera', compact('data'));
    }

    public function guardar(Request $request)
    {
        try {

            $data = $request->all();

            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255',
                'descripcion' => 'required|string|',

            ]);

            if ($validator->fails()) {
                return "Ocurrió un error de validación";
            }

            $respuesta = \DB::table('carreras')->insert([
                "nombre" => $data["nombre"],
                "descripcion" => $data["descripcion"],
            ]);

            $data = \DB::table('carreras')->get(); //traemos todos los datos de la base de datos

            return view('carrera', compact('data'));
        } catch (\Exception $e) {

            \Log::debug('Ocurrio un error');
        }
    }


    public function delete($id)
    {

        $resultado = \DB::table('carreras')->where('id', $id)->delete();

        $data = \DB::table('carreras')->get(); //traemos todos los datos de la base de datos

        return view('carrera', compact('data'));
    }



    public function update($id)
    {
        try {

            $data = \DB::table('carreras')->where('id', $id)->first(); //traemos todos los datos de la base de datos
            
            return view('carreraedit', compact('data'));
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
                'descripcion' => 'required|string|',

            ]);

            if ($validator->fails()) {
                return "Ocurrió un error de validación";
            }

            $respuesta = \DB::table('carreras')->where('id', $data["id"])->update([
                "nombre" => $data["nombre"],
                "descripcion" => $data["descripcion"],
            ]);

            $data = \DB::table('carreras')->get(); //traemos todos los datos de la base de datos

            return view('carrera', compact('data'));
        } catch (\Exception $e) {

            \Log::debug('Ocurrio un error');
        }
    }
}
