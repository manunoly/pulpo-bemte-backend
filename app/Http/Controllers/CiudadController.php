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
        $sedes = Sede::where('activa', true)->select('nombre', 'ciudad')->get();
        return response()->json($sedes, 200);
    }

    public function listaSedesCiudad()
    {
        $search = \Request::get('ciudad');
        $sedes = Sede::where('activa', true)->where('ciudad', $search)->select('nombre')->get();
        return response()->json($sedes, 200);
    }

    public function listaCiudadPais()
    {
        $search = \Request::get('pais');
        if ($search == null)
            $ciudades = Ciudad::where('activa', '1' )->select('ciudad', 'pais', 'codigo')->get();
        else
            $ciudades = Ciudad::where('activa', '1' )->where('pais', $search)
                        ->select('ciudad', 'pais', 'codigo')->get();
        return response()->json($ciudades, 200);
    }

    public function listaPaises()
    {
        $ciudades = Ciudad::where('activa', '1' )->select('pais', 'codigo')->distinct()->get();
        return response()->json($ciudades, 200);
    }
    
}