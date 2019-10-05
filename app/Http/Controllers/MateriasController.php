<?php

namespace App\Http\Controllers;

use App\Materia;
use Illuminate\Http\Request;

class MateriasController extends Controller
{
    public function listaMaterias()
    {
        $materia = Materia::where('activa', '1' )->select('nombre', 'icono')->get();
        return response()->json($materia, 200);
    }
}