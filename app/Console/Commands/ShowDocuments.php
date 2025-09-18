<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Documento;

class ShowDocuments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'document:list';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'List all documents with their tutors and alumnos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $documentos = Documento::with(['tesis', 'alumno', 'tutor'])->get();
        
        $this->info("Lista de documentos:");
        $this->line("");
        
        foreach ($documentos as $doc) {
            $alumno = $doc->alumno ? $doc->alumno->nombre . ' ' . $doc->alumno->apellido : 'Sin alumno';
            $tutor = $doc->tutor ? $doc->tutor->nombre . ' ' . $doc->tutor->apellido : 'Sin tutor';
            $tesis = $doc->tesis ? $doc->tesis->titulo : 'Sin tesis';
            
            $this->info("ID: {$doc->id} | Título: {$doc->titulo}");
            $this->line("  Tesis: {$tesis}");
            $this->line("  Alumno: {$alumno} (ID: {$doc->alumno_id})");
            $this->line("  Tutor: {$tutor} (ID: {$doc->tutor_id})");
            $this->line("");
        }
    }
}
