# Base de datos de proyectos en Laragon

El despliegue local utiliza MySQL/MariaDB y una base independiente por proyecto. Reutiliza el nombre guardado al volver a desplegar. Los seeders se ejecutan únicamente cuando se seleccionan; la base no se borra automáticamente. La clonación solamente obtiene el repositorio.

La URL principal usa el dominio especial `<nombre>.localhost`, que los navegadores resuelven hacia el equipo local sin modificar el archivo `hosts` de Windows. El virtual host conserva también `<nombre>.test` como alias para instalaciones que ya tengan esa entrada configurada.

En **Proyectos → Despliegue**:

- **Archivo .env:** cargue directamente el archivo del proyecto (hasta 128 KB). Se crea `DB_DATABASE` con las credenciales `DB_HOST`, `DB_PORT`, `DB_USERNAME` y `DB_PASSWORD` del archivo. El usuario necesita permiso para crear la base. Debe usar `DB_CONNECTION=mysql`, sin URL de conexión. La conexión queda en el `.env` del proyecto y se conserva al redesplegar; sus credenciales no se guardan en los metadatos de la tesis.
- **Migraciones y seeders:** el sistema detecta las migraciones y las ejecuta completas. Si encuentra `DatabaseSeeder` y no se cargó un backup, lo ejecuta en el primer despliegue y registra el resultado para no repetir seeders no idempotentes. Si el código del seeder contiene correos y contraseñas literales de prueba, aparecen en la pantalla del proyecto después de una ejecución exitosa.
- **Crear solamente la base:** cargue el `.env`, deje el backup vacío y desmarque migraciones y seeders.

- **Sin backup:** ejecuta las migraciones de Laravel pendientes. Un Laravel sin migraciones necesita tablas existentes o un backup.
- **Backup con estructura y datos:** importe el SQL directamente; deje desmarcada la opción de ejecutar migraciones antes del backup.
- **Backup con solo datos:** marque la opción de ejecutar migraciones antes de importarlo. Los datos deben corresponder con esas tablas.
- Puede cargar un archivo o elegir un backup guardado del mismo proyecto. El guardado tiene prioridad.

Se admite SQL, ZIP, TAR y GZ. Los archivos comprimidos deben contener un único SQL, de hasta 100 MB descomprimido. Se restaura la base, no archivos de aplicación. Los encabezados estándar `CREATE DATABASE` y `USE` de dumps se adaptan a la base del proyecto. Referencias calificadas a otras bases se rechazan; exporte solamente la base de la aplicación. Los formatos nativos de PostgreSQL/SQLite requieren una conversión previa a MySQL.

Las credenciales se toman de la configuración MySQL del sistema. Se pueden separar en `.env` mediante `PROJECT_DB_HOST`, `PROJECT_DB_PORT`, `PROJECT_DB_USERNAME` y `PROJECT_DB_PASSWORD`. `PROJECT_LARAGON_PATH` permite cambiar la instalación de Laragon; `PROJECT_MYSQL_BINARY` permite indicar la ruta completa a `mysql.exe`.

El usuario configurado necesita crear bases y crear un usuario temporal con permisos sobre la base del proyecto para importar. Ese usuario se elimina al finalizar. La importación comprueba el código de salida del cliente y no ejecuta SQL con permisos sobre las demás bases. Una importación SQL que falla puede haber aplicado algunas sentencias: MySQL no permite revertir todo el DDL de un dump. El estado se marca como fallido y muestra el error; corrija el dump antes de reintentar.

La configuración cacheada del proyecto se elimina antes de ejecutar Artisan, y los subprocesos reciben el entorno del proyecto, evitando heredar la conexión de la aplicación principal. Se conserva la `APP_KEY` existente.

Composer se ejecuta con PHP CLI y carpetas propias en `storage/app/project-composer` para configuración, caché y temporales. No necesita `APPDATA` ni escribir en `C:\WINDOWS`. Se establece `sys_temp_dir` únicamente para ese proceso. Si la instalación de Composer no se detecta, configure `PROJECT_COMPOSER_PHAR` con la ruta a `composer.phar`; `PROJECT_COMPOSER_RUNTIME_PATH` permite cambiar la carpeta de trabajo, que debe ser escribible por el servidor web.

Composer, migraciones y seeders comparten la detección de PHP CLI. Bajo Apache, `PHP_BINARY` puede contener `httpd.exe`, por lo que se descarta y se busca `php.exe` junto al `php.ini` cargado o en la instalación de PHP. Cada candidato se comprueba en modo CLI. `PROJECT_PHP_BINARY` permite indicar explícitamente la ruta completa a `php.exe`.

Si el proyecto contiene `package.json` y un script `build`, el despliegue instala las dependencias de desarrollo y genera automáticamente `public/build/manifest.json`. Una compilación vigente se reutiliza. La descarga de npm usa caché local y reintenta cortes de red y bloqueos transitorios de Windows. `PROJECT_NPM_BINARY` permite indicar la ruta a `npm` cuando no está disponible en el PATH.

Apache tiene un virtual host dinámico para `*.localhost` mediante `mod_vhost_alias`. Cada nombre se dirige a `C:/laragon/www/<proyecto>/public`, por lo que los despliegues nuevos no necesitan modificar el archivo `hosts` ni reiniciar Apache.

## Verificación

`php vendor/phpunit/phpunit/phpunit tests/Unit` ejecuta las pruebas del servicio y del controlador. Las pruebas del controlador usan SQLite en memoria.

Para incluir pruebas reales de importación en PowerShell:

```powershell
$env:PROJECT_DB_INTEGRATION = '1'
php vendor/phpunit/phpunit/phpunit tests/Unit
Remove-Item Env:PROJECT_DB_INTEGRATION
```

Las pruebas de integración crean bases con nombres aleatorios `project_db_test_*` y las eliminan al terminar. No ejecutan migraciones sobre la base del sistema.
