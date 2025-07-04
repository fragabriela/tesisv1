<?php

namespace App\Exports;

use App\Models\Tesis;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TesisExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return Tesis::with(['alumno', 'tutor'])->get();
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        return [
            'ID',
            'Título',
            'Alumno',
            'Tutor',
            'Estado',
            'Fecha Inicio',
            'Fecha Fin',
            'Calificación',
            'Fecha Creación',
        ];
    }

    /**
     * @param mixed $row
     * @return array
     */
    public function map($row): array
    {
        $estado = '';
        switch($row->estado) {
            case 'pendiente':
                $estado = 'Pendiente';
                break;
            case 'en_progreso':
                $estado = 'En Progreso';
                break;
            case 'completado':
                $estado = 'Completado';
                break;
            case 'rechazado':
                $estado = 'Rechazado';
                break;
        }
        
        return [
            $row->id,
            $row->titulo,
            $row->alumno->nombre . ' ' . $row->alumno->apellido,
            $row->tutor->nombre . ' ' . $row->tutor->apellido,
            $estado,
            $row->fecha_inicio->format('d/m/Y'),
            $row->fecha_fin ? $row->fecha_fin->format('d/m/Y') : 'N/A',
            $row->calificacion ?? 'N/A',
            $row->created_at->format('d/m/Y'),
        ];
    }

    /**
     * @param Worksheet $sheet
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Style the first row as bold text
            1 => ['font' => ['bold' => true]],
        ];
    }
}
