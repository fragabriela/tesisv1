# 🏗️ DIAGRAMA DE BASE DE DATOS - SISTEMA DE TESIS

## 📊 **DIAGRAMA ENTIDAD-RELACIÓN (ERD)**

```mermaid
erDiagram
    USERS {
        bigint id PK
        string name
        string email UK "UNIQUE"
        timestamp email_verified_at "NULLABLE"
        string password
        string remember_token "NULLABLE"
        timestamp created_at
        timestamp updated_at
    }

    CARRERAS {
        bigint id PK
        string nombre
        text descripcion
        boolean activo "DEFAULT: true"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "SOFT DELETE"
    }

    ALUMNOS {
        bigint id PK
        string nombre
        string apellido
        string email UK "UNIQUE"
        string telefono
        string cedula UK "UNIQUE"
        string matricula UK "UNIQUE"
        date fecha_nacimiento
        string direccion "NULLABLE"
        bigint id_carrera FK
        enum estado "activo|inactivo|egresado, DEFAULT: activo"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "SOFT DELETE"
    }

    TUTORES {
        bigint id PK
        string nombre
        string apellido
        string email UK "UNIQUE"
        string telefono
        string especialidad
        text biografia "NULLABLE"
        boolean activo "DEFAULT: true"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "SOFT DELETE"
    }

    TESIS {
        bigint id PK
        string titulo
        text descripcion
        date fecha_inicio
        date fecha_fin "NULLABLE"
        bigint alumno_id FK
        bigint tutor_id FK
        enum estado "pendiente|en_progreso|completado|rechazado, DEFAULT: pendiente"
        integer calificacion "NULLABLE"
        text observaciones "NULLABLE"
        string documento_url "NULLABLE"
        string github_repo "NULLABLE"
        string project_type "NULLABLE"
        string container_id "NULLABLE"
        string container_status "NULLABLE"
        string project_url "NULLABLE"
        json project_config "NULLABLE"
        timestamp last_deployed "NULLABLE"
        timestamp created_at
        timestamp updated_at
        timestamp deleted_at "SOFT DELETE"
    }

    PERMISSIONS {
        bigint id PK
        string name
        string guard_name
        timestamp created_at
        timestamp updated_at
    }

    ROLES {
        bigint id PK
        string name
        string guard_name
        timestamp created_at
        timestamp updated_at
    }

    MODEL_HAS_PERMISSIONS {
        bigint permission_id FK
        string model_type
        bigint model_id
    }

    MODEL_HAS_ROLES {
        bigint role_id FK
        string model_type
        bigint model_id
    }

    ROLE_HAS_PERMISSIONS {
        bigint permission_id FK
        bigint role_id FK
    }

    PASSWORD_RESET_TOKENS {
        string email PK
        string token
        timestamp created_at "NULLABLE"
    }

    PERSONAL_ACCESS_TOKENS {
        bigint id PK
        string tokenable_type
        bigint tokenable_id
        string name
        string token UK "UNIQUE"
        text abilities "NULLABLE"
        timestamp last_used_at "NULLABLE"
        timestamp expires_at "NULLABLE"
        timestamp created_at
        timestamp updated_at
    }

    FAILED_JOBS {
        bigint id PK
        string uuid UK "UNIQUE"
        text connection
        text queue
        longtext payload
        longtext exception
        timestamp failed_at "DEFAULT: CURRENT_TIMESTAMP"
    }

    %% RELACIONES PRINCIPALES
    CARRERAS ||--o{ ALUMNOS : "id_carrera"
    ALUMNOS ||--o{ TESIS : "alumno_id"
    TUTORES ||--o{ TESIS : "tutor_id"

    %% RELACIONES DE PERMISOS (Spatie)
    USERS ||--o{ MODEL_HAS_ROLES : "model_id"
    USERS ||--o{ MODEL_HAS_PERMISSIONS : "model_id"
    ROLES ||--o{ MODEL_HAS_ROLES : "role_id"
    ROLES ||--o{ ROLE_HAS_PERMISSIONS : "role_id"
    PERMISSIONS ||--o{ MODEL_HAS_PERMISSIONS : "permission_id"
    PERMISSIONS ||--o{ ROLE_HAS_PERMISSIONS : "permission_id"

    %% RELACIONES DE TOKENS
    USERS ||--o{ PERSONAL_ACCESS_TOKENS : "tokenable_id"
```

## 🔍 **DETALLES DE RELACIONES**

### 🎯 **Relaciones Principales (1:N)**
- **CARRERAS** → **ALUMNOS**: Una carrera puede tener muchos alumnos
- **ALUMNOS** → **TESIS**: Un alumno puede tener muchas tesis
- **TUTORES** → **TESIS**: Un tutor puede dirigir muchas tesis

### 🔐 **Sistema de Permisos (Spatie Laravel-Permission)**
- **USERS** ↔ **ROLES**: Relación Many-to-Many (a través de model_has_roles)
- **USERS** ↔ **PERMISSIONS**: Relación Many-to-Many (a través de model_has_permissions)
- **ROLES** ↔ **PERMISSIONS**: Relación Many-to-Many (a través de role_has_permissions)

### 🚀 **Funcionalidades Docker/Proyectos**
- Campos en **TESIS** para gestión de proyectos:
  - `github_repo`: URL del repositorio
  - `project_type`: Tipo de proyecto (Laravel, Java, etc.)
  - `container_id`: ID del contenedor Docker
  - `container_status`: Estado del contenedor
  - `project_url`: URL de acceso al proyecto desplegado
  - `project_config`: Configuración en JSON
  - `last_deployed`: Fecha del último despliegue
```
