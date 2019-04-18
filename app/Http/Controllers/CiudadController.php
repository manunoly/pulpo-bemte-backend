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


    public function index()
    {
        //return Auth::user()->id; obtener id del usuario que ha iniciado la sesion
        $ciudades = Ciudad::select( 'ciudad');
        $ciudades = $ciudades->where('activa', 1);

        if( \Request::get('nombre') ){
            $search = \Request::get('nombre');
            $ciudades = $ciudades->where('ciudad','LIKE', '%'.$search.'%' );
        }

        $ciudades = $ciudades->get();
        
        return response()->json( $ciudades  , 200);
    }


    public function getAll(){
        $ciudades  = Ciudad::select( 'ciudad' )->get();
        return response()->json( $ciudades , 200);
    }
}