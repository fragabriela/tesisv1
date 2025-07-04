<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Validator;

class MiFormularioController extends Controller
{
    public function index(Request $request)
    {
       
        // Ahora puedes usar $validatedData['nombre'] y $validatedDate['email']
        $data = \DB::table('formularios')->get();
        return view('tesisv3',compact('data'));
    }
    
    public function guardar(Request $request)
    {
        try{

                    $data = $request->all();

                    $validator = Validator::make($request->all(), [
                        'nombre' => 'required|string|max:255',
                        'apellido' => 'required|string',
                        'email' => 'required|string',
                        'password' => 'required|string',
                        'direccion' => 'required|string',
                        'barrio' => 'required|string',
                        'telefono' => 'required|numeric',
                    ]);

                    if ($validator->fails()) {
                        return "Ocurrió un error de validación";
                    }

                    $respuesta = \DB::table('formularios')->insert(
                        [
                            "name" => $data["nombre"],
                            "apellido" => $data["apellido"],
                            "email" => $data["email"],
                            "password" => $data["password"],
                            "direccion" => $data["direccion"],
                            "barrio" => $data["barrio"],
                            "telefono" => $data["telefono"],
                        ]
                    );

                    $data = \DB::table('formularios')->get(); //traemos todos los datos de la base de datos
                    
                    // Ahora puedes usar $validatedData['nombre'] y $validatedDate['email']
                    return view('tesisv3',compact('data'));

        } catch(\Exception $e){

            \Log::debug('Ocurrio un error');
        }
    }
}