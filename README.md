# Sistema de Gestión Académica

Este sistema permite la gestión de información académica incluyendo carreras, alumnos, tutores y tesis.

## Características

- **Gestión de Carreras**: Administración de carreras universitarias.
- **Gestión de Alumnos**: Registro y seguimiento de estudiantes.
- **Gestión de Tutores**: Control de tutores académicos.
- **Gestión de Tesis**: Administración de proyectos de tesis.
- **Dashboard**: Panel con estadísticas y gráficas.
- **Reportes**: Exportación de datos en PDF y Excel.
- **Control de Acceso**: Sistema de roles y permisos.
- **Herramientas de Diagnóstico**: Monitor de formularios y scripts de prueba para facilitar la detección y solución de problemas.

## Tecnologías Utilizadas

- **Laravel 10**: Framework PHP
- **MySQL**: Base de datos
- **AdminLTE 3**: Plantilla de administración
- **DataTables**: Tablas interactivas
- **Chart.js**: Gráficas y visualizaciones

## Herramientas de Diagnóstico

Para facilitar la detección y solución de problemas, el sistema incluye las siguientes herramientas:

### Monitor de Formularios
- **Ruta**: `/debug/form-monitor`
- **Descripción**: Muestra todos los envíos de formularios recientes y permite realizar pruebas de actualización.
- **Acceso**: Disponible desde el menú lateral en "Herramientas de Desarrollo"

### Scripts de Prueba Directa
- **Para Alumnos**: `/test_update.php`
- **Para Tutores**: `/test_update_tutor.php`
- **Descripción**: Permiten realizar pruebas directas de actualización utilizando tanto Eloquent como SQL directo.

### Comandos de Monitoreo
- `php artisan monitor:alumno-updates`: Verifica la funcionalidad de actualización de alumnos
- `php artisan monitor:tutor-updates`: Verifica la funcionalidad de actualización de tutores
- **Opción adicional**: Agregar `--fix` para intentar solucionar problemas automáticamente

## Documentación Adicional

- **INSTRUCCIONES_USO.md**: Guía detallada para usuarios sobre cómo utilizar el sistema.
- **SOLUCION_PROBLEMAS.md**: Documentación sobre problemas conocidos y sus soluciones.
- **OPTIMIZACIONES.md**: Información sobre optimizaciones realizadas en el sistema.
- **Spatie Permission**: Control de roles y permisos
- **DomPDF**: Generación de documentos PDF
- **Laravel Excel**: Exportación de datos a Excel

## Requisitos

- PHP >= 8.1
- Composer
- MySQL >= 5.7
- Node.js y NPM

## Instalación

1. Clonar el repositorio:
```
git clone https://github.com/username/tesisv1.git
cd tesisv1
```

2. Instalar dependencias PHP:
```
composer install
```

3. Instalar dependencias JavaScript:
```
npm install && npm run dev
```

4. Configurar el archivo .env:
```
cp .env.example .env
php artisan key:generate
```

5. Configurar la base de datos en el archivo .env:
```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=tesisv1
DB_USERNAME=root
DB_PASSWORD=
```

6. Ejecutar migraciones y seeders:
```
php artisan migrate --seed
```

7. Configurar el almacenamiento:
```
php artisan storage:link
```

8. Iniciar el servidor:
```
php artisan serve
```

## Usuarios Predefinidos

El sistema cuenta con tres usuarios predefinidos:

1. **Administrador**
   - Email: admin@example.com
   - Password: password
   - Rol: Administrador (acceso completo)

2. **Coordinador**
   - Email: coordinador@example.com
   - Password: password
   - Rol: Coordinador (acceso parcial)

3. **Tutor**
   - Email: tutor@example.com
   - Password: password
   - Rol: Tutor (acceso limitado)

## Estructura del Proyecto

- **app/Models**: Modelos Eloquent para las entidades del sistema
- **app/Http/Controllers**: Controladores para manejar las peticiones
- **app/Exports**: Clases para exportación de datos
- **database/migrations**: Migraciones de la base de datos
- **database/seeders**: Seeders para datos iniciales
- **resources/views**: Vistas blade del sistema
- **public**: Assets públicos (CSS, JS, imágenes)

## Licencia

Este proyecto está licenciado bajo [MIT License](LICENSE).

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
