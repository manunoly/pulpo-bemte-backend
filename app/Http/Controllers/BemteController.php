<?php

namespace App\Http\Controllers;

use App\Bemte;
use Illuminate\Http\Request;
use Validator;

class BemteController extends Controller
{
    public function terminos()
    {
        $respuesta = Bemte::select('terminosNombre', 'terminosDescripcion', 'terminosUrl')->first();
        return response()->json($respuesta, 200);
    }

    public function reglamento()
    {
        $respuesta = Bemte::select('reglamentoNombre', 'reglamentoDescripcion', 'reglamentoUrl')->first();
        return response()->json($respuesta, 200);
    }

    public function video()
    {
        $respuesta = Bemte::select('reglamentoNombre', 'videoDescripcion', 'videoUrl')->first();
        return response()->json($respuesta, 200);
    }
}