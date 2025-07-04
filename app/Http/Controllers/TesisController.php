<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Tesis;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class TesisController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Tesis::with(['alumno', 'tutor']);
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('alumno_nombre', function($row){
                    return $row->alumno->nombre . ' ' . $row->alumno->apellido;
                })
                ->addColumn('tutor_nombre', function($row){
                    return $row->tutor->nombre . ' ' . $row->tutor->apellido;
                })
                ->addColumn('estado_badge', function($row){
                    $badgeClass = '';
                    switch($row->estado) {
                        case 'pendiente':
                            $badgeClass = 'warning';
                            break;
                        case 'en_progreso':
                            $badgeClass = 'info';
                            break;
                        case 'completado':
                            $badgeClass = 'success';
                            break;
                        case 'rechazado':
                            $badgeClass = 'danger';
                            break;
                    }
                    return '<span class="badge badge-' . $badgeClass . '">' . ucfirst(str_replace('_', ' ', $row->estado)) . '</span>';
                })
                ->addColumn('action', function($row){
                    $actionBtn = '<a href="'.route('tesis.show', $row->id).'" class="view btn btn-info btn-sm">Ver</a> ';
                    $actionBtn .= '<a href="'.route('tesis.edit', $row->id).'" class="edit btn btn-primary btn-sm">Editar</a> ';
                    $actionBtn .= '<a href="javascript:void(0)" class="delete btn btn-danger btn-sm" onclick="eliminarTesis('.$row->id.')">Eliminar</a>';
                    return $actionBtn;
                })
                ->rawColumns(['estado_badge', 'action'])
                ->make(true);
        }
        
        return view('tesis.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $alumnos = Alumno::where('estado', 'activo')->get();
        $tutores = Tutor::where('activo', true)->get();
        return view('tesis.create', compact('alumnos', 'tutores'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'titulo' => 'required|string|max:255',
                'descripcion' => 'required|string',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
                'alumno_id' => 'required|exists:alumnos,id',
                'tutor_id' => 'required|exists:tutores,id',
                'estado' => 'required|in:pendiente,en_progreso,completado,rechazado',
                'calificacion' => 'nullable|integer|min:0|max:100',
                'observaciones' => 'nullable|string',
                'documento' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $tesis = new Tesis([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'fecha_inicio' => $request->fecha_inicio,
                'fecha_fin' => $request->fecha_fin,
                'alumno_id' => $request->alumno_id,
                'tutor_id' => $request->tutor_id,
                'estado' => $request->estado,
                'calificacion' => $request->calificacion,
                'observaciones' => $request->observaciones,
            ]);

            if ($request->hasFile('documento')) {
                $documento = $request->file('documento');
                $nombreArchivo = time() . '_' . $documento->getClientOriginalName();
                $path = $documento->storeAs('documentos_tesis', $nombreArchivo, 'public');
                $tesis->documento_url = $path;
            }

            $tesis->save();

            return redirect()->route('tesis.index')->with('success', 'Tesis creada exitosamente');
        } catch (\Exception $e) {
            \Log::error('Error al crear tesis: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ocurrió un error al crear la tesis')->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tesis = Tesis::with(['alumno', 'tutor'])->findOrFail($id);
        return view('tesis.show', compact('tesis'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $tesis = Tesis::findOrFail($id);
        $alumnos = Alumno::where('estado', 'activo')->get();
        $tutores = Tutor::where('activo', true)->get();
        return view('tesis.edit', compact('tesis', 'alumnos', 'tutores'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $tesis = Tesis::findOrFail($id);
            
            $validator = Validator::make($request->all(), [
                'titulo' => 'required|string|max:255',
                'descripcion' => 'required|string',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
                'alumno_id' => 'required|exists:alumnos,id',
                'tutor_id' => 'required|exists:tutores,id',
                'estado' => 'required|in:pendiente,en_progreso,completado,rechazado',
                'calificacion' => 'nullable|integer|min:0|max:100',
                'observaciones' => 'nullable|string',
                'documento' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $tesis->titulo = $request->titulo;
            $tesis->descripcion = $request->descripcion;
            $tesis->fecha_inicio = $request->fecha_inicio;
            $tesis->fecha_fin = $request->fecha_fin;
            $tesis->alumno_id = $request->alumno_id;
            $tesis->tutor_id = $request->tutor_id;
            $tesis->estado = $request->estado;
            $tesis->calificacion = $request->calificacion;
            $tesis->observaciones = $request->observaciones;

            if ($request->hasFile('documento')) {
                // Delete previous file if exists
                if ($tesis->documento_url) {
                    Storage::disk('public')->delete($tesis->documento_url);
                }
                
                $documento = $request->file('documento');
                $nombreArchivo = time() . '_' . $documento->getClientOriginalName();
                $path = $documento->storeAs('documentos_tesis', $nombreArchivo, 'public');
                $tesis->documento_url = $path;
            }

            $tesis->save();

            return redirect()->route('tesis.index')->with('success', 'Tesis actualizada exitosamente');
        } catch (\Exception $e) {
            \Log::error('Error al actualizar tesis: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ocurrió un error al actualizar la tesis')->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $tesis = Tesis::findOrFail($id);
            
            // Delete document if exists
            if ($tesis->documento_url) {
                Storage::disk('public')->delete($tesis->documento_url);
            }
            
            $tesis->delete();
            
            return response()->json(['success' => true, 'message' => 'Tesis eliminada exitosamente']);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar tesis: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Ocurrió un error al eliminar la tesis']);
        }
    }
    
    /**
     * Export tesis to PDF
     */
    public function exportPDF()
    {
        $tesis = Tesis::with(['alumno', 'tutor'])->get();
        $pdf = PDF::loadView('exports.tesis_pdf', compact('tesis'));
        return $pdf->download('tesis.pdf');
    }
    
    /**
     * Export tesis to Excel
     */
    public function exportExcel()
    {
        return Excel::download(new \App\Exports\TesisExport, 'tesis.xlsx');
    }
}
