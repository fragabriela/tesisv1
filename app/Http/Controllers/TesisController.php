<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TesisController extends Controller
{
    public function index()
    {
        //logica de negocio
        $nombre = "franci";
        return view('tesis', compact('nombre'));
    }
    public function indexv2()
    {
        //logica de negocio
        $nombre = "franci";
        return view('tesisv2', compact('nombre'));
    }
    
}
