<?php

namespace App\Imports;

use App\Models\Tutor;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Log;

class TutoresImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
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
            $especialidad = trim((string)($row['especialidad'] ?? ''));
            $biografia = trim((string)($row['biografia'] ?? ''));
            $activo = isset($row['activo']) ? (bool) $row['activo'] : true;

            if (empty($nombre) || empty($apellido) || empty($email)) {
                $this->errors[] = "Fila con datos incompletos encontrada (nombre, apellido y email son obligatorios)";
                return null;
            }

            // Check if tutor already exists by email
            $existingTutor = Tutor::where('email', $email)->first();
            if ($existingTutor) {
                $this->errors[] = "El tutor con email '{$email}' ya existe";
                return null;
            }

            $tutor = new Tutor([
                'nombre' => $nombre,
                'apellido' => $apellido,
                'email' => $email,
                'telefono' => $telefono,
                'especialidad' => $especialidad,
                'biografia' => $biografia,
                'activo' => $activo
            ]);

            $this->importedCount++;
            Log::info("Tutor importado: {$nombre} {$apellido}");

            return $tutor;

        } catch (\Exception $e) {
            $this->errors[] = "Error al procesar fila: " . $e->getMessage();
            Log::error("Error importando tutor: " . $e->getMessage());
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
            'especialidad' => 'nullable|max:255',
            'biografia' => 'nullable',
            'activo' => 'nullable'
        ];
    }

    /**
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'nombre.required' => 'El nombre del tutor es obligatorio',
            'apellido.required' => 'El apellido del tutor es obligatorio',
            'email.required' => 'El email del tutor es obligatorio',
            'email.email' => 'El email debe tener un formato válido'
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