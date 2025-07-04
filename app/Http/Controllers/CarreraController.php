<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Exports\CarrerasExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class CarreraController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Carrera::select(['id', 'nombre', 'descripcion', 'activo', 'created_at']);
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $actionBtn = '<a href="'.route('carrera.show', $row->id).'" class="view btn btn-info btn-sm">Ver</a> ';
                    $actionBtn .= '<a href="'.route('carrera.edit', $row->id).'" class="edit btn btn-primary btn-sm">Editar</a> ';
                    $actionBtn .= '<a href="javascript:void(0)" class="delete btn btn-danger btn-sm" onclick="eliminarCarrera('.$row->id.')">Eliminar</a>';
                    return $actionBtn;
                })
                ->editColumn('activo', function($row){
                    return $row->activo ? 'Activo' : 'Inactivo';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('carreras.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('carreras.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            \Log::info('Carrera Store - Request Method: ' . $request->method());
            \Log::info('Carrera Store - Request URL: ' . $request->url());
            \Log::info('Carrera Store - Request has token: ' . ($request->has('_token') ? 'Yes' : 'No'));
            \Log::info('Carrera Store - Request data: ' . print_r($request->all(), true));
            \Log::info('Carrera Store - Current user: ' . (auth()->user() ? auth()->user()->name : 'No user'));
            \Log::info('Carrera Store - User permissions: ' . print_r(auth()->user() ? auth()->user()->getAllPermissions()->pluck('name') : 'None', true));

            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255',
                'descripcion' => 'required|string',
                'activo' => 'nullable'
            ]);

            if ($validator->fails()) {
                \Log::error('Validation failed: ' . print_r($validator->errors()->toArray(), true));
                return redirect()->back()->withErrors($validator)->withInput();
            }

            \Log::info('Creating new carrera with: ' . json_encode([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'activo' => $request->has('activo')
            ]));

            $carrera = new Carrera();
            $carrera->nombre = $request->nombre;
            $carrera->descripcion = $request->descripcion;
            $carrera->activo = $request->has('activo');
            $carrera->save();

            \Log::info('Carrera created: ' . print_r($carrera->toArray(), true));

            return redirect()->route('carrera.index')->with('success', 'Carrera creada exitosamente');
        } catch (\Exception $e) {
            \Log::error('Error al guardar carrera: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());
            return redirect()->back()->with('error', 'Ocurrió un error al guardar la carrera: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $carrera = Carrera::with('alumnos')->findOrFail($id);
            return view('carreras.show', compact('carrera'));
        } catch (\Exception $e) {
            \Log::error('Error al mostrar carrera: ' . $e->getMessage());
            return redirect()->route('carrera.index')->with('error', 'Carrera no encontrada');
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $carrera = Carrera::findOrFail($id);
            return view('carreras.edit', compact('carrera'));
        } catch (\Exception $e) {
            \Log::error('Error al obtener carrera: ' . $e->getMessage());
            return redirect()->route('carrera.index')->with('error', 'Carrera no encontrada');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            \Log::info('Update request data for ID ' . $id . ': ' . print_r($request->all(), true));
            
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255',
                'descripcion' => 'required|string',
                'activo' => 'nullable|sometimes'
            ]);

            if ($validator->fails()) {
                \Log::error('Validation failed in update: ' . print_r($validator->errors()->toArray(), true));
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $carrera = Carrera::findOrFail($id);
            $carrera->nombre = $request->nombre;
            $carrera->descripcion = $request->descripcion;
            $carrera->activo = $request->has('activo');
            $carrera->save();
            
            \Log::info('Carrera updated: ' . print_r($carrera->toArray(), true));

            return redirect()->route('carrera.index')->with('success', 'Carrera actualizada exitosamente');
        } catch (\Exception $e) {
            \Log::error('Error al editar carrera: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ocurrió un error al editar la carrera')->withInput();
        }
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $carrera = Carrera::findOrFail($id);
            $carrera->delete();
            
            return response()->json(['success' => true, 'message' => 'Carrera eliminada exitosamente']);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar carrera: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'No se puede eliminar esta carrera porque está siendo utilizada']);
        }
    }
    
    /**
     * Export carreras to PDF
     */
    public function exportPDF()
    {
        $carreras = Carrera::all();
        $pdf = PDF::loadView('exports.carreras_pdf', compact('carreras'));
        return $pdf->download('carreras.pdf');
    }
    
    /**
     * Export carreras to Excel
     */
    public function exportExcel()
    {
        return Excel::download(new CarrerasExport, 'carreras.xlsx');
    }
}
