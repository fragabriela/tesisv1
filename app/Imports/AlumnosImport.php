<?php

namespace App\Imports;

use App\Models\Alumno;
use App\Models\Carrera;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AlumnosImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    use Importable;

    private $importedCount = 0;
    private $errors = [];

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        try {
            // Clean and validate data - convert to string to handle Excel numeric fields
            $nombre = trim((string)($row['nombre'] ?? ''));
            $apellido = trim((string)($row['apellido'] ?? ''));
            $email = trim((string)($row['email'] ?? ''));
            $telefono = trim((string)($row['telefono'] ?? ''));
            $cedula = trim((string)($row['cedula'] ?? ''));
            $matricula = trim((string)($row['matricula'] ?? ''));
            $fecha_nacimiento = $row['fecha_nacimiento'] ?? null;
            $direccion = trim((string)($row['direccion'] ?? ''));
            $carrera_nombre = trim((string)($row['carrera'] ?? ''));
            $estado = trim((string)($row['estado'] ?? 'activo'));

            if (empty($nombre) || empty($apellido) || empty($email)) {
                $this->errors[] = "Fila con datos incompletos encontrada (nombre, apellido y email son obligatorios)";
                return null;
            }

            // Check if alumno already exists by email or cedula (only if cedula is not empty)
            $existingQuery = Alumno::where('email', $email);
            if (!empty($cedula)) {
                $existingQuery->orWhere('cedula', $cedula);
            }
            $existingAlumno = $existingQuery->first();
            
            if ($existingAlumno) {
                if ($existingAlumno->email == $email) {
                    $this->errors[] = "El alumno con email '{$email}' ya existe";
                } else {
                    $this->errors[] = "El alumno con cédula '{$cedula}' ya existe";
                }
                return null;
            }

            // Find carrera by name
            $carrera = null;
            if (!empty($carrera_nombre)) {
                $carrera = Carrera::where('nombre', 'LIKE', "%{$carrera_nombre}%")->first();
                if (!$carrera) {
                    $this->errors[] = "No se encontró la carrera '{$carrera_nombre}' para el alumno {$nombre} {$apellido}";
                }
            }

            // Process fecha_nacimiento
            $fechaNacimiento = null;
            if ($fecha_nacimiento) {
                try {
                    if (is_numeric($fecha_nacimiento)) {
                        // Excel date serial number
                        $fechaNacimiento = Carbon::createFromFormat('Y-m-d', '1900-01-01')->addDays($fecha_nacimiento - 2);
                    } else {
                        // Try to parse as regular date
                        $fechaNacimiento = Carbon::parse($fecha_nacimiento);
                    }
                } catch (\Exception $e) {
                    $this->errors[] = "Formato de fecha inválido para {$nombre} {$apellido}: {$fecha_nacimiento}";
                }
            }

            $alumno = new Alumno([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $email,
                'telefono' => $telefono,
                'cedula' => $cedula,
                'matricula' => $matricula,
                'fecha_nacimiento' => $fechaNacimiento,
                'direccion' => $direccion,
                'id_carrera' => $carrera ? $carrera->id : null,
                'estado' => $estado
            ]);

            $this->importedCount++;
            Log::info("Alumno importado: {$nombre} {$apellido}");

            return $alumno;

        } catch (\Exception $e) {
            $this->errors[] = "Error al procesar fila: " . $e->getMessage();
            Log::error("Error importando alumno: " . $e->getMessage());
            return null;
        }
    }

    /**
     * @return array
     */
    public function rules(): array
    {
        return [
            'nombre' => 'required|max:255',
            'apellido' => 'required|max:255',
            'email' => 'required|email|max:255',
            'telefono' => 'nullable|max:20',
            'cedula' => 'nullable|max:20',
            'matricula' => 'nullable|max:50',
            'direccion' => 'nullable',
            'carrera' => 'nullable',
            'estado' => 'nullable|in:activo,inactivo,graduado,retirado'
        ];
    }

    /**
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'nombre.required' => 'El nombre del alumno es obligatorio',
            'apellido.required' => 'El apellido del alumno es obligatorio',
            'email.required' => 'El email del alumno es obligatorio',
            'email.email' => 'El email debe tener un formato válido',
            'estado.in' => 'El estado debe ser: activo, inactivo, graduado o retirado'
        ];
    }

    /**
     * @return int
     */
    public function batchSize(): int
    {
        return 100;
    }

    /**
     * @return int
     */
    public function chunkSize(): int
    {
        return 100;
    }

    /**
     * Get the count of imported records
     */
    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    /**
     * Get import errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}