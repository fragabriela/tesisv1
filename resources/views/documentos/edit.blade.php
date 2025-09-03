@extends('adminlte::page')

@section('title', 'Editor Colaborativo')

@section('content_header')
    <div class="row">
        <div class="col-md-8">
            <h1>{{ $documento->titulo }}</h1>
            <p class="text-muted">
                <i class="fas fa-user"></i> {{ $documento->alumno->nombre }} {{ $documento->alumno->apellido }} |
                <i class="fas fa-user-tie"></i> Prof. {{ $documento->tutor->nombre }} {{ $documento->tutor->apellido }} |
                <i class="fas fa-clock"></i> Versión {{ $documento->version }} |
                <i class="fas fa-edit"></i> {{ $documento->updated_at->format('d/m/Y H:i') }}
            </p>
        </div>
        <div class="col-md-4 text-right">
            <div class="btn-group">
                <button type="button" class="btn btn-success" id="guardar-documento">
                    <i class="fas fa-save"></i> Guardar
                </button>
                @if($documento->archivo_original)
                <a href="{{ route('documento.reconvertir', $documento->id) }}" 
                   class="btn btn-warning" 
                   onclick="return confirm('¿Está seguro de que desea reconvertir el documento? Esto sobrescribirá el contenido actual con una nueva conversión del archivo Word original.')">
                    <i class="fas fa-sync-alt"></i> Reconvertir
                </a>
                @endif
                <button type="button" class="btn btn-info" id="toggle-comentarios">
                    <i class="fas fa-comments"></i> Comentarios 
                    <span class="badge badge-light">{{ $documento->comentariosPendientes->count() }}</span>
                </button>
                <a href="{{ route('documento.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>
    </div>
@stop

@section('content')
<div class="row">
    <!-- Panel del Editor -->
    <div class="col-md-8" id="editor-panel">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-edit"></i> Editor de Documento
                </h3>
                <div class="card-tools">
                    <span class="badge badge-{{ $documento->estado == 'aprobado' ? 'success' : ($documento->estado == 'revision' ? 'warning' : 'secondary') }}">
                        {{ ucfirst($documento->estado) }}
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <!-- Área del editor TinyMCE -->
                <textarea id="documento-editor" name="contenido_html">
                    {!! $documento->contenido_html ?: '<p>Comience a escribir su documento aquí...</p>' !!}
                </textarea>
            </div>
        </div>
    </div>

    <!-- Panel de Comentarios -->
    <div class="col-md-4" id="comentarios-panel">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-comments"></i> Comentarios
                </h3>
            </div>
            <div class="card-body">
                <!-- Formulario para agregar comentario -->
                <div id="nuevo-comentario-form" style="display: none;">
                    <div class="alert alert-info">
                        <strong>Texto seleccionado:</strong>
                        <div id="texto-seleccionado" class="mt-1"></div>
                    </div>
                    <div class="form-group">
                        <label>Tipo de comentario:</label>
                        <select class="form-control form-control-sm" id="tipo-comentario">
                            <option value="revision">Revisión</option>
                            <option value="sugerencia">Sugerencia</option>
                            <option value="corrección">Corrección</option>
                            <option value="aprobacion">Aprobación</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <textarea class="form-control" id="comentario-texto" rows="3" placeholder="Escriba su comentario..."></textarea>
                    </div>
                    <div class="btn-group btn-group-sm">
                        <button type="button" class="btn btn-primary" id="guardar-comentario">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                        <button type="button" class="btn btn-secondary" id="cancelar-comentario">
                            Cancelar
                        </button>
                    </div>
                    <hr>
                </div>

                <!-- Lista de comentarios existentes -->
                <div id="lista-comentarios">
                    @foreach($documento->comentarios->sortByDesc('created_at') as $comentario)
                        <div class="comentario-item" data-id="{{ $comentario->id }}">
                            <div class="card card-outline card-{{ $comentario->tipo == 'corrección' ? 'danger' : ($comentario->tipo == 'aprobacion' ? 'success' : 'warning') }}">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small>
                                            <strong>{{ $comentario->usuario->name }}</strong>
                                            <span class="badge badge-{{ $comentario->tipo == 'corrección' ? 'danger' : ($comentario->tipo == 'aprobacion' ? 'success' : 'warning') }}">
                                                {{ ucfirst($comentario->tipo) }}
                                            </span>
                                        </small>
                                        <small class="text-muted">{{ $comentario->created_at->format('d/m H:i') }}</small>
                                    </div>
                                </div>
                                <div class="card-body p-2">
                                    @if($comentario->texto_seleccionado)
                                        <div class="bg-light p-2 mb-2 small">
                                            <strong>Referencia:</strong> "{{ $comentario->texto_seleccionado }}"
                                        </div>
                                    @endif
                                    <p class="mb-2">{{ $comentario->comentario }}</p>
                                    
                                    @if($comentario->respuesta)
                                        <div class="bg-success-light p-2 mt-2">
                                            <small><strong>Respuesta ({{ $comentario->respondidoPor->name ?? 'Usuario' }}):</strong></small>
                                            <p class="mb-0 small">{{ $comentario->respuesta }}</p>
                                        </div>
                                    @endif

                                    @if($comentario->estado == 'pendiente' && !$comentario->respuesta)
                                        <div class="respuesta-form mt-2" style="display: none;">
                                            <div class="form-group mb-2">
                                                <textarea class="form-control form-control-sm respuesta-texto" rows="2" placeholder="Escriba su respuesta..."></textarea>
                                            </div>
                                            <div class="form-group mb-2">
                                                <select class="form-control form-control-sm estado-respuesta">
                                                    <option value="resuelto">Resuelto</option>
                                                    <option value="pendiente">Mantener Pendiente</option>
                                                    <option value="descartado">Descartar</option>
                                                </select>
                                            </div>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-success btn-responder" data-id="{{ $comentario->id }}">
                                                    <i class="fas fa-reply"></i> Responder
                                                </button>
                                                <button type="button" class="btn btn-secondary btn-cancelar-respuesta">
                                                    Cancelar
                                                </button>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary btn-mostrar-respuesta mt-1">
                                            <i class="fas fa-reply"></i> Responder
                                        </button>
                                    @endif
                                    
                                    <div class="mt-2">
                                        <span class="badge badge-{{ $comentario->estado == 'resuelto' ? 'success' : ($comentario->estado == 'pendiente' ? 'warning' : 'secondary') }}">
                                            {{ ucfirst($comentario->estado) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para confirmar cambios -->
<div class="modal fade" id="cambios-modal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Guardar Cambios</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label>Comentario sobre los cambios realizados:</label>
                    <textarea class="form-control" id="comentario-cambios" rows="3" placeholder="Describa brevemente los cambios realizados..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="confirmar-guardado">
                    <i class="fas fa-save"></i> Guardar
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>
@stop

@section('css')
    <style>
        .texto-seleccionado {
            background-color: #fff3cd;
            padding: 2px 4px;
            border-radius: 3px;
        }
        
        .comentario-item {
            margin-bottom: 15px;
        }
        
        .bg-success-light {
            background-color: #d4edda;
            border-radius: 3px;
        }
        
        #comentarios-panel {
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .card-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        /* Estilos personalizados para TinyMCE */
        .tox-tinymce {
            border: 1px solid #ddd !important;
        }

        .tox .tox-editor-header {
            background: #f8f9fa !important;
            border-bottom: 1px solid #ddd !important;
        }

        /* Hacer que el área de contenido se parezca más a Word */
        .tox .tox-edit-area {
            background: white !important;
        }
    </style>
@stop

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        let documentoId = {{ $documento->id }};
        let contenidoOriginal = '';
        let seleccionActual = null;
        let editorInstance = null;

        $(document).ready(function() {
            // Inicializar TinyMCE con configuración avanzada tipo Word (versión gratuita)
            tinymce.init({
                selector: '#documento-editor',
                height: 600,
                // language: 'es', // Comentado para usar inglés por defecto
                plugins: [
                    'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                    'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                    'insertdatetime', 'media', 'table', 'help', 'wordcount', 'quickbars'
                ],
                toolbar1: 'undo redo | styles | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify',
                toolbar2: 'bullist numlist outdent indent | link unlink | image media table | removeformat | code fullscreen | help',
                toolbar3: 'fontfamily fontsize | subscript superscript | charmap | insertdatetime',
                
                // Configuración de estilo tipo Word
                style_formats: [
                    {title: 'Títulos', items: [
                        {title: 'Título 1', format: 'h1'},
                        {title: 'Título 2', format: 'h2'},
                        {title: 'Título 3', format: 'h3'},
                        {title: 'Título 4', format: 'h4'},
                        {title: 'Título 5', format: 'h5'},
                        {title: 'Título 6', format: 'h6'}
                    ]},
                    {title: 'Párrafos', items: [
                        {title: 'Párrafo normal', format: 'p'},
                        {title: 'Párrafo centrado', format: 'p', styles: {textAlign: 'center'}},
                        {title: 'Párrafo justificado', format: 'p', styles: {textAlign: 'justify'}}
                    ]},
                    {title: 'Texto', items: [
                        {title: 'Texto destacado', inline: 'span', classes: 'destacado'},
                        {title: 'Texto de nota', inline: 'span', classes: 'nota'},
                        {title: 'Cita', format: 'blockquote'}
                    ]}
                ],
                
                // Configuración visual tipo Word
                content_style: `
                    body { 
                        font-family: 'Times New Roman', Times, serif; 
                        font-size: 12pt; 
                        line-height: 1.6; 
                        margin: 40px; 
                        background: white;
                        color: #333;
                        max-width: none;
                    }
                    h1, h2, h3, h4, h5, h6 { 
                        font-family: 'Times New Roman', Times, serif; 
                        margin: 1em 0 0.5em 0;
                        color: #2c3e50;
                    }
                    h1 { font-size: 18pt; font-weight: bold; }
                    h2 { font-size: 16pt; font-weight: bold; }
                    h3 { font-size: 14pt; font-weight: bold; }
                    h4 { font-size: 12pt; font-weight: bold; }
                    p { margin: 0 0 12pt 0; text-align: justify; }
                    blockquote { 
                        margin: 12pt 40pt; 
                        font-style: italic; 
                        border-left: 3px solid #ccc; 
                        padding-left: 12pt; 
                    }
                    table { border-collapse: collapse; width: 100%; margin: 12pt 0; }
                    th, td { border: 1px solid #ddd; padding: 8pt; text-align: left; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    ul, ol { margin: 0 0 12pt 24pt; }
                    li { margin-bottom: 6pt; }
                    .destacado { background-color: #ffffcc; padding: 2px 4px; }
                    .nota { font-size: 10pt; color: #666; font-style: italic; }
                `,
                
                // Configuración del menú contextual
                contextmenu: 'link image table',
                
                // Configuraciones adicionales
                menubar: 'file edit view insert format tools table help',
                quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote',
                
                // Personalización de botones
                toolbar_mode: 'sliding',
                
                // Configuración de tablas
                table_default_attributes: {
                    border: '1'
                },
                table_default_styles: {
                    'border-collapse': 'collapse',
                    'width': '100%'
                },
                
                // Callback cuando el editor está listo
                setup: function(editor) {
                    editorInstance = editor;
                    
                    editor.on('init', function() {
                        contenidoOriginal = editor.getContent();
                    });
                    
                    // Manejar selección de texto para comentarios
                    editor.on('mouseup keyup', function() {
                        setTimeout(function() {
                            let selection = editor.selection.getContent({format: 'text'});
                            if (selection.length > 0) {
                                seleccionActual = {
                                    texto: selection,
                                    html: editor.selection.getContent()
                                };
                                $('#texto-seleccionado').text(seleccionActual.texto);
                                $('#nuevo-comentario-form').show();
                            }
                        }, 100);
                    });
                },
                
                // Configuración de imágenes
                images_upload_url: '/documento/upload-image',
                images_upload_credentials: true,
                images_upload_handler: function (blobInfo, progress) {
                    return new Promise(function(resolve, reject) {
                        let xhr = new XMLHttpRequest();
                        xhr.withCredentials = false;
                        xhr.open('POST', '/documento/upload-image');
                        xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
                        
                        xhr.onload = function() {
                            if (xhr.status === 403) {
                                reject({message: 'HTTP Error: ' + xhr.status, remove: true});
                                return;
                            }
                            
                            if (xhr.status < 200 || xhr.status >= 300) {
                                reject('HTTP Error: ' + xhr.status);
                                return;
                            }
                            
                            let json = JSON.parse(xhr.responseText);
                            if (!json || typeof json.location !== 'string') {
                                reject('Invalid JSON: ' + xhr.responseText);
                                return;
                            }
                            
                            resolve(json.location);
                        };
                        
                        let formData = new FormData();
                        formData.append('file', blobInfo.blob(), blobInfo.filename());
                        xhr.send(formData);
                    });
                }
            });
            
            // Manejar selección de texto para comentarios (versión alternativa para cuando TinyMCE está activo)
            $(document).on('mouseup', function(e) {
                // Solo si no estamos dentro del editor TinyMCE
                if (!$(e.target).closest('.tox-tinymce').length && editorInstance) {
                    let selection = editorInstance.selection.getContent({format: 'text'});
                    if (selection && selection.length > 0) {
                        seleccionActual = {
                            texto: selection,
                            html: editorInstance.selection.getContent()
                        };
                        $('#texto-seleccionado').text(seleccionActual.texto);
                        $('#nuevo-comentario-form').show();
                    }
                }
            });

            // Guardar comentario
            $('#guardar-comentario').click(function() {
                let comentario = $('#comentario-texto').val();
                let tipo = $('#tipo-comentario').val();
                
                if (!comentario) {
                    Swal.fire('Error', 'Debe escribir un comentario', 'error');
                    return;
                }

                $.ajax({
                    url: `/documento/${documentoId}/comentario`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        comentario: comentario,
                        tipo: tipo,
                        texto_seleccionado: seleccionActual ? seleccionActual.texto : null,
                        html_seleccionado: seleccionActual ? seleccionActual.html : null
                    },
                    success: function(response) {
                        if (response.success) {
                            // Agregar el nuevo comentario al panel sin recargar
                            agregarComentarioAlPanel(response.comentario);
                            // Actualizar contador
                            actualizarContadorComentarios();
                            // Limpiar formulario
                            $('#comentario-texto').val('');
                            $('#nuevo-comentario-form').hide();
                            seleccionActual = null;
                            if (editorInstance) {
                                editorInstance.selection.collapse();
                            }
                            
                            Swal.fire({
                                icon: 'success',
                                title: '¡Comentario agregado!',
                                text: 'El comentario se ha guardado correctamente',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Ocurrió un error al guardar el comentario', 'error');
                    }
                });
            });

            // Cancelar comentario
            $('#cancelar-comentario').click(function() {
                $('#nuevo-comentario-form').hide();
                $('#comentario-texto').val('');
                seleccionActual = null;
                if (editorInstance) {
                    editorInstance.selection.collapse();
                }
            });

            // Mostrar formulario de respuesta
            $(document).on('click', '.btn-mostrar-respuesta', function() {
                $(this).hide();
                $(this).siblings('.respuesta-form').show();
            });

            // Cancelar respuesta
            $(document).on('click', '.btn-cancelar-respuesta', function() {
                let respuestaForm = $(this).closest('.respuesta-form');
                respuestaForm.hide();
                respuestaForm.siblings('.btn-mostrar-respuesta').show();
            });

            // Enviar respuesta
            $(document).on('click', '.btn-responder', function() {
                let comentarioId = $(this).data('id');
                let respuestaForm = $(this).closest('.respuesta-form');
                let respuesta = respuestaForm.find('.respuesta-texto').val();
                let estado = respuestaForm.find('.estado-respuesta').val();

                if (!respuesta) {
                    Swal.fire('Error', 'Debe escribir una respuesta', 'error');
                    return;
                }

                $.ajax({
                    url: `/comentario/${comentarioId}/responder`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        respuesta: respuesta,
                        estado: estado
                    },
                    success: function(response) {
                        if (response.success) {
                            // Actualizar el comentario específico sin recargar
                            actualizarComentarioConRespuesta(comentarioId, response.comentario);
                            // Actualizar contador
                            actualizarContadorComentarios();
                            
                            Swal.fire({
                                icon: 'success',
                                title: '¡Respuesta enviada!',
                                text: 'La respuesta se ha guardado correctamente',
                                timer: 2000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Ocurrió un error al enviar la respuesta', 'error');
                    }
                });
            });

            // Guardar documento
            $('#guardar-documento').click(function() {
                if (!editorInstance) {
                    Swal.fire('Error', 'El editor no está inicializado', 'error');
                    return;
                }
                
                let contenidoActual = editorInstance.getContent();
                
                if (contenidoActual === contenidoOriginal) {
                    Swal.fire('Información', 'No hay cambios para guardar', 'info');
                    return;
                }

                $('#cambios-modal').modal('show');
            });

            // Confirmar guardado
            $('#confirmar-guardado').click(function() {
                if (!editorInstance) {
                    Swal.fire('Error', 'El editor no está inicializado', 'error');
                    return;
                }
                
                let contenidoActual = editorInstance.getContent();
                let comentarioCambios = $('#comentario-cambios').val();

                $.ajax({
                    url: `/documento/${documentoId}`,
                    method: 'PUT',
                    data: {
                        _token: '{{ csrf_token() }}',
                        contenido_html: contenidoActual,
                        comentario_cambios: comentarioCambios
                    },
                    success: function(response) {
                        if (response.success) {
                            contenidoOriginal = contenidoActual;
                            $('#cambios-modal').modal('hide');
                            $('#comentario-cambios').val('');
                            
                            Swal.fire({
                                icon: 'success',
                                title: '¡Documento guardado!',
                                text: response.message || 'El documento se ha guardado correctamente',
                                timer: 3000,
                                showConfirmButton: false
                            });
                        } else {
                            Swal.fire('Error', response.message, 'error');
                        }
                    },
                    error: function() {
                        Swal.fire('Error', 'Ocurrió un error al guardar el documento', 'error');
                    }
                });
            });

            // Toggle panel de comentarios
            $('#toggle-comentarios').click(function() {
                $('#comentarios-panel').toggle();
                if ($('#comentarios-panel').is(':visible')) {
                    $('#editor-panel').removeClass('col-md-12').addClass('col-md-8');
                } else {
                    $('#editor-panel').removeClass('col-md-8').addClass('col-md-12');
                }
                
                // Redimensionar TinyMCE cuando se cambia el tamaño del panel
                setTimeout(function() {
                    if (editorInstance) {
                        editorInstance.execCommand('mceAutoResize');
                    }
                }, 300);
            });
        });
        
        // Función para agregar comentario al panel dinámicamente
        function agregarComentarioAlPanel(comentario) {
            let tipoColor = comentario.tipo === 'corrección' ? 'danger' : 
                           (comentario.tipo === 'aprobacion' ? 'success' : 'warning');
            
            let comentarioHtml = `
                <div class="comentario-item" data-id="${comentario.id}">
                    <div class="card card-outline card-${tipoColor}">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <small>
                                    <strong>${comentario.usuario_nombre}</strong>
                                    <span class="badge badge-${tipoColor}">
                                        ${comentario.tipo.charAt(0).toUpperCase() + comentario.tipo.slice(1)}
                                    </span>
                                </small>
                                <small class="text-muted">Ahora</small>
                            </div>
                        </div>
                        <div class="card-body p-2">
                            ${comentario.texto_seleccionado ? 
                                `<div class="bg-light p-2 mb-2 small">
                                    <strong>Referencia:</strong> "${comentario.texto_seleccionado}"
                                </div>` : ''}
                            <p class="mb-2">${comentario.comentario}</p>
                            
                            <div class="respuesta-form mt-2" style="display: none;">
                                <div class="form-group mb-2">
                                    <textarea class="form-control form-control-sm respuesta-texto" rows="2" placeholder="Escriba su respuesta..."></textarea>
                                </div>
                                <div class="form-group mb-2">
                                    <select class="form-control form-control-sm estado-respuesta">
                                        <option value="resuelto">Resuelto</option>
                                        <option value="pendiente">Mantener Pendiente</option>
                                        <option value="descartado">Descartar</option>
                                    </select>
                                </div>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-success btn-responder" data-id="${comentario.id}">
                                        <i class="fas fa-reply"></i> Responder
                                    </button>
                                    <button type="button" class="btn btn-secondary btn-cancelar-respuesta">
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary btn-mostrar-respuesta mt-1">
                                <i class="fas fa-reply"></i> Responder
                            </button>
                            
                            <div class="mt-2">
                                <span class="badge badge-warning">Pendiente</span>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('#lista-comentarios').prepend(comentarioHtml);
        }
        
        // Función para actualizar comentario con respuesta
        function actualizarComentarioConRespuesta(comentarioId, comentarioData) {
            let comentarioElement = $(`.comentario-item[data-id="${comentarioId}"]`);
            
            // Ocultar formulario de respuesta y botón
            comentarioElement.find('.respuesta-form').hide();
            comentarioElement.find('.btn-mostrar-respuesta').hide();
            
            // Agregar la respuesta al contenido
            let respuestaHtml = `
                <div class="bg-success-light p-2 mt-2">
                    <small><strong>Respuesta (${comentarioData.respondido_por}):</strong></small>
                    <p class="mb-0 small">${comentarioData.respuesta}</p>
                </div>
            `;
            
            comentarioElement.find('.card-body p').last().after(respuestaHtml);
            
            // Actualizar badge de estado
            let estadoColor = comentarioData.estado === 'resuelto' ? 'success' : 
                             (comentarioData.estado === 'pendiente' ? 'warning' : 'secondary');
            let estadoBadge = `<span class="badge badge-${estadoColor}">${comentarioData.estado.charAt(0).toUpperCase() + comentarioData.estado.slice(1)}</span>`;
            
            comentarioElement.find('.mt-2:last .badge').replaceWith(estadoBadge);
        }
        
        // Función para actualizar contador de comentarios
        function actualizarContadorComentarios() {
            let totalComentarios = $('.comentario-item').length;
            let comentariosPendientes = $('.badge-warning').filter(function() {
                return $(this).text().toLowerCase() === 'pendiente';
            }).length;
            
            $('#toggle-comentarios .badge').text(comentariosPendientes);
        }
    </script>
@stop