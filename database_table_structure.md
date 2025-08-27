# 📋 ESTRUCTURA DETALLADA DE TABLAS - SISTEMA DE TESIS

## 🏗️ **TABLAS PRINCIPALES DEL SISTEMA**

### 👥 **1. USERS** (Usuarios del Sistema)
| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `id` | BIGINT(20) UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Identificador único |
| `name` | VARCHAR(255) | NOT NULL | Nombre del usuario |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE | Email único para login |
| `email_verified_at` | TIMESTAMP | NULLABLE | Fecha de verificación del email |
| `password` | VARCHAR(255) | NOT NULL | Contraseña hasheada |
| `remember_token` | VARCHAR(100) | NULLABLE | Token para recordar sesión |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Fecha de actualización |

**Índices:**
- `users_email_unique` (UNIQUE) en `email`

---

### 🎓 **2. CARRERAS** (Carreras Universitarias)
| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `id` | BIGINT(20) UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Identificador único |
| `nombre` | VARCHAR(255) | NOT NULL | Nombre de la carrera |
| `descripcion` | TEXT | NOT NULL | Descripción detallada |
| `activo` | BOOLEAN | NOT NULL, DEFAULT 1 | Estado activo/inactivo |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Fecha de actualización |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

**Características:**
- ✅ Soft Deletes habilitado
- 🔍 Filtros por estado activo

---

### 👨‍🎓 **3. ALUMNOS** (Estudiantes)
| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `id` | BIGINT(20) UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Identificador único |
| `nombre` | VARCHAR(255) | NOT NULL | Nombre del alumno |
| `apellido` | VARCHAR(255) | NOT NULL | Apellido del alumno |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE | Email único del alumno |
| `telefono` | VARCHAR(255) | NOT NULL | Teléfono de contacto |
| `cedula` | VARCHAR(255) | NOT NULL, UNIQUE | Cédula única |
| `matricula` | VARCHAR(255) | NOT NULL, UNIQUE | Matrícula única |
| `fecha_nacimiento` | DATE | NOT NULL | Fecha de nacimiento |
| `direccion` | VARCHAR(255) | NULLABLE | Dirección del alumno |
| `id_carrera` | BIGINT(20) UNSIGNED | NOT NULL, FOREIGN KEY | Referencia a carreras.id |
| `estado` | ENUM | NOT NULL, DEFAULT 'activo' | activo, inactivo, egresado |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Fecha de actualización |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

**Índices:**
- `alumnos_email_unique` (UNIQUE) en `email`
- `alumnos_cedula_unique` (UNIQUE) en `cedula`
- `alumnos_matricula_unique` (UNIQUE) en `matricula`
- `alumnos_id_carrera_foreign` (FOREIGN KEY) en `id_carrera` → `carreras(id)`

**Características:**
- ✅ Soft Deletes habilitado
- 🔗 Relación con Carreras (N:1)
- 📊 Estados controlados por ENUM

---

### 👨‍🏫 **4. TUTORES** (Profesores/Tutores)
| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `id` | BIGINT(20) UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Identificador único |
| `nombre` | VARCHAR(255) | NOT NULL | Nombre del tutor |
| `apellido` | VARCHAR(255) | NOT NULL | Apellido del tutor |
| `email` | VARCHAR(255) | NOT NULL, UNIQUE | Email único del tutor |
| `telefono` | VARCHAR(255) | NOT NULL | Teléfono de contacto |
| `especialidad` | VARCHAR(255) | NOT NULL | Área de especialización |
| `biografia` | TEXT | NULLABLE | Biografía profesional |
| `activo` | BOOLEAN | NOT NULL, DEFAULT 1 | Estado activo/inactivo |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Fecha de actualización |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

**Índices:**
- `tutores_email_unique` (UNIQUE) en `email`

**Características:**
- ✅ Soft Deletes habilitado
- 🔍 Filtros por estado activo
- 📝 Biografía opcional para perfil

---

### 📚 **5. TESIS** (Proyectos de Tesis)
| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `id` | BIGINT(20) UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Identificador único |
| `titulo` | VARCHAR(255) | NOT NULL | Título de la tesis |
| `descripcion` | TEXT | NOT NULL | Descripción del proyecto |
| `fecha_inicio` | DATE | NOT NULL | Fecha de inicio |
| `fecha_fin` | DATE | NULLABLE | Fecha de finalización |
| `alumno_id` | BIGINT(20) UNSIGNED | NOT NULL, FOREIGN KEY | Referencia a alumnos.id |
| `tutor_id` | BIGINT(20) UNSIGNED | NOT NULL, FOREIGN KEY | Referencia a tutores.id |
| `estado` | ENUM | NOT NULL, DEFAULT 'pendiente' | pendiente, en_progreso, completado, rechazado |
| `calificacion` | INTEGER | NULLABLE | Calificación final (0-100) |
| `observaciones` | TEXT | NULLABLE | Observaciones del tutor |
| `documento_url` | VARCHAR(255) | NULLABLE | URL del documento final |
| `github_repo` | VARCHAR(255) | NULLABLE | **🚀 URL del repositorio GitHub** |
| `project_type` | VARCHAR(255) | NULLABLE | **🚀 Tipo de proyecto (Laravel, Java, etc.)** |
| `container_id` | VARCHAR(255) | NULLABLE | **🚀 ID del contenedor Docker** |
| `container_status` | VARCHAR(255) | NULLABLE | **🚀 Estado del contenedor** |
| `project_url` | VARCHAR(255) | NULLABLE | **🚀 URL del proyecto desplegado** |
| `project_config` | JSON | NULLABLE | **🚀 Configuración del proyecto** |
| `last_deployed` | TIMESTAMP | NULLABLE | **🚀 Última fecha de despliegue** |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Fecha de actualización |
| `deleted_at` | TIMESTAMP | NULLABLE | Soft delete timestamp |

**Índices:**
- `tesis_alumno_id_foreign` (FOREIGN KEY) en `alumno_id` → `alumnos(id)`
- `tesis_tutor_id_foreign` (FOREIGN KEY) en `tutor_id` → `tutores(id)`

**Características:**
- ✅ Soft Deletes habilitado
- 🔗 Relación con Alumnos (N:1)
- 🔗 Relación con Tutores (N:1)
- 🚀 **Integración Docker completa**
- 📊 Estados controlados por ENUM
- 📁 Soporte para archivos y repositorios

---

## 🔐 **SISTEMA DE PERMISOS (SPATIE LARAVEL-PERMISSION)**

### 🎭 **6. ROLES** (Roles del Sistema)
| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `id` | BIGINT(20) UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Identificador único |
| `name` | VARCHAR(255) | NOT NULL | Nombre del rol |
| `guard_name` | VARCHAR(255) | NOT NULL | Guard de autenticación |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Fecha de actualización |

**Índices:**
- `roles_name_guard_name_unique` (UNIQUE) en `name, guard_name`

---

### 🛡️ **7. PERMISSIONS** (Permisos del Sistema)
| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `id` | BIGINT(20) UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Identificador único |
| `name` | VARCHAR(255) | NOT NULL | Nombre del permiso |
| `guard_name` | VARCHAR(255) | NOT NULL | Guard de autenticación |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Fecha de actualización |

**Índices:**
- `permissions_name_guard_name_unique` (UNIQUE) en `name, guard_name`

---

### 🔗 **8. MODEL_HAS_ROLES** (Usuarios ↔ Roles)
| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `role_id` | BIGINT(20) UNSIGNED | NOT NULL, FOREIGN KEY | Referencia a roles.id |
| `model_type` | VARCHAR(255) | NOT NULL | Tipo de modelo (App\Models\User) |
| `model_id` | BIGINT(20) UNSIGNED | NOT NULL | ID del modelo (user.id) |

**Índices:**
- `model_has_roles_role_id_foreign` (FOREIGN KEY) en `role_id` → `roles(id)`
- `model_has_roles_model_id_model_type_index` en `model_id, model_type`

---

### 🔗 **9. MODEL_HAS_PERMISSIONS** (Usuarios ↔ Permisos)
| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `permission_id` | BIGINT(20) UNSIGNED | NOT NULL, FOREIGN KEY | Referencia a permissions.id |
| `model_type` | VARCHAR(255) | NOT NULL | Tipo de modelo (App\Models\User) |
| `model_id` | BIGINT(20) UNSIGNED | NOT NULL | ID del modelo (user.id) |

**Índices:**
- `model_has_permissions_permission_id_foreign` (FOREIGN KEY) en `permission_id` → `permissions(id)`
- `model_has_permissions_model_id_model_type_index` en `model_id, model_type`

---

### 🔗 **10. ROLE_HAS_PERMISSIONS** (Roles ↔ Permisos)
| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `permission_id` | BIGINT(20) UNSIGNED | NOT NULL, FOREIGN KEY | Referencia a permissions.id |
| `role_id` | BIGINT(20) UNSIGNED | NOT NULL, FOREIGN KEY | Referencia a roles.id |

**Índices:**
- `role_has_permissions_permission_id_foreign` (FOREIGN KEY) en `permission_id` → `permissions(id)`
- `role_has_permissions_role_id_foreign` (FOREIGN KEY) en `role_id` → `roles(id)`

---

## 🔑 **TABLAS DE SOPORTE**

### 🔐 **11. PASSWORD_RESET_TOKENS** (Tokens de Reset de Contraseña)
| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `email` | VARCHAR(255) | PRIMARY KEY | Email del usuario |
| `token` | VARCHAR(255) | NOT NULL | Token de reset |
| `created_at` | TIMESTAMP | NULLABLE | Fecha de creación |

---

### 🎟️ **12. PERSONAL_ACCESS_TOKENS** (Tokens de API)
| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `id` | BIGINT(20) UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Identificador único |
| `tokenable_type` | VARCHAR(255) | NOT NULL | Tipo de modelo tokenizable |
| `tokenable_id` | BIGINT(20) UNSIGNED | NOT NULL | ID del modelo tokenizable |
| `name` | VARCHAR(255) | NOT NULL | Nombre del token |
| `token` | VARCHAR(64) | NOT NULL, UNIQUE | Token único |
| `abilities` | TEXT | NULLABLE | Habilidades del token |
| `last_used_at` | TIMESTAMP | NULLABLE | Último uso |
| `expires_at` | TIMESTAMP | NULLABLE | Fecha de expiración |
| `created_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Fecha de creación |
| `updated_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP | Fecha de actualización |

---

### ❌ **13. FAILED_JOBS** (Trabajos Fallidos)
| Campo | Tipo | Restricciones | Descripción |
|-------|------|---------------|-------------|
| `id` | BIGINT(20) UNSIGNED | PRIMARY KEY, AUTO_INCREMENT | Identificador único |
| `uuid` | VARCHAR(255) | NOT NULL, UNIQUE | UUID único del trabajo |
| `connection` | TEXT | NOT NULL | Conexión utilizada |
| `queue` | TEXT | NOT NULL | Cola del trabajo |
| `payload` | LONGTEXT | NOT NULL | Datos del trabajo |
| `exception` | LONGTEXT | NOT NULL | Excepción ocurrida |
| `failed_at` | TIMESTAMP | DEFAULT CURRENT_TIMESTAMP | Fecha del fallo |

---

## 📊 **RESUMEN DE RELACIONES**

### 🎯 **Relaciones Principales (1:N)**
- `carreras.id` ← `alumnos.id_carrera` (Una carrera tiene muchos alumnos)
- `alumnos.id` ← `tesis.alumno_id` (Un alumno puede tener muchas tesis)
- `tutores.id` ← `tesis.tutor_id` (Un tutor puede dirigir muchas tesis)

### 🔐 **Relaciones de Permisos (N:M)**
- `users` ↔ `roles` (a través de `model_has_roles`)
- `users` ↔ `permissions` (a través de `model_has_permissions`)
- `roles` ↔ `permissions` (a través de `role_has_permissions`)

### 🚀 **Funcionalidades Docker Integradas**
- **Campos específicos en `tesis`:** `github_repo`, `project_type`, `container_id`, `container_status`, `project_url`, `project_config`, `last_deployed`
- **Permite:** Desplegar automáticamente proyectos de GitHub en contenedores Docker
- **Gestiona:** Estado y monitoreo de contenedores en tiempo real
