# 📖 DOCUMENTACIÓN COMPLETA DE BASE DE DATOS
## Sistema de Gestión de Tesis - Laravel 10.x con Docker

---

## 🎯 **ÍNDICE DE DOCUMENTACIÓN**

### 📊 **1. [Diagrama Entidad-Relación (ERD)](./database_erd_diagram.md)**
- **🎯 Propósito:** Visualización completa de todas las tablas y sus relaciones
- **📋 Contenido:** 
  - Diagrama ERD interactivo en Mermaid
  - Relaciones principales (1:N)
  - Sistema de permisos Spatie
  - Funcionalidades Docker integradas
- **👥 Audiencia:** Desarrolladores, Arquitectos de Software, DBAs

### 🔄 **2. [Diagramas de Secuencia](./database_sequence_diagrams.md)**
- **🎯 Propósito:** Flujos de trabajo y procesos del sistema
- **📋 Contenido:**
  - Autenticación y autorización
  - CRUD de alumnos
  - Gestión de tesis y proyectos Docker
  - Exportación de datos
  - Flujo completo del sistema
- **👥 Audiencia:** Desarrolladores, Analistas de Sistemas, QA

### 📋 **3. [Estructura Detallada de Tablas](./database_table_structure.md)**
- **🎯 Propósito:** Especificaciones técnicas de cada tabla
- **📋 Contenido:**
  - Definición de columnas con tipos y restricciones
  - Índices y claves foráneas
  - Características especiales (Soft Deletes, ENUMs)
  - Documentación de campos Docker
- **👥 Audiencia:** Desarrolladores, DBAs, Documentación Técnica

---

## 🏗️ **ARQUITECTURA DEL SISTEMA**

### 🎓 **ENTIDADES PRINCIPALES**
1. **👥 USERS** - Usuarios del sistema (admin, coordinador, tutor)
2. **🏫 CARRERAS** - Carreras universitarias
3. **👨‍🎓 ALUMNOS** - Estudiantes registrados
4. **👨‍🏫 TUTORES** - Profesores/tutores
5. **📚 TESIS** - Proyectos de tesis con integración Docker

### 🔐 **SISTEMA DE SEGURIDAD**
- **Spatie Laravel-Permission** para roles y permisos
- **Múltiples roles:** Admin, Coordinador, Tutor
- **31+ permisos granulares** por módulo
- **Autenticación Laravel** con middleware

### 🚀 **FUNCIONALIDADES DOCKER**
- **Despliegue automático** de proyectos desde GitHub
- **Detección inteligente** de versión PHP requerida
- **Monitoreo de contenedores** en tiempo real
- **URLs dinámicas** para acceso a proyectos
- **Configuración JSON** para parámetros específicos

---

## 📊 **ESTADÍSTICAS DE LA BASE DE DATOS**

| Categoría | Cantidad | Detalles |
|-----------|----------|----------|
| **📋 Tablas Principales** | 5 | users, carreras, alumnos, tutores, tesis |
| **🔐 Tablas de Permisos** | 5 | permissions, roles, model_has_*, role_has_permissions |
| **🛠️ Tablas de Soporte** | 3 | password_reset_tokens, personal_access_tokens, failed_jobs |
| **🔗 Relaciones Principales** | 3 | carreras→alumnos, alumnos→tesis, tutores→tesis |
| **🔐 Relaciones de Permisos** | 3 | users↔roles, users↔permissions, roles↔permissions |
| **🚀 Campos Docker** | 7 | github_repo, project_type, container_id, etc. |
| **✅ Soft Deletes** | 4 | carreras, alumnos, tutores, tesis |

---

## 🎯 **CASOS DE USO PRINCIPALES**

### 👨‍💼 **ADMINISTRADOR**
- ✅ Gestión completa de carreras, alumnos y tutores
- ✅ Asignación de roles y permisos
- ✅ Monitoreo general del sistema
- ✅ Exportación de reportes en Excel
- ✅ Gestión de contenedores Docker

### 👩‍🎓 **COORDINADOR**
- ✅ Gestión de alumnos y tutores de su área
- ✅ Supervisión de tesis en progreso
- ✅ Asignación de tutores a alumnos
- ✅ Generación de reportes específicos

### 👨‍🏫 **TUTOR**
- ✅ Gestión de sus tesis asignadas
- ✅ Evaluación y calificación de proyectos
- ✅ Despliegue de proyectos en Docker
- ✅ Monitoreo del progreso de alumnos

---

## 🔧 **CARACTERÍSTICAS TÉCNICAS**

### 🛡️ **SEGURIDAD**
- **Password Hashing:** Bcrypt con Laravel
- **Soft Deletes:** Preserva integridad referencial
- **Validación de Datos:** Form Requests personalizados
- **CSRF Protection:** Token en formularios
- **Unique Constraints:** Email, cédula, matrícula únicos

### 📈 **ESCALABILIDAD**
- **Índices Optimizados:** En campos de búsqueda frecuente
- **Relaciones Eficientes:** Eager loading con Eloquent
- **Queue System:** Para tareas pesadas (Docker builds)
- **Caching:** Redis para sesiones y cache

### 🔄 **MANTENIMIENTO**
- **Migraciones Versionadas:** Control de cambios en BD
- **Seeders Configurables:** Datos iniciales automáticos
- **Logs Estructurados:** Laravel Log para debugging
- **Backup Automático:** Comandos Artisan para respaldo

---

## 📝 **NOTAS DE IMPLEMENTACIÓN**

### 🎯 **MEJORES PRÁCTICAS APLICADAS**
- ✅ **Naming Convention:** snake_case para BD, camelCase para código
- ✅ **Foreign Keys:** Con nombres descriptivos y ON DELETE CASCADE apropiado
- ✅ **Timestamps:** created_at y updated_at en todas las tablas principales
- ✅ **JSON Fields:** Para configuraciones flexibles (project_config)
- ✅ **ENUM Values:** Estados controlados y predefinidos

### 🚀 **FUNCIONALIDADES AVANZADAS**
- ✅ **Docker Integration:** Despliegue automático de proyectos
- ✅ **GitHub Integration:** Clone y build automático
- ✅ **Excel Export:** Reportes completos con Maatwebsite
- ✅ **Real-time Monitoring:** Estado de contenedores en vivo
- ✅ **Dynamic URLs:** Acceso directo a proyectos desplegados

### 🔮 **EXTENSIBILIDAD FUTURA**
- 🎯 **API REST:** Preparado para Laravel Sanctum
- 🎯 **WebSockets:** Para notificaciones en tiempo real
- 🎯 **Multi-tenant:** Estructura lista para múltiples instituciones
- 🎯 **CI/CD Integration:** Hooks para deployment automático
- 🎯 **File Storage:** S3/MinIO para documentos grandes

---

## 🤝 **CONTRIBUCIÓN Y MANTENIMIENTO**

### 📋 **PARA DESARROLLADORES**
1. **Leer** este índice completo
2. **Revisar** los diagramas ERD y de secuencia
3. **Consultar** la estructura detallada de tablas
4. **Ejecutar** migraciones y seeders en orden
5. **Testear** funcionalidades Docker antes de desplegar

### 🔧 **PARA ADMINISTRADORES**
1. **Backup regular** de la base de datos
2. **Monitoreo** de logs de Docker y Laravel
3. **Actualización** de seeders con nuevos permisos
4. **Verificación** de integridad referencial
5. **Limpieza** periódica de contenedores Docker huérfanos

---

**📅 Última actualización:** $(Get-Date -Format "yyyy-MM-dd HH:mm:ss")  
**👩‍💻 Generado por:** GitHub Copilot  
**🔗 Proyecto:** Sistema de Gestión de Tesis v1.0  
**🎯 Laravel:** 10.x con PHP 8.2 y Docker Integration
