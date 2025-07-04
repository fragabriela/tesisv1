# Configuración para config/adminlte.php

Actualizar la sección del menú en el archivo `config/adminlte.php` con lo siguiente:

```php
'menu' => [
    // Navbar items:
    [
        'type' => 'navbar-search',
        'text' => 'search',
        'topnav_right' => true,
    ],
    [
        'type' => 'fullscreen-widget',
        'topnav_right' => true,
    ],

    // Sidebar items:
    [
        'type' => 'sidebar-menu-search',
        'text' => 'search',
    ],
    [
        'text' => 'Dashboard',
        'url' => 'dashboard',
        'icon' => 'fas fa-fw fa-tachometer-alt',
        'can' => 'ver dashboard',
    ],
    
    ['header' => 'GESTIÓN ACADÉMICA'],
    [
        'text' => 'Carreras',
        'url' => 'carrera',
        'icon' => 'fas fa-fw fa-graduation-cap',
        'can' => 'ver carreras',
    ],
    [
        'text' => 'Alumnos',
        'url' => 'alumno',
        'icon' => 'fas fa-fw fa-user-graduate',
        'can' => 'ver alumnos',
    ],
    [
        'text' => 'Tutores',
        'url' => 'tutor',
        'icon' => 'fas fa-fw fa-chalkboard-teacher',
        'can' => 'ver tutores',
    ],
    [
        'text' => 'Tesis',
        'url' => 'tesis',
        'icon' => 'fas fa-fw fa-book',
        'can' => 'ver tesis',
    ],

    ['header' => 'REPORTES'],
    [
        'text' => 'Exportar Datos',
        'icon' => 'fas fa-fw fa-file-export',
        'submenu' => [
            [
                'text' => 'Carreras PDF',
                'url' => 'carrera/export-pdf',
                'icon' => 'fas fa-fw fa-file-pdf',
                'can' => 'exportar carreras',
            ],
            [
                'text' => 'Carreras Excel',
                'url' => 'carrera/export-excel',
                'icon' => 'fas fa-fw fa-file-excel',
                'can' => 'exportar carreras',
            ],
            [
                'text' => 'Alumnos PDF',
                'url' => 'alumno/export-pdf',
                'icon' => 'fas fa-fw fa-file-pdf',
                'can' => 'exportar alumnos',
            ],
            [
                'text' => 'Alumnos Excel',
                'url' => 'alumno/export-excel',
                'icon' => 'fas fa-fw fa-file-excel',
                'can' => 'exportar alumnos',
            ],
            [
                'text' => 'Tutores PDF',
                'url' => 'tutor/export-pdf',
                'icon' => 'fas fa-fw fa-file-pdf',
                'can' => 'exportar tutores',
            ],
            [
                'text' => 'Tutores Excel',
                'url' => 'tutor/export-excel',
                'icon' => 'fas fa-fw fa-file-excel',
                'can' => 'exportar tutores',
            ],
            [
                'text' => 'Tesis PDF',
                'url' => 'tesis/export-pdf',
                'icon' => 'fas fa-fw fa-file-pdf',
                'can' => 'exportar tesis',
            ],
            [
                'text' => 'Tesis Excel',
                'url' => 'tesis/export-excel',
                'icon' => 'fas fa-fw fa-file-excel',
                'can' => 'exportar tesis',
            ],
        ],
    ],
    
    ['header' => 'CONFIGURACIÓN'],
    [
        'text' => 'Usuarios',
        'url' => 'admin/users',
        'icon' => 'fas fa-fw fa-users',
        'can' => 'administrar usuarios',
    ],
],
```
