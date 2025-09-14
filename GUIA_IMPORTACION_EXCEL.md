# Guía de Importación de Excel

Esta funcionalidad permite importar datos desde archivos Excel (.xlsx, .xls, .csv) para las entidades: Carreras, Alumnos y Tutores.

## Cómo acceder a la funcionalidad

1. Navegue a la sección correspondiente (Carreras, Alumnos o Tutores)
2. Haga clic en el botón "Importar Excel" en la parte superior derecha
3. Seleccione su archivo Excel
4. Haga clic en "Importar"

## Formatos de archivo requeridos

### Carreras
El archivo debe contener las siguientes columnas (la primera fila debe contener los nombres):

| Columna | Tipo | Obligatorio | Descripción |
|---------|------|-------------|-------------|
| nombre | Texto | Sí | Nombre de la carrera |
| descripcion | Texto | No | Descripción de la carrera |
| activo | Número | No | 1 para activo, 0 para inactivo (por defecto: 1) |

**Ejemplo:**
```
nombre,descripcion,activo
Ingeniería en Sistemas,Carrera orientada al desarrollo de software,1
Administración de Empresas,Carrera enfocada en la gestión empresarial,1
```

### Alumnos
El archivo debe contener las siguientes columnas:

| Columna | Tipo | Obligatorio | Descripción |
|---------|------|-------------|-------------|
| nombre | Texto | Sí | Nombre del alumno |
| apellido | Texto | Sí | Apellido del alumno |
| email | Email | Sí | Correo electrónico |
| telefono | Texto | No | Número de teléfono |
| cedula | Texto | No | Número de cédula |
| matricula | Texto | No | Número de matrícula |
| fecha_nacimiento | Fecha | No | Formato: YYYY-MM-DD |
| carrera | Texto | No | Nombre de la carrera (debe existir) |
| direccion | Texto | No | Dirección del alumno |
| estado | Texto | No | activo, inactivo, graduado, retirado |

**Ejemplo:**
```
nombre,apellido,email,telefono,cedula,matricula,fecha_nacimiento,carrera,direccion,estado
Juan,Pérez,juan.perez@email.com,099123456,12345678,2021001,1995-03-15,Ingeniería en Sistemas,Av. Principal 123,activo
```

### Tutores
El archivo debe contener las siguientes columnas:

| Columna | Tipo | Obligatorio | Descripción |
|---------|------|-------------|-------------|
| nombre | Texto | Sí | Nombre del tutor |
| apellido | Texto | Sí | Apellido del tutor |
| email | Email | Sí | Correo electrónico |
| telefono | Texto | No | Número de teléfono |
| especialidad | Texto | No | Área de especialidad |
| biografia | Texto | No | Biografía del tutor |
| activo | Número | No | 1 para activo, 0 para inactivo (por defecto: 1) |

**Ejemplo:**
```
nombre,apellido,email,telefono,especialidad,biografia,activo
Dr. Roberto,Silva,roberto.silva@universidad.edu,095123456,Desarrollo de Software,Experto en metodologías ágiles,1
```

## Consideraciones importantes

1. **Formato de archivo**: Se aceptan archivos .xlsx, .xls y .csv con un tamaño máximo de 2MB
2. **Primera fila**: Debe contener los nombres de las columnas exactamente como se especifica
3. **Duplicados**: Los registros duplicados (por email, cédula, etc.) serán omitidos
4. **Errores**: Al finalizar la importación se mostrará un resumen con el número de registros importados y errores encontrados
5. **Relaciones**: Para alumnos, la carrera debe existir previamente o se dejará sin asignar

## Archivos de ejemplo

Se han creado archivos de ejemplo en la carpeta `storage/app/public/samples/`:
- `carreras_sample.csv`: Ejemplo de importación de carreras
- `alumnos_sample.csv`: Ejemplo de importación de alumnos  
- `tutores_sample.csv`: Ejemplo de importación de tutores

## Resolución de problemas

### Error: "No se puede leer el archivo"
- Verifique que el archivo esté en formato Excel (.xlsx, .xls) o CSV
- Asegúrese de que el archivo no esté corrupto

### Error: "Columna requerida no encontrada"
- Verifique que la primera fila contenga los nombres de columnas exactos
- Las columnas obligatorias deben estar presentes

### Error: "Email/Cédula ya existe"
- Revise que no haya registros duplicados en su archivo
- Los emails y cédulas deben ser únicos en el sistema

### Algunos registros no se importaron
- Revise el mensaje de errores al final de la importación
- Corrija los datos problemáticos y vuelva a importar solo esos registros