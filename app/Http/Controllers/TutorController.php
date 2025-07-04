<?php

namespace App\Http\Controllers;

use App\Models\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;

class TutorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Tutor::select(['id', 'nombre', 'apellido', 'email', 'telefono', 'especialidad', 'activo', 'created_at']);
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('action', function($row){
                    $actionBtn = '<a href="'.route('tutor.edit', $row->id).'" class="edit btn btn-primary btn-sm">Editar</a> ';
                    $actionBtn .= '<a href="javascript:void(0)" class="delete btn btn-danger btn-sm" onclick="eliminarTutor('.$row->id.')">Eliminar</a>';
                    return $actionBtn;
                })
                ->editColumn('activo', function($row){
                    return $row->activo ? 'Activo' : 'Inactivo';
                })
                ->rawColumns(['action'])
                ->make(true);
        }
        return view('tutores.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('tutores.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Log all incoming data for debugging
            \Log::info('Tutor Store - Request data: ' . json_encode($request->all()));
            \Log::info('Tutor Store - Request Method: ' . $request->method());
            \Log::info('Tutor Store - Request URL: ' . $request->url());
            
            // Emergency direct database insertion to bypass any potential middleware issues
            try {
                \DB::beginTransaction();
                
                // Use direct database insertion first
                $data = [
                    'nombre' => $request->input('nombre'),
                    'apellido' => $request->input('apellido'),
                    'email' => $request->input('email'),
                    'telefono' => $request->input('telefono'),
                    'especialidad' => $request->input('especialidad'),
                    'biografia' => $request->input('biografia'),
                    'activo' => $request->has('activo') ? true : false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                // Log the final data before insertion
                \Log::info('Data to insert: ' . json_encode($data));
                $tutorId = \DB::table('tutores')->insertGetId($data);
                
                // Verify that the data was actually saved
                $savedTutor = \DB::table('tutores')->where('id', $tutorId)->first();
                
                if (!$savedTutor) {
                    throw new \Exception('Failed to retrieve newly created tutor with ID: ' . $tutorId);
                }
                
                \DB::commit();
                \Log::info('Direct DB insertion successful, ID: ' . $tutorId);
                
                return redirect()->route('tutor.index')
                    ->with('success', 'Tutor creado exitosamente (ID: ' . $tutorId . ')');
            } 
            catch (\Exception $dbException) {
                \DB::rollback();
                \Log::error('Direct DB insertion failed: ' . $dbException->getMessage());
                
                // Fall back to original implementation with validation if direct insertion fails
                $validator = Validator::make($request->all(), [
                    'nombre' => 'required|string|max:255',
                    'apellido' => 'required|string|max:255',
                    'email' => 'required|email|unique:tutores,email',
                    'telefono' => 'required|string|max:20',
                    'especialidad' => 'required|string|max:255',
                    'biografia' => 'nullable|string',
                    'activo' => 'sometimes|boolean'
                ]);
    
                if ($validator->fails()) {
                    \Log::error('Tutor validation failed: ' . print_r($validator->errors()->toArray(), true));
                    return redirect()->back()->withErrors($validator)->withInput();
                }
    
                // Start a new transaction for the Eloquent approach
                \DB::beginTransaction();
                
                try {
                    // Create tutor using Eloquent
                    $tutor = new Tutor();
                    $tutor->nombre = $request->nombre;
                    $tutor->apellido = $request->apellido;
                    $tutor->email = $request->email;
                    $tutor->telefono = $request->telefono;
                    $tutor->especialidad = $request->especialidad;
                    $tutor->biografia = $request->biografia;
                    $tutor->activo = $request->has('activo') ? true : false;
                    $tutor->save();
                    
                    // Verify the save was successful
                    $saveData = [
                        'nombre' => $request->nombre,
                        'apellido' => $request->apellido,
                        'email' => $request->email,
                        'telefono' => $request->telefono,
                        'especialidad' => $request->especialidad,
                        'biografia' => $request->biografia,
                        'activo' => $request->has('activo') ? true : false,
                    ];
                    
                    $verified = $tutor->verifyCreate($saveData);
                    
                    if (!$verified) {
                        throw new \Exception('Failed to verify tutor creation');
                    }
                    
                    \DB::commit();
                    \Log::info('Tutor created successfully through model: ' . $tutor->id);
                    
                    return redirect()->route('tutor.index')
                        ->with('success', 'Tutor creado exitosamente');
                } catch (\Exception $modelException) {
                    \DB::rollback();
                    \Log::error('Eloquent creation failed: ' . $modelException->getMessage());
                    throw $modelException; // Let the outer catch handle this
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error al guardar tutor: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Ocurrió un error al guardar el tutor: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tutor = Tutor::findOrFail($id);
        return view('tutores.show', compact('tutor'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $tutor = Tutor::findOrFail($id);
        return view('tutores.edit', compact('tutor'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            // Enhanced debugging logs
            \Log::info('====== START: TUTOR UPDATE ======');
            \Log::info('Tutor Update - Request data for ID ' . $id . ': ' . print_r($request->all(), true));
            \Log::info('Tutor Update - Request Method: ' . $request->method());
            \Log::info('Tutor Update - Request URL: ' . $request->url());
            \Log::info('Tutor Update - Request has token: ' . ($request->has('_token') ? 'Yes' : 'No'));
            
            // First try to find the tutor
            $tutor = Tutor::findOrFail($id);
            \Log::info('Tutor Update - Found tutor: ' . $tutor->id . ' - ' . $tutor->nombre . ' ' . $tutor->apellido);
            
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255',
                'apellido' => 'required|string|max:255',
                'email' => 'required|email|unique:tutores,email,' . $id,
                'telefono' => 'required|string|max:20',
                'especialidad' => 'required|string|max:255',
                'biografia' => 'nullable|string',
                'activo' => 'sometimes|boolean'
            ]);

            if ($validator->fails()) {
                \Log::error('Tutor update validation failed: ' . print_r($validator->errors()->toArray(), true));
                return redirect()->back()->withErrors($validator)->withInput();
            }

            // Start a database transaction for safety
            \DB::beginTransaction();
            
            try {
                // Try to update with direct SQL first for troubleshooting
                $updateData = [
                    'nombre' => $request->nombre,
                    'apellido' => $request->apellido,
                    'email' => $request->email,
                    'telefono' => $request->telefono,
                    'especialidad' => $request->especialidad,
                    'biografia' => $request->biografia,
                    'activo' => $request->has('activo') ? true : false,
                    'updated_at' => now(),
                ];
                
                // Log direct SQL update attempt
                \Log::info('Attempting direct SQL update with data: ' . print_r($updateData, true));
                
                // Execute the direct SQL update
                $affected = \DB::table('tutores')
                    ->where('id', $id)
                    ->update($updateData);
                    
                \Log::info('Direct SQL update result: ' . $affected . ' rows affected');
                
                // Also try the Eloquent update as a backup
                $tutor->nombre = $request->nombre;
                $tutor->apellido = $request->apellido;
                $tutor->email = $request->email;
                $tutor->telefono = $request->telefono;
                $tutor->especialidad = $request->especialidad;
                $tutor->biografia = $request->biografia;
                $tutor->activo = $request->has('activo') ? true : false;
                
                // Force the model to recognize the changes
                $tutor->syncOriginal();
                $tutor->syncChanges();
                
                // Check if any fields actually changed
                $changes = $tutor->getDirty();
                \Log::info('Tutor changes to be saved via Eloquent: ' . print_r($changes, true));
                
                // Add save result to debug output
                $saveResult = $tutor->save();
                \Log::info('Tutor Eloquent save result: ' . ($saveResult ? 'true' : 'false'));
                
                // Verify that changes were applied by reloading the model
                $updatedTutor = Tutor::find($id);
                \Log::info('Reloaded tutor after update: ' . print_r($updatedTutor->toArray(), true));
                
                // Verify the update with our new verification function
                $updateAttributes = [
                    'nombre' => $request->nombre,
                    'apellido' => $request->apellido,
                    'email' => $request->email,
                    'telefono' => $request->telefono,
                    'especialidad' => $request->especialidad,
                ];
                
                $verified = $updatedTutor->verifyUpdate($updateAttributes);
                \Log::info('Update verification result: ' . ($verified ? 'Verified OK' : 'Verification FAILED'));
                
                // Commit transaction if both updates were successful
                \DB::commit();
                \Log::info('Transaction committed successfully');
                
                \Log::info('Tutor updated successfully: ' . $tutor->id);
                \Log::info('====== END: TUTOR UPDATE ======');
                
                return redirect()->route('tutor.index')
                    ->with('success', 'Tutor actualizado exitosamente' . (!$verified ? ' (con advertencia)' : ''));
                
            } catch (\Exception $dbException) {
                \DB::rollBack();
                \Log::error('Database error during update: ' . $dbException->getMessage());
                throw $dbException;
            }
        } catch (\Exception $e) {
            \Log::error('Error al actualizar tutor: ' . $e->getMessage());
            \Log::error('Exception trace: ' . $e->getTraceAsString());
            \Log::info('====== END: TUTOR UPDATE (ERROR) ======');
            return redirect()->back()
                ->with('error', 'Ocurrió un error al actualizar el tutor: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $tutor = Tutor::findOrFail($id);
            $tutor->delete();
            
            return response()->json(['success' => true, 'message' => 'Tutor eliminado exitosamente']);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar tutor: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'No se puede eliminar este tutor porque está siendo utilizado']);
        }
    }
    
    /**
     * Export tutores to PDF
     */
    public function exportPDF()
    {
        $tutores = Tutor::all();
        $pdf = PDF::loadView('exports.tutores_pdf', compact('tutores'));
        return $pdf->download('tutores.pdf');
    }
    
    /**
     * Export tutores to Excel
     */
    public function exportExcel()
    {
        return Excel::download(new TutoresExport, 'tutores.xlsx');
    }
}
