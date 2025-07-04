# Instrucciones de Uso - Sistema de Gestión Académica

## Actualización de Registros - Procedimiento Correcto

Después de las correcciones implementadas, la actualización de alumnos y tutores debería funcionar sin problemas. Siga estos pasos para actualizar correctamente la información:

### Para Alumnos:

1. Acceda al listado de alumnos en la sección "Alumnos" del menú lateral.
2. Haga clic en el botón "Editar" junto al alumno que desea modificar.
3. Realice los cambios necesarios en el formulario.
4. Haga clic en "Actualizar" para guardar los cambios.

### Para Tutores:

1. Acceda al listado de tutores en la sección "Tutores" del menú lateral.
2. Haga clic en el botón "Editar" junto al tutor que desea modificar.
3. Realice los cambios necesarios en el formulario.
4. Haga clic en "Actualizar" para guardar los cambios.

## Monitoreo y Diagnóstico

Si experimenta algún problema con la actualización de alumnos, puede utilizar las siguientes herramientas de diagnóstico:

### Monitor de Formularios
- Acceda a través del menú lateral: "Herramientas de Desarrollo" -> "Monitor de Formularios"
- Esta herramienta muestra todos los envíos de formularios recientes, lo que ayuda a identificar si los datos del formulario se están enviando correctamente.
- También puede realizar pruebas de actualización directamente desde esta herramienta.

### Prueba de Actualización Directa
- Acceda a `/public/test_update.php` en su navegador.
- Esta herramienta realiza actualizaciones directas en la base de datos usando tanto Eloquent como SQL directo, y muestra los resultados detallados.
- Útil para verificar si los problemas están relacionados con la base de datos o con la lógica de la aplicación.

### Comando de Monitoreo
- Puede ejecutar `php artisan monitor:alumno-updates` para realizar pruebas automáticas de actualización.
- Añada la opción `--fix` para intentar solucionar automáticamente los problemas detectados: `php artisan monitor:alumno-updates --fix`

## Diagnóstico de Errores Comunes

### Si las actualizaciones no se guardan:
1. Verifique que tiene permisos adecuados para editar alumnos o tutores.
2. Revise el Monitor de Formularios para asegurarse de que los datos se están enviando correctamente.
3. Utilice la Prueba de Actualización Directa para comprobar si las actualizaciones funcionan fuera del flujo normal:
   - Para alumnos: `/public/test_update.php`
   - Para tutores: `/public/test_update_tutor.php`
4. Verifique los registros de Laravel en `storage/logs/laravel.log`.

### Si aparecen errores de validación:
1. Asegúrese de que todos los campos obligatorios están completados.
2. Verifique que los datos cumplen con las reglas de validación (por ejemplo, formato de correo electrónico, longitud máxima, etc.).
3. Si persisten los errores, compruebe los mensajes específicos que aparecen en el formulario.

## Mantenimiento

El sistema ahora incluye un proceso automático de monitoreo de actualizaciones que se ejecuta diariamente. Este proceso verifica que la funcionalidad de actualización esté funcionando correctamente y registra cualquier problema detectado.

Para revisar estos registros, puede acceder a `storage/logs/laravel.log` o utilizar el Monitor de Formularios.

---

Si encuentra algún problema adicional o necesita más asistencia, por favor contacte al equipo de soporte técnico.
