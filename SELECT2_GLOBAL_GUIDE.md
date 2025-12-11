# Select2 Global - Guía de Uso

## Resumen
Se ha implementado Select2 de manera global en toda la aplicación. Todos los elementos `<select>` ahora tendrán automáticamente:
- Funcionalidad de búsqueda
- Tema Bootstrap 4
- Placeholders personalizables
- Soporte para múltiples selecciones
- Compatibilidad con datos remotos (AJAX)

## Configuración Automática

### Lo que se hace automáticamente:
- Inicialización de Select2 en todos los elementos `<select>`
- Re-inicialización cuando se agrega contenido dinámico
- Limpieza al remover elementos del DOM
- Configuración de idioma español

### Elementos que se saltan automáticamente:
- Selects que ya tienen Select2 inicializado
- Selects con la clase `no-select2`

## Uso Básico

### Select Simple
```html
<select name="categoria" class="form-control">
    <option value="">Seleccione una opción</option>
    <option value="1">Categoría 1</option>
    <option value="2">Categoría 2</option>
</select>
```

### Select con Placeholder Personalizado
```html
<select name="alumno_id" class="form-control" data-placeholder="Buscar y seleccionar un alumno...">
    <option value="">Seleccione un alumno</option>
    @foreach($alumnos as $alumno)
        <option value="{{ $alumno->id }}">{{ $alumno->nombre }}</option>
    @endforeach
</select>
```

### Select Múltiple
```html
<select name="categorias[]" class="form-control" multiple data-placeholder="Seleccionar categorías...">
    <option value="1">Categoría 1</option>
    <option value="2">Categoría 2</option>
    <option value="3">Categoría 3</option>
</select>
```

### Select con Datos Remotos (AJAX)
```html
<select name="usuario_id" class="form-control" 
        data-ajax-url="/api/usuarios/search" 
        data-placeholder="Buscar usuarios...">
    <option value="">Buscar usuarios...</option>
</select>
```

### Deshabilitar Select2 en un elemento específico
```html
<select name="simple" class="form-control no-select2">
    <option value="1">Opción 1</option>
    <option value="2">Opción 2</option>
</select>
```

## Atributos de Configuración

### `data-placeholder`
Define el texto del placeholder
```html
data-placeholder="Texto personalizado del placeholder"
```

### `data-allow-clear`
Controla si se puede limpiar la selección (por defecto: true, false si required)
```html
data-allow-clear="false"
```

### `data-ajax-url`
URL para cargar datos remotos vía AJAX
```html
data-ajax-url="/api/endpoint"
```

## Compatibilidad con Formularios Existentes

### Migración de formularios existentes:
1. **Remover** configuraciones específicas de Select2 en secciones `@section('css')` y `@section('js')`
2. **Remover** la clase `select2` de los elementos select (opcional, pero recomendado)
3. **Agregar** atributos `data-placeholder` para placeholders más descriptivos
4. **Mantener** clases de Bootstrap como `form-control`, `@error('campo') is-invalid @enderror`

### Ejemplo de migración:

**Antes:**
```html
<!-- En la vista -->
<select class="form-control select2" name="categoria">
    <option value="">Seleccione</option>
    <!-- opciones -->
</select>

<!-- En @section('js') -->
<script>
    $('.select2').select2({
        theme: 'bootstrap4'
    });
</script>
```

**Después:**
```html
<!-- En la vista -->
<select class="form-control" name="categoria" data-placeholder="Buscar y seleccionar categoría...">
    <option value="">Seleccione una categoría</option>
    <!-- opciones -->
</select>

<!-- No se necesita JavaScript adicional -->
```

## Trabajo con Modales

### Inicialización Automática
Los modales se detectan automáticamente y Select2 se configura correctamente:
```javascript
// Al abrir un modal, Select2 se inicializa automáticamente
$('#miModal').modal('show');
```

### Funciones Específicas para Modales
```javascript
// Inicializar Select2 específicamente en un modal
window.initializeSelect2InModal('#miModal');

// Reinicializar después de cargar contenido dinámico
window.reinitializeSelect2('#contenedor');
```

### Ejemplo de Modal con Select2
```html
<div class="modal" id="miModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-body">
                <select class="form-control" data-placeholder="Buscar opciones...">
                    <option value="">Seleccionar...</option>
                    <!-- opciones -->
                </select>
            </div>
        </div>
    </div>
</div>

<script>
// Al cargar datos dinámicos en el modal
function loadModalData() {
    $.get('/api/data', function(data) {
        // Cargar opciones...
        $('#select-en-modal').html(opciones);
        
        // Reinicializar Select2
        window.initializeSelect2InModal('#miModal');
    });
}
</script>
```

## Relaciones Múltiples

### Asociaciones Tutor-Alumno
El sistema ahora permite:
- Un tutor puede tener múltiples alumnos asociados
- Un usuario puede ser tanto tutor como alumno
- Búsqueda completa en todos los registros (no solo los no asociados)

### Datos Mostrados
- **Alumnos**: Nombre, apellido, matrícula, email
- **Tutores**: Nombre, apellido, especialidad, email

## Eventos JavaScript Disponibles

```javascript
// Cuando Select2 se inicializa
$(document).on('select2:open', function(e) {
    console.log('Select2 abierto:', e.target);
});

// Cuando se selecciona un valor
$(document).on('select2:select', function(e) {
    console.log('Valor seleccionado:', e.params.data);
});

// Reinicializar Select2 manualmente (si es necesario)
$('#mi-select').select2('destroy').select2({
    theme: 'bootstrap4',
    placeholder: 'Placeholder personalizado'
});
```

## Troubleshooting

### El Select2 no se inicializa en contenido dinámico
El sistema detecta automáticamente contenido nuevo, pero si hay problemas:
```javascript
// Forzar reinicialización
setTimeout(function() {
    initializeSelect2(); // Función global disponible
}, 100);
```

### Conflictos con otros plugins
Si hay conflictos, puedes deshabilitarlo específicamente:
```html
<select class="form-control no-select2">
    <!-- Este select no tendrá Select2 -->
</select>
```

### El placeholder no aparece
Asegúrate de tener una opción vacía como primera opción:
```html
<select data-placeholder="Mi placeholder">
    <option value=""><!-- Esta opción debe existir --></option>
    <option value="1">Opción 1</option>
</select>
```

## Archivos Modificados

### Configuración:
- `config/adminlte.php` - Plugin Select2 activado y script global agregado
- `public/js/select2-global.js` - Script de inicialización global

### Formularios actualizados:
- `resources/views/alumnos/create.blade.php`
- `resources/views/alumnos/edit.blade.php`
- `resources/views/tesis/create.blade.php`
- `resources/views/tesis/edit.blade.php`
- `resources/views/proyectos/create.blade.php`