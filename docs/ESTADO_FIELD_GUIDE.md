# Estado de Proyectos y Tesis

Este documento explica los valores correctos para el campo `estado` en las tablas `tesis` y cualquier otra que utilice estados similares.

## Valores permitidos para el campo `estado` en Tesis

El campo `estado` en la tabla `tesis` está definido como un ENUM en la base de datos, lo que significa que sólo acepta un conjunto específico de valores. Los valores permitidos son:

| Valor en Base de Datos | Etiqueta en Interfaz | Descripción |
|------------------------|----------------------|-------------|
| `pendiente`            | Pendiente            | El proyecto está en etapa inicial, pendiente de comenzar. |
| `en_progreso`          | En Progreso          | El proyecto está siendo trabajado activamente. |
| `completado`           | Completado           | El proyecto ha sido finalizado satisfactoriamente. |
| `rechazado`            | Rechazado            | El proyecto ha sido rechazado o cancelado. |

## Errores comunes

Es importante tener en cuenta los siguientes puntos para evitar errores:

1. El valor `en_progreso` utiliza un guión bajo (_), no un espacio. Usar `en progreso` causará un error de validación o de base de datos.

2. No se debe usar `finalizado` como un valor para el estado, sino `completado`.

## Visualización en la interfaz

Aunque en la base de datos los valores utilizan guiones bajos (en_progreso), en la interfaz de usuario se muestran con espacios y formato más amigable (En Progreso).

## Corrección de valores inconsistentes

Si se detectan valores inconsistentes en la base de datos, se puede utilizar el siguiente comando para corregirlos:

```bash
php artisan app:fix-tesis-estado
```

Este comando realizará las siguientes correcciones automáticamente:
- `en progreso` (con espacio) → `en_progreso` (con guión bajo)
- `finalizado` → `completado`

## Para desarrolladores

Al trabajar con el campo `estado` en controladores, asegúrese de utilizar los valores exactos:

```php
// Correcto
'estado' => 'required|in:pendiente,en_progreso,completado,rechazado',

// Incorrecto
'estado' => 'required|in:pendiente,en progreso,finalizado',
```

En las vistas Blade, asegúrese de que los values de los elementos option coincidan exactamente con los valores esperados:

```blade
<option value="en_progreso">En Progreso</option>  {{-- Correcto --}}
<option value="en progreso">En Progreso</option>  {{-- Incorrecto --}}
```
