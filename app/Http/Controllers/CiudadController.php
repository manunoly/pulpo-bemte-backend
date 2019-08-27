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

    public function listaCiudadPais()
    {
        $ciudades = Ciudad::where('activa', '1' )->select('ciudad', 'pais', 'codigo')->get();
        return response()->json($ciudades, 200);
    }

    public function listaPaises()
    {
        $ciudades = Ciudad::where('activa', '1' )->select('pais', 'codigo')->distinct()->get();
        return response()->json($ciudades, 200);
    }
    
}