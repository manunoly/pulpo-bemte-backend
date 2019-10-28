<?php

namespace App\Http\Controllers;

use App\Bemte;
use Illuminate\Http\Request;
use Validator;

use App\Tarea;
use App\Clase;

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


    public function eliminar(Request $request)
    {
        $clase = Clase::where('id', $request['clase_id'])->first();
        if ($clase != null)
        {
            if ($clase->estado == 'Solicitado' || $clase->estado == 'Sin_Horas')
            {
                $clase->delete();
                return response()->json(['success' => 'Clase Eliminada'], 200);
            }
            else
                return response()->json(['error' => 'Clase no permite Eliminar'], 401);
        }
        $tarea = Tarea::where('id', $request['tarea_id'])->first();
        if ($tarea != null)
        {
            if ($tarea->estado == 'Solicitado' || $tarea->estado == 'Sin_Horas')
            {
                $tarea->delete();
                return response()->json(['success' => 'Tarea Eliminada'], 200);
            }
            else
                return response()->json(['error' => 'Tarea no permite Eliminar'], 401);
        }
        return response()->json(['error' => 'No se encontró la Clase o Tarea'], 401);
    }
}