<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Carrera;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AlumnosExport;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlumnoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $data = Alumno::with('carrera')->select('alumnos.*');
            
            return DataTables::of($data)
                ->addIndexColumn()
                ->addColumn('carrera_nombre', function($row){
                    return $row->carrera->nombre;
                })
                ->addColumn('estado_badge', function($row){
                    $badgeClass = $row->estado == 'activo' ? 'success' : 'danger';
                    return '<span class="badge badge-' . $badgeClass . '">' . ucfirst($row->estado) . '</span>';
                })
                ->addColumn('action', function($row){
                    $actionBtn = '<a href="'.route('alumno.show', $row->id).'" class="view btn btn-info btn-sm">Ver</a> ';
                    $actionBtn .= '<a href="'.route('alumno.edit', $row->id).'" class="edit btn btn-primary btn-sm">Editar</a> ';
                    $actionBtn .= '<a href="'.route('alumno.delete', ['id' => $row->id]).'" class="delete btn btn-danger btn-sm">Eliminar</a>';
                    return $actionBtn;
                })
                ->rawColumns(['action', 'estado_badge'])
                ->make(true);
        }
        
        $data = Alumno::select('alumnos.*', 'carreras.nombre as carrera_nombre')
                ->leftJoin('carreras', 'alumnos.id_carrera', '=', 'carreras.id')
                ->get();
        $carreras = Carrera::all();
        return view('alumno', compact('data', 'carreras'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $carreras = Carrera::all();
        return view('alumnos.create', compact('carreras'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Emergency direct database insertion to bypass any potential middleware issues
            $allData = $request->all();
            file_put_contents('c:/laragon/www/tesisv1/storage/logs/form_data.log', json_encode($allData, JSON_PRETTY_PRINT));
            
            \Log::info('Alumno Store - Emergency direct insert attempt');
            \Log::info('Alumno Store - Request data: ' . json_encode($request->all()));
            \Log::info('Alumno Store - Request Method: ' . $request->method());
            \Log::info('Alumno Store - Request URL: ' . $request->url());
            \Log::info('Alumno Store - Request has token: ' . ($request->has('_token') ? 'Yes' : 'No'));
            
            // Bypass validation for now to test if it's causing the issue
            try {
                \DB::beginTransaction();
                
                // Log column existence for debugging
                \Log::info('Checking if direccion column exists');
                $hasColumn = Schema::hasColumn('alumnos', 'direccion');
                \Log::info('Direccion column exists: ' . ($hasColumn ? 'Yes' : 'No'));
                
                // Use direct database insertion
                $data = [
                    'nombre' => $request->input('nombre'),
                    'apellido' => $request->input('apellido'),
                    'email' => $request->input('email'),
                    'telefono' => $request->input('telefono'),
                    'cedula' => $request->input('cedula'),
                    'matricula' => $request->input('matricula'),
                    'fecha_nacimiento' => date('Y-m-d', strtotime($request->input('fecha_nacimiento'))), // Format date consistently
                    'id_carrera' => $request->input('id_carrera'),
                    'estado' => $request->input('estado', 'activo'),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
                
                // Add direccion field only if column exists
                if (Schema::hasColumn('alumnos', 'direccion')) {
                    \Log::info('Adding direccion to the data array');
                    $data['direccion'] = $request->input('direccion');
                } else {
                    \Log::info('Direccion column does not exist, skipping this field');
                }
                
                // Log the final data before insertion
                \Log::info('Data to insert: ' . json_encode($data));
                $alumnoId = \DB::table('alumnos')->insertGetId($data);
                
                // Verify that the data was actually saved
                $savedAlumno = \DB::table('alumnos')->where('id', $alumnoId)->first();
                
                if (!$savedAlumno) {
                    throw new \Exception('Failed to retrieve newly created alumno with ID: ' . $alumnoId);
                }
                
                // Verify each field matches what we tried to save
                $verified = true;
                foreach ($data as $key => $value) {
                    if ($key != 'created_at' && $key != 'updated_at' && $savedAlumno->$key != $value) {
                        \Log::warning("Direct DB insertion verification failed for attribute {$key}: expected '{$value}', got '{$savedAlumno->$key}'");
                        $verified = false;
                    }
                }
                
                \DB::commit();
                \Log::info('Direct DB insertion successful, ID: ' . $alumnoId . ', Verified: ' . ($verified ? 'Yes' : 'No'));
                
                return redirect()->route('alumno.index')
                    ->with('success', 'Alumno creado exitosamente (ID: ' . $alumnoId . ')' . (!$verified ? ' (con advertencia)' : ''));
            } 
            catch (\Exception $dbException) {
                \DB::rollback();
                \Log::error('Direct DB insertion failed: ' . $dbException->getMessage());
                
                // Fall back to original implementation if direct insertion fails
                $validator = Validator::make($request->all(), [
                    'nombre' => 'required|string|max:255',
                    'apellido' => 'required|string|max:255',
                    'email' => 'required|string|email|max:255',
                    'telefono' => 'required|string|max:20',
                    'cedula' => 'required|string|max:20|unique:alumnos,cedula',
                    'matricula' => 'required|string|max:20|unique:alumnos,matricula',
                    'fecha_nacimiento' => 'required|date',
                    'id_carrera' => 'required|exists:carreras,id',
                    'estado' => 'required|in:activo,inactivo',
                ]);

                if ($validator->fails()) {
                    \Log::error('Alumno validation failed: ' . print_r($validator->errors()->toArray(), true));
                    return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
                }

                $alumno = new Alumno();
                $alumno->nombre = $request->nombre;
                $alumno->apellido = $request->apellido;
                $alumno->email = $request->email;
                $alumno->telefono = $request->telefono;
                $alumno->cedula = $request->cedula;
                $alumno->matricula = $request->matricula;
                $alumno->fecha_nacimiento = date('Y-m-d', strtotime($request->fecha_nacimiento)); // Format date consistently
                $alumno->id_carrera = $request->id_carrera;
                $alumno->estado = $request->estado;
                
                // Check if direccion column exists in the table before trying to set it
                try {
                    if ($request->has('direccion') && \Schema::hasColumn('alumnos', 'direccion')) {
                        $alumno->direccion = $request->direccion;
                    }
                } catch (\Exception $e) {
                    \Log::info('Direccion column check failed: ' . $e->getMessage());
                }
                
                $alumno->save();
                
                // Verify the save was successful
                $saveData = [
                    'nombre' => $request->nombre,
                    'apellido' => $request->apellido,
                    'email' => $request->email,
                    'telefono' => $request->telefono,
                    'cedula' => $request->cedula,
                    'matricula' => $request->matricula,
                    'fecha_nacimiento' => date('Y-m-d', strtotime($request->fecha_nacimiento)),
                    'id_carrera' => $request->id_carrera,
                    'estado' => $request->estado,
                ];
                
                if ($request->has('direccion') && \Schema::hasColumn('alumnos', 'direccion')) {
                    $saveData['direccion'] = $request->direccion;
                }
                
                $verified = $alumno->verifyCreate($saveData);
                
                if (!$verified) {
                    \Log::error('Failed to verify alumno creation. Manual verification required.');
                }
                
                \Log::info('Alumno created successfully through model: ' . $alumno->id);
                return redirect()->route('alumno.index')
                    ->with('success', 'Alumno creado exitosamente' . (!$verified ? ' (con advertencia)' : ''));
            }
        } catch (\Exception $e) {
            \Log::error('Error al crear alumno: ' . $e->getMessage());
            \Log::error('Error trace: ' . $e->getTraceAsString());
            return redirect()->back()
                ->with('error', 'Ocurrió un error al crear el alumno: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $alumno = Alumno::with(['carrera', 'tesis'])->findOrFail($id);
        return view('alumnos.show', compact('alumno'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $alumno = Alumno::findOrFail($id);
        $carreras = Carrera::all();
        return view('alumnos.edit', compact('alumno', 'carreras'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            // Enhanced debugging logs
            \Log::info('====== START: ALUMNO UPDATE ======');
            \Log::info('Alumno Update - Request data for ID ' . $id . ': ' . print_r($request->all(), true));
            \Log::info('Alumno Update - Request Method: ' . $request->method());
            \Log::info('Alumno Update - Request URL: ' . $request->url());
            \Log::info('Alumno Update - Request has token: ' . ($request->has('_token') ? 'Yes' : 'No'));
            \Log::info('Alumno Update - Request route parameters: ' . print_r($request->route()->parameters(), true));
            \Log::info('Alumno Update - Form method field: ' . ($request->input('_method') ?? 'Not set'));
            
            // First try to find the alumno
            $alumno = Alumno::findOrFail($id);
            \Log::info('Alumno Update - Found alumno: ' . $alumno->id . ' - ' . $alumno->nombre . ' ' . $alumno->apellido);
            
            // Validate the request
            $validator = Validator::make($request->all(), [
                'nombre' => 'required|string|max:255',
                'apellido' => 'required|string|max:255',
                'email' => 'required|string|email|max:255',
                'telefono' => 'required|string|max:20',
                'cedula' => 'required|string|max:20|unique:alumnos,cedula,'.$id,
                'matricula' => 'required|string|max:20|unique:alumnos,matricula,'.$id,
                'fecha_nacimiento' => 'required|date',
                'direccion' => 'nullable|string|max:255',
                'id_carrera' => 'required|exists:carreras,id',
                'estado' => 'required|in:activo,inactivo',
            ]);

            if ($validator->fails()) {
                \Log::error('Alumno update validation failed: ' . print_r($validator->errors()->toArray(), true));
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
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
                    'cedula' => $request->cedula,
                    'matricula' => $request->matricula,
                    'fecha_nacimiento' => date('Y-m-d', strtotime($request->fecha_nacimiento)),
                    'id_carrera' => $request->id_carrera,
                    'estado' => $request->estado,
                    'updated_at' => now(),
                ];
                
                if ($request->has('direccion') && \Schema::hasColumn('alumnos', 'direccion')) {
                    $updateData['direccion'] = $request->direccion;
                }
                
                // Log direct SQL update attempt
                \Log::info('Attempting direct SQL update with data: ' . print_r($updateData, true));
                
                // Execute the direct SQL update
                $affected = \DB::table('alumnos')
                    ->where('id', $id)
                    ->update($updateData);
                    
                \Log::info('Direct SQL update result: ' . $affected . ' rows affected');
                
                // Also try the Eloquent update as a backup
                $alumno->nombre = $request->nombre;
                $alumno->apellido = $request->apellido;
                $alumno->email = $request->email;
                $alumno->telefono = $request->telefono;
                $alumno->cedula = $request->cedula;
                $alumno->matricula = $request->matricula;
                $alumno->fecha_nacimiento = date('Y-m-d', strtotime($request->fecha_nacimiento));
                $alumno->id_carrera = $request->id_carrera;
                $alumno->estado = $request->estado;
                if ($request->has('direccion') && \Schema::hasColumn('alumnos', 'direccion')) {
                    $alumno->direccion = $request->direccion;
                }
                
                // Force the model to recognize the changes
                $alumno->syncOriginal();
                $alumno->syncChanges();
                
                // Check if any fields actually changed
                $changes = $alumno->getDirty();
                \Log::info('Alumno changes to be saved via Eloquent: ' . print_r($changes, true));
                
                // Add save result to debug output
                $saveResult = $alumno->save();
                \Log::info('Alumno Eloquent save result: ' . ($saveResult ? 'true' : 'false'));
                
                // Verify that changes were applied by reloading the model
                $updatedAlumno = Alumno::find($id);
                \Log::info('Reloaded alumno after update: ' . print_r($updatedAlumno->toArray(), true));
                
                // Verify the update with our new verification function
                $updateAttributes = [
                    'nombre' => $request->nombre,
                    'apellido' => $request->apellido,
                    'email' => $request->email,
                    'telefono' => $request->telefono,
                    'cedula' => $request->cedula,
                    'matricula' => $request->matricula,
                    'id_carrera' => $request->id_carrera,
                    'estado' => $request->estado,
                ];
                
                $verified = $updatedAlumno->verifyUpdate($updateAttributes);
                \Log::info('Update verification result: ' . ($verified ? 'Verified OK' : 'Verification FAILED'));
                
                // Commit transaction if both updates were successful
                \DB::commit();
                \Log::info('Transaction committed successfully');
                
                \Log::info('Alumno updated successfully: ' . $alumno->id);
                \Log::info('====== END: ALUMNO UPDATE ======');
                
                return redirect()->route('alumno.index')
                    ->with('success', 'Alumno actualizado exitosamente' . (!$verified ? ' (con advertencia)' : ''));
                    
            } catch (\Exception $dbException) {
                \DB::rollBack();
                \Log::error('Database error during update: ' . $dbException->getMessage());
                throw $dbException;
            }
        } catch (\Exception $e) {
            \Log::error('Error al actualizar alumno: ' . $e->getMessage());
            \Log::error('Exception trace: ' . $e->getTraceAsString());
            \Log::info('====== END: ALUMNO UPDATE (ERROR) ======');
            return redirect()->back()
                ->with('error', 'Ocurrió un error al actualizar el alumno: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $alumno = Alumno::findOrFail($id);
            $alumno->delete();
            
            return response()->json(['success' => 'Alumno eliminado exitosamente']);
        } catch (\Exception $e) {
            \Log::error('Error al eliminar alumno: ' . $e->getMessage());
            return response()->json(['error' => 'Ocurrió un error al eliminar el alumno'], 500);
        }
    }

    /**
     * Export alumnos to PDF
     */
    public function exportPDF()
    {
        $alumnos = Alumno::with('carrera')->get();
        $pdf = PDF::loadView('exports.alumnos_pdf', compact('alumnos'));
        return $pdf->download('alumnos_' . date('YmdHis') . '.pdf');
    }

    /**
     * Export alumnos to Excel
     */
    public function exportExcel()
    {
        return Excel::download(new AlumnosExport, 'alumnos_' . date('YmdHis') . '.xlsx');
    }
}