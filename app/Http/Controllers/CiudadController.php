<?php

namespace App\Http\Controllers;

use App\Ciudad;
use Illuminate\Http\Request;

class CiudadController extends Controller
{
    public function listaCiudades()
    {
        $ciudades = Ciudad::where('activa', '1' )->select('ciudad')->get();
        return response()->json($ciudades, 200);
    }
}