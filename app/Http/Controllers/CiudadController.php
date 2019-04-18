<?php

namespace App\Http\Controllers;

use App\Ciudad;
use App\Sede;
use Illuminate\Http\Request;

class CiudadController extends Controller
{
    public function listaCiudades()
    {
        $ciudades = Ciudad::where('activa', '1' )->select('ciudad')->get();
        return response()->json($ciudades, 200);
    }

    public function listaSedes()
    {
        $sedes = Sede::where('activa', '1' )->select('nombre', 'ciudad')->get();
        return response()->json($sedes, 200);
    }
}