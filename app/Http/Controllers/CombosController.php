<?php

namespace App\Http\Controllers;

use App\CombosHora;
use Illuminate\Http\Request;

class CombosController extends Controller
{
    public function listaCombos()
    {
        $combos = Combo::where('activo', '1' )->select('nombre', 'descripcion', 'beneficios')->get();
        return response()->json($combos, 200);
    }

    public function listaCombosHoras()
    {
        if( \Request::get('combo') )
        {
            $search = \Request::get('combo');
            $combos = CombosHora::where('combo', $search)->where('activo', '1')
                        ->select('id', 'combo', 'hora', 'inversion', 'descuento')->get();
            return response()->json($combos, 200);
        }
        else
        {
            return response()->json(['error' => 'Combo no especificado'], 401);
        }
    }

    
    public function getAll(){
        $combos  = Combo::select( 'nombre' )
        ->with(array('CombosHora'=>function($query){
            $query->select('*');
        }))
        ->get();
        return response()->json( $combos , 200);
    }
}