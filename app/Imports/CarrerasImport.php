<?php

namespace App\Imports;

use App\Models\Carrera;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Facades\Log;

class CarrerasImport implements ToModel, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
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
            $descripcion = trim((string)($row['descripcion'] ?? ''));
            $activo = isset($row['activo']) ? (bool) $row['activo'] : true;

            if (empty($nombre)) {
                $this->errors[] = "Fila sin nombre de carrera encontrada";
                return null;
            }

            // Check if carrera already exists
            $existingCarrera = Carrera::where('nombre', $nombre)->first();
            if ($existingCarrera) {
                $this->errors[] = "La carrera '{$nombre}' ya existe";
                return null;
            }

            $carrera = new Carrera([
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'activo' => $activo
            ]);

            $this->importedCount++;
            Log::info("Carrera importada: {$nombre}");

            return $carrera;

        } catch (\Exception $e) {
            $this->errors[] = "Error al procesar fila: " . $e->getMessage();
            Log::error("Error importando carrera: " . $e->getMessage());
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
            'descripcion' => 'nullable',
            'activo' => 'nullable'
        ];
    }

    /**
     * @return array
     */
    public function customValidationMessages()
    {
        return [
            'nombre.required' => 'El nombre de la carrera es obligatorio',
            'nombre.string' => 'El nombre debe ser un texto válido',
            'nombre.max' => 'El nombre no puede exceder 255 caracteres'
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