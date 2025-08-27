# 🔄 DIAGRAMAS DE SECUENCIA - SISTEMA DE TESIS

## 🔐 **1. PROCESO DE AUTENTICACIÓN Y AUTORIZACIÓN**

```mermaid
sequenceDiagram
    participant U as Usuario
    participant L as LoginController
    participant A as Auth
    participant SP as Spatie Permission
    participant DB as Database

    U->>L: POST /login (email, password)
    L->>A: Auth::attempt(credentials)
    A->>DB: SELECT * FROM users WHERE email = ?
    DB-->>A: User data
    A->>A: Hash::check(password, user.password)
    A-->>L: Authentication success
    L->>SP: $user->getRoleNames()
    SP->>DB: SELECT roles via model_has_roles
    DB-->>SP: User roles
    L->>SP: $user->getAllPermissions()
    SP->>DB: SELECT permissions via model_has_permissions + role_has_permissions
    DB-->>SP: User permissions
    SP-->>L: Permissions list
    L->>A: Auth::login(user)
    L-->>U: Redirect to dashboard with session
```

## 👨‍🎓 **2. GESTIÓN DE ALUMNOS (CRUD)**

```mermaid
sequenceDiagram
    participant A as Admin/Coordinador
    participant AC as AlumnoController
    participant V as Validator
    participant AM as AlumnoModel
    particle CM as CarreraModel
    participant DB as Database

    Note over A,DB: CREAR ALUMNO
    A->>AC: POST /alumnos (datos del alumno)
    AC->>V: Validate request data
    V-->>AC: Validation passed
    AC->>CM: Carrera::find(id_carrera)
    CM->>DB: SELECT * FROM carreras WHERE id = ?
    DB-->>CM: Carrera data
    CM-->>AC: Carrera exists
    AC->>AM: Alumno::create(data)
    AM->>DB: INSERT INTO alumnos
    DB-->>AM: Created alumno
    AM-->>AC: Alumno instance
    AC-->>A: Success response + redirect

    Note over A,DB: LISTAR ALUMNOS
    A->>AC: GET /alumnos
    AC->>AM: Alumno::with('carrera')
    AM->>DB: SELECT alumnos.*, carreras.nombre FROM alumnos JOIN carreras
    DB-->>AM: Alumnos with carrera data
    AM-->>AC: Collection of alumnos
    AC-->>A: Alumnos index view

    Note over A,DB: ACTUALIZAR ALUMNO
    A->>AC: PUT /alumnos/{id} (updated data)
    AC->>AM: Alumno::findOrFail(id)
    AM->>DB: SELECT * FROM alumnos WHERE id = ?
    DB-->>AM: Alumno data
    AC->>V: Validate updated data
    V-->>AC: Validation passed
    AC->>AM: $alumno->update(data)
    AM->>DB: UPDATE alumnos SET ... WHERE id = ?
    DB-->>AM: Update success
    AC-->>A: Success response

    Note over A,DB: SOFT DELETE ALUMNO
    A->>AC: DELETE /alumnos/{id}
    AC->>AM: Alumno::findOrFail(id)
    AM->>DB: SELECT * FROM alumnos WHERE id = ? AND deleted_at IS NULL
    DB-->>AM: Alumno data
    AC->>AM: $alumno->delete()
    AM->>DB: UPDATE alumnos SET deleted_at = NOW() WHERE id = ?
    DB-->>AM: Soft delete success
    AC-->>A: Success response
```

## 📚 **3. GESTIÓN DE TESIS Y PROYECTOS DOCKER**

```mermaid
sequenceDiagram
    participant T as Tutor/Admin
    participant TC as TesisController
    participant TM as TesisModel
    participant DS as DockerService
    participant D as Docker
    participant GH as GitHub
    participant DB as Database

    Note over T,DB: CREAR TESIS CON PROYECTO
    T->>TC: POST /tesis (datos + github_repo)
    TC->>TM: Tesis::create(data)
    TM->>DB: INSERT INTO tesis
    DB-->>TM: Created tesis
    TM-->>TC: Tesis instance
    TC-->>T: Success response

    Note over T,DB: DESPLEGAR PROYECTO DOCKER
    T->>TC: POST /tesis/{id}/deploy
    TC->>TM: Tesis::findOrFail(id)
    TM->>DB: SELECT * FROM tesis WHERE id = ?
    DB-->>TM: Tesis data
    TC->>DS: DockerService::deployProject(tesis)
    DS->>GH: Clone repository (github_repo)
    GH-->>DS: Repository files
    DS->>DS: detectRequiredPhpVersion()
    DS->>DS: generateDockerfile()
    DS->>D: docker build -t project_name .
    D-->>DS: Build success + image_id
    DS->>D: docker run -d -p 8080:80 project_name
    D-->>DS: Container created + container_id
    DS->>TM: Update tesis (container_id, project_url, last_deployed)
    TM->>DB: UPDATE tesis SET container_id = ?, project_url = ?, last_deployed = NOW()
    DB-->>TM: Update success
    DS-->>TC: Deployment success
    TC-->>T: Success response with project URL

    Note over T,DB: MONITOREAR ESTADO DEL CONTENEDOR
    T->>TC: GET /tesis/{id}/status
    TC->>TM: Tesis::findOrFail(id)
    TM->>DB: SELECT container_id FROM tesis WHERE id = ?
    DB-->>TM: Container ID
    TC->>DS: DockerService::getContainerStatus(container_id)
    DS->>D: docker ps --filter id=container_id
    D-->>DS: Container status
    DS->>TM: Update container_status
    TM->>DB: UPDATE tesis SET container_status = ?
    DB-->>TM: Update success
    DS-->>TC: Status info
    TC-->>T: Status response
```

## 📊 **4. EXPORTACIÓN DE DATOS**

```mermaid
sequenceDiagram
    participant A as Admin
    participant EC as ExportController
    participant EX as Export Class (Maatwebsite)
    participant DB as Database
    participant F as File System

    A->>EC: GET /export/alumnos
    EC->>EX: new AlumnosExport()
    EX->>DB: SELECT alumnos.*, carreras.nombre FROM alumnos JOIN carreras
    DB-->>EX: Alumnos data with carreras
    EX->>EX: Transform data to Excel format
    EX->>F: Generate Excel file
    F-->>EX: File path
    EX-->>EC: Excel file
    EC-->>A: Download Excel file

    A->>EC: GET /export/tesis
    EC->>EX: new TesisExport()
    EX->>DB: SELECT tesis.*, alumnos.nombre, tutores.nombre FROM tesis JOIN alumnos JOIN tutores
    DB-->>EX: Tesis data with relations
    EX->>EX: Transform data (including project URLs)
    EX->>F: Generate Excel file
    F-->>EX: File path
    EX-->>EC: Excel file
    EC-->>A: Download Excel file
```

## 🔄 **5. FLUJO COMPLETO: DE ALUMNO A PROYECTO DESPLEGADO**

```mermaid
sequenceDiagram
    participant A as Admin
    participant AL as Alumno
    participant T as Tutor
    participant SYS as Sistema
    participant D as Docker
    participant DB as Database

    Note over A,DB: SETUP INICIAL
    A->>SYS: Crear Carrera
    SYS->>DB: INSERT carreras
    A->>SYS: Crear Alumno (con carrera)
    SYS->>DB: INSERT alumnos
    A->>SYS: Crear Tutor
    SYS->>DB: INSERT tutores
    A->>SYS: Asignar Permisos
    SYS->>DB: INSERT model_has_permissions

    Note over A,DB: GESTIÓN DE TESIS
    T->>SYS: Crear Tesis (alumno + tutor)
    SYS->>DB: INSERT tesis (estado: pendiente)
    T->>SYS: Actualizar estado a "en_progreso"
    SYS->>DB: UPDATE tesis SET estado = 'en_progreso'
    AL->>SYS: Subir repositorio GitHub
    SYS->>DB: UPDATE tesis SET github_repo = ?

    Note over A,DB: DESPLIEGUE DEL PROYECTO
    T->>SYS: Solicitar despliegue
    SYS->>D: DockerService::deployProject()
    D-->>SYS: Container ID + URL
    SYS->>DB: UPDATE tesis (container_id, project_url, last_deployed)
    T->>SYS: Evaluar proyecto
    SYS->>DB: UPDATE tesis SET calificacion = ?, estado = 'completado'

    Note over A,DB: REPORTES Y SEGUIMIENTO
    A->>SYS: Generar reporte de tesis
    SYS->>DB: SELECT with all relations
    DB-->>SYS: Complete data
    SYS-->>A: Excel export with project URLs
```
