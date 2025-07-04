<div class="alert alert-info alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
    <h5><i class="icon fas fa-info"></i> ¿Problemas con el formulario?</h5>
    <p>Si experimenta problemas al guardar los datos, puede utilizar nuestras herramientas de diagnóstico:</p>
    <ul>
        <li><a href="{{ route('debug.form.monitor') }}" target="_blank">Monitor de Formularios</a> - Ver los envíos recientes de formularios</li>
        <li><a href="{{ url('/test_update.php') }}" target="_blank">Prueba de Actualización (Alumnos)</a></li>
        <li><a href="{{ url('/test_update_tutor.php') }}" target="_blank">Prueba de Actualización (Tutores)</a></li>
    </ul>
</div>
