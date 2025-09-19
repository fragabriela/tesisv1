<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\ComentarioDocumento;
use App\Models\Tesis;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class DocumentoController extends Controller
{
    public function index()
    {
        return view('documentos.index');
    }

    public function create()
    {
        $user = auth()->user();
        
        // Filtrar tesis según el rol del usuario
        if ($user->hasRole('alumno') && $user->alumno) {
            // Si es alumno, solo mostrar sus propias tesis
            $tesis = Tesis::with(['alumno', 'tutor'])
                          ->where('alumno_id', $user->alumno->id)
                          ->get();
        } elseif ($user->hasRole('tutor') && $user->tutor) {
            // Si es tutor, solo mostrar las tesis donde es tutor
            $tesis = Tesis::with(['alumno', 'tutor'])
                          ->where('tutor_id', $user->tutor->id)
                          ->get();
        } else {
            // Admin y coordinador ven todas las tesis
            $tesis = Tesis::with(['alumno', 'tutor'])->get();
        }
        
        return view('documentos.create', compact('tesis'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'nullable|string|max:255',
            'tesis_id' => 'required|exists:tesis,id',
            'archivo_word' => 'nullable|file|mimes:doc,docx|max:10240',
            'descripcion' => 'nullable|string|max:1000',
        ]);

        try {
            $tesis = Tesis::findOrFail($request->tesis_id);
            
            // Log para debugging
            Log::info('DocumentoController::store - Datos recibidos:', [
                'titulo' => $request->titulo,
                'tesis_id' => $request->tesis_id,
                'descripcion' => $request->descripcion,
                'tiene_archivo' => $request->hasFile('archivo_word'),
                'archivo_info' => $request->hasFile('archivo_word') ? [
                    'nombre' => $request->file('archivo_word')->getClientOriginalName(),
                    'tamaño' => $request->file('archivo_word')->getSize(),
                    'extension' => $request->file('archivo_word')->getClientOriginalExtension()
                ] : null
            ]);
            
            $rutaArchivo = null;
            $contenidoHtml = '';
            
            // Generar título automático si no se proporciona o está vacío
            $titulo = $request->titulo;
            if (empty($titulo)) {
                $titulo = 'Documento de ' . $tesis->titulo;
            }
            
            // Procesar archivo si se subió uno
            if ($request->hasFile('archivo_word')) {
                Log::info('DocumentoController::store - Procesando archivo Word');
                
                $archivo = $request->file('archivo_word');
                $nombreArchivo = time() . '_' . Str::slug($titulo) . '.' . $archivo->getClientOriginalExtension();
                $rutaArchivo = $archivo->storeAs('documentos', $nombreArchivo, 'public');
                
                Log::info('DocumentoController::store - Archivo guardado:', [
                    'ruta' => $rutaArchivo,
                    'nombre_generado' => $nombreArchivo
                ]);
                
                $rutaCompleta = storage_path('app/public/' . $rutaArchivo);
                $contenidoHtml = $this->convertirWordAHtml($rutaCompleta);
                
                Log::info('DocumentoController::store - Conversión completada:', [
                    'longitud_html' => strlen($contenidoHtml),
                    'primeros_100_chars' => substr($contenidoHtml, 0, 100)
                ]);
                
                // Si se subió un archivo Word y no se proporcionó título, usar el nombre del archivo
                if (empty($request->titulo)) {
                    $titulo = pathinfo($archivo->getClientOriginalName(), PATHINFO_FILENAME);
                }
            } else {
                Log::info('DocumentoController::store - No se subió archivo, creando contenido básico');
                // Si no hay archivo, crear contenido HTML básico
                $contenidoHtml = '<h1>' . $titulo . '</h1>';
                if ($request->descripcion) {
                    $contenidoHtml .= '<p>' . nl2br(e($request->descripcion)) . '</p>';
                }
            }

            $documento = Documento::create([
                'titulo' => $titulo,
                'descripcion' => $request->descripcion,
                'tesis_id' => $request->tesis_id,
                'alumno_id' => $tesis->alumno_id,
                'tutor_id' => $tesis->tutor_id,
                'editado_por' => Auth::id(),
                'archivo_original' => $rutaArchivo,
                'contenido_html' => $contenidoHtml,
                'contenido_texto' => strip_tags($contenidoHtml),
                'version' => 1,
                'estado' => 'borrador'
            ]);

            // Redirección normal para formularios HTML
            return redirect()->route('documento.edit', $documento)
                           ->with('success', 'Documento creado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error creating documento: ' . $e->getMessage());
            
            return redirect()->back()
                           ->withInput()
                           ->with('error', 'Error al crear el documento: ' . $e->getMessage());
        }
    }

    public function edit(Documento $documento)
    {
        $documento->load(['tesis', 'alumno', 'tutor', 'comentarios.usuario', 'comentarios.respondidoPor']);
        return view('documentos.edit', compact('documento'));
    }

    public function update(Request $request, Documento $documento)
    {
        $request->validate([
            'contenido_html' => 'required|string',
            'comentario_cambios' => 'nullable|string|max:500'
        ]);

        try {
            $versionAnterior = $documento->version;
            
            $documento->update([
                'contenido_html' => $request->contenido_html,
                'version' => $versionAnterior + 1,
                'fecha_ultima_edicion' => now(),
                'editado_por' => Auth::id()
            ]);

            if ($request->comentario_cambios) {
                ComentarioDocumento::create([
                    'documento_id' => $documento->id,
                    'usuario_id' => Auth::id(),
                    'comentario' => 'Versión actualizada (' . $documento->version . '): ' . $request->comentario_cambios,
                    'tipo' => 'revision',
                    'estado' => 'pendiente'
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Nueva versión ' . $documento->version . ' guardada correctamente.',
                'version' => $documento->version
            ]);

        } catch (\Exception $e) {
            Log::error('Error updating documento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el documento: ' . $e->getMessage()
            ], 500);
        }
    }

    public function agregarComentario(Request $request, Documento $documento)
    {
        $request->validate([
            'comentario' => 'required|string|max:1000',
            'tipo' => 'required|in:revision,sugerencia,corrección,aprobacion',
            'texto_seleccionado' => 'nullable|string|max:500',
            'html_seleccionado' => 'nullable|string',
            'posicion_inicio' => 'nullable|integer',
            'posicion_fin' => 'nullable|integer'
        ]);

        try {
            $comentario = ComentarioDocumento::create([
                'documento_id' => $documento->id,
                'usuario_id' => Auth::id(),
                'comentario' => $request->comentario,
                'tipo' => $request->tipo,
                'texto_seleccionado' => $request->texto_seleccionado,
                'html_seleccionado' => $request->html_seleccionado,
                'posicion_inicio' => $request->posicion_inicio,
                'posicion_fin' => $request->posicion_fin,
                'estado' => 'pendiente'
            ]);

            // Cargar relaciones necesarias
            $comentario->load(['usuario', 'respondidoPor']);

            return response()->json([
                'success' => true,
                'message' => 'Comentario agregado correctamente',
                'comentario' => [
                    'id' => $comentario->id,
                    'comentario' => $comentario->comentario,
                    'tipo' => $comentario->tipo,
                    'estado' => $comentario->estado,
                    'texto_seleccionado' => $comentario->texto_seleccionado,
                    'html_seleccionado' => $comentario->html_seleccionado,
                    'usuario_nombre' => $comentario->usuario->name ?? 'Usuario',
                    'created_at' => $comentario->created_at,
                    'respuesta' => $comentario->respuesta,
                    'respondido_por' => $comentario->respondidoPor->name ?? null
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error adding comment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al agregar el comentario: ' . $e->getMessage()
            ], 500);
        }
    }

    public function responderComentario(Request $request, ComentarioDocumento $comentario)
    {
        $request->validate([
            'respuesta' => 'required|string|max:1000',
            'estado' => 'required|in:resuelto,pendiente,descartado'
        ]);

        try {
            $comentario->update([
                'respuesta' => $request->respuesta,
                'estado' => $request->estado,
                'respondido_por' => Auth::id(),
                'fecha_respuesta' => now()
            ]);

            // Cargar relaciones actualizadas
            $comentario->load(['usuario', 'respondidoPor']);

            return response()->json([
                'success' => true,
                'message' => 'Respuesta enviada correctamente',
                'comentario' => [
                    'id' => $comentario->id,
                    'comentario' => $comentario->comentario,
                    'tipo' => $comentario->tipo,
                    'estado' => $comentario->estado,
                    'texto_seleccionado' => $comentario->texto_seleccionado,
                    'usuario_nombre' => $comentario->usuario->name ?? 'Usuario',
                    'respuesta' => $comentario->respuesta,
                    'respondido_por' => $comentario->respondidoPor->name ?? 'Usuario',
                    'fecha_respuesta' => $comentario->fecha_respuesta,
                    'created_at' => $comentario->created_at
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error responding to comment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al responder el comentario: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getData(Request $request)
    {
        $user = auth()->user();
        $query = Documento::with(['tesis', 'alumno', 'tutor', 'usuarioCreacion'])
            ->withCount('comentarios')
            ->select(['id', 'titulo', 'tesis_id', 'alumno_id', 'tutor_id', 'version', 'estado', 'fecha_ultima_edicion', 'created_at']);

        // Filtrar según el rol del usuario
        if ($user->hasRole('alumno')) {
            // Alumno solo ve sus propios documentos
            if ($user->alumno) {
                $query->where('alumno_id', $user->alumno->id);
            } else {
                // Si no tiene alumno asociado, no ve nada
                $query->where('id', 0);
            }
        } elseif ($user->hasRole('tutor')) {
            // Tutor solo ve los documentos donde es tutor
            if ($user->tutor) {
                $query->where('tutor_id', $user->tutor->id);
            } else {
                // Si no tiene tutor asociado, no ve nada
                $query->where('id', 0);
            }
        }
        // Admin y coordinador ven todos los documentos (sin filtro adicional)

        return datatables($query)
            ->addIndexColumn()
            ->addColumn('tesis', function ($documento) {
                return $documento->tesis ? $documento->tesis->titulo : 'N/A';
            })
            ->addColumn('alumno', function ($documento) {
                return $documento->alumno ? $documento->alumno->nombre . ' ' . $documento->alumno->apellido : 'N/A';
            })
            ->addColumn('tutor', function ($documento) {
                return $documento->tutor ? $documento->tutor->nombre . ' ' . $documento->tutor->apellido : 'N/A';
            })
            ->addColumn('comentarios_count', function ($documento) {
                $count = $documento->comentarios_count;
                return $count > 0 ? '<span class="badge badge-info">' . $count . '</span>' : '<span class="badge badge-secondary">0</span>';
            })
            ->addColumn('estado_badge', function ($documento) {
                $badgeClass = [
                    'borrador' => 'secondary',
                    'revision' => 'warning',
                    'aprobado' => 'success',
                    'corregir' => 'danger'
                ];
                return '<span class="badge badge-' . ($badgeClass[$documento->estado] ?? 'secondary') . '">' . ucfirst($documento->estado) . '</span>';
            })
            ->addColumn('fecha_ultima_modificacion', function ($documento) {
                return $documento->fecha_ultima_edicion ? $documento->fecha_ultima_edicion->format('d/m/Y H:i') : $documento->created_at->format('d/m/Y H:i');
            })
            ->addColumn('actions', function ($documento) {
                $user = auth()->user();
                $actions = '';
                
                // Solo admin, coordinador y el alumno/tutor propietario pueden editar
                if ($user->hasRole(['administrador', 'coordinador']) || 
                    ($user->hasRole('alumno') && $user->alumno && $user->alumno->id == $documento->alumno_id) ||
                    ($user->hasRole('tutor') && $user->tutor && $user->tutor->id == $documento->tutor_id)) {
                    $actions .= '<a href="' . route('documento.edit', $documento) . '" class="btn btn-sm btn-primary" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>';
                }
                
                // Solo admin y coordinador pueden eliminar
                if ($user->hasRole(['administrador', 'coordinador'])) {
                    $actions .= '<button class="btn btn-sm btn-danger eliminar-documento" onclick="eliminarDocumento(' . $documento->id . ')" title="Eliminar">
                            <i class="fas fa-trash"></i>
                        </button>';
                }
                
                return '<div class="btn-group" role="group">' . $actions . '</div>';
            })
            ->rawColumns(['estado_badge', 'comentarios_count', 'actions'])
            ->make(true);
    }

    private function convertirWordAHtml($archivoPath)
    {
        try {
            if (!file_exists($archivoPath)) {
                throw new \Exception('El archivo no existe: ' . $archivoPath);
            }

            Log::info('Iniciando conversión de Word a HTML para: ' . $archivoPath);
            
            $phpWord = \PhpOffice\PhpWord\IOFactory::load($archivoPath);
            $htmlWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'HTML');
            
            $tempHtml = tempnam(sys_get_temp_dir(), 'word_to_html_') . '.html';
            $htmlWriter->save($tempHtml);
            
            $htmlContent = file_get_contents($tempHtml);
            unlink($tempHtml);
            
            Log::info('Contenido HTML bruto tiene ' . strlen($htmlContent) . ' caracteres');
            
            $htmlContent = $this->procesarHtmlParaEditor($htmlContent);
            
            Log::info('Contenido HTML procesado tiene ' . strlen($htmlContent) . ' caracteres');
            
            return $htmlContent;
            
        } catch (\Exception $e) {
            Log::error('Error converting Word to HTML: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            return '<p>Error al convertir el documento: ' . $e->getMessage() . '</p><p>Por favor, verifique que el archivo sea un documento Word válido.</p>';
        }
    }
    
    private function procesarHtmlParaEditor($html)
    {
        // Log del contenido original para debug
        Log::info('HTML original (primeros 500 chars): ' . substr($html, 0, 500));
        
        // Extraer el contenido del body si existe
        if (preg_match('/<body[^>]*>(.*?)<\/body>/is', $html, $matches)) {
            $html = $matches[1];
            Log::info('Contenido extraído del body');
        }
        
        // Limpiar solo las etiquetas problemáticas pero conservar el contenido
        $html = preg_replace('/<html[^>]*>/i', '', $html);
        $html = preg_replace('/<\/html>/i', '', $html);
        $html = preg_replace('/<head.*?<\/head>/is', '', $html);
        
        // Mantener algunos estilos básicos pero limpiar los muy específicos
        $html = preg_replace('/style="[^"]*font-family:[^"]*"/i', '', $html);
        $html = preg_replace('/style="[^"]*mso-[^"]*"/i', '', $html);
        
        // Limpiar espacios en blanco excesivos pero mantener estructura
        $html = preg_replace('/\s+/', ' ', $html);
        $html = str_replace('> <', '><', $html);
        
        // Verificar si hay contenido real
        $textoPlano = strip_tags($html);
        $textoLimpio = trim(preg_replace('/\s+/', ' ', $textoPlano));
        
        Log::info('Texto extraído (primeros 200 chars): ' . substr($textoLimpio, 0, 200));
        Log::info('Longitud del texto: ' . strlen($textoLimpio));
        
        if (strlen($textoLimpio) < 10) {
            Log::warning('Contenido muy corto o vacío después del procesamiento');
            $html = '<p>El documento se ha cargado pero parece tener poco contenido visible. El archivo original puede contener principalmente formato o elementos no compatibles.</p><p>Contenido detectado: ' . htmlspecialchars($textoLimpio) . '</p>';
        } else {
            // Asegurar que el HTML esté bien formado
            $html = '<div class="documento-contenido">' . $html . '</div>';
        }
        
        return trim($html);
    }

    public function reconvertirDocumento($id)
    {
        try {
            $documento = Documento::findOrFail($id);
            
            if (!$documento->archivo_original || !Storage::disk('public')->exists($documento->archivo_original)) {
                return redirect()->back()->with('error', 'No se encontró el archivo original del documento.');
            }
            
            $rutaCompleta = storage_path('app/public/' . $documento->archivo_original);
            $contenidoHtml = $this->convertirWordAHtml($rutaCompleta);
            
            $documento->contenido_html = $contenidoHtml;
            $documento->contenido_texto = strip_tags($contenidoHtml);
            $documento->save();
            
            return redirect()->route('documento.edit', $documento->id)
                           ->with('success', 'Documento reconvertido exitosamente. Por favor revisa el contenido.');
                           
        } catch (\Exception $e) {
            Log::error('Error reconvirtiendo documento: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al reconvertir el documento: ' . $e->getMessage());
        }
    }

    /**
     * Upload image for TinyMCE editor
     */
    public function uploadImage(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:5120' // 5MB max
        ]);

        try {
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $filename = time() . '_' . Str::random(10) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('editor_images', $filename, 'public');
                
                return response()->json([
                    'location' => Storage::url($path)
                ]);
            }
            
            return response()->json(['error' => 'No file uploaded'], 400);
            
        } catch (\Exception $e) {
            Log::error('Error uploading image: ' . $e->getMessage());
            return response()->json(['error' => 'Upload failed'], 500);
        }
    }

    /**
     * Remove the specified document from storage.
     */
    public function destroy(Documento $documento)
    {
        try {
            // Verificar permisos si es necesario
            // if (!auth()->user()->can('eliminar documentos')) {
            //     return response()->json(['success' => false, 'message' => 'No tiene permisos para eliminar documentos'], 403);
            // }

            // Eliminar archivo físico si existe
            if ($documento->archivo_original && Storage::disk('public')->exists($documento->archivo_original)) {
                Storage::disk('public')->delete($documento->archivo_original);
            }

            // Eliminar comentarios asociados (CASCADE debería manejar esto automáticamente)
            $documento->comentarios()->delete();

            // Eliminar el documento
            $documento->delete();

            return response()->json([
                'success' => true,
                'message' => 'Documento eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error eliminando documento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el documento: ' . $e->getMessage()
            ], 500);
        }
    }
}