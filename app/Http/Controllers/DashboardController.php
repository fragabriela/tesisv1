<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Carrera;
use App\Models\Tesis;
use App\Models\Tutor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Show the dashboard with statistics
     */
    public function index()
    {
        // Obtain counts
        $totalAlumnos = Alumno::count();
        $totalCarreras = Carrera::count();
        $totalTutores = Tutor::count();
        $totalTesis = Tesis::count();
        
        // Tesis by status
        $tesisByStatus = Tesis::select('estado', DB::raw('count(*) as total'))
            ->groupBy('estado')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->estado => $item->total];
            });
        
        // Alumnos by carrera
        $alumnosByCarrera = Carrera::select('carreras.nombre', DB::raw('count(alumnos.id) as total'))
            ->leftJoin('alumnos', 'carreras.id', '=', 'alumnos.id_carrera')
            ->groupBy('carreras.id', 'carreras.nombre')
            ->get();
        
        // Recent tesis
        $recentTesis = Tesis::with(['alumno', 'tutor'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();
        
        // Tutores with most tesis
        $topTutores = Tutor::select('tutores.id', 'tutores.nombre', 'tutores.apellido', DB::raw('count(tesis.id) as total_tesis'))
            ->leftJoin('tesis', 'tutores.id', '=', 'tesis.tutor_id')
            ->groupBy('tutores.id', 'tutores.nombre', 'tutores.apellido')
            ->orderBy('total_tesis', 'desc')
            ->take(5)
            ->get();
            
        return view('dashboard', compact(
            'totalAlumnos', 
            'totalCarreras', 
            'totalTutores', 
            'totalTesis',
            'tesisByStatus',
            'alumnosByCarrera',
            'recentTesis',
            'topTutores'
        ));
    }
}
