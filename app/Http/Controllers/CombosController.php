<?php

namespace App\Http\Controllers;

use App\Combo;
use App\CombosHora;
use App\Alumno;
use App\AlumnoCompra;
use App\AlumnoBilletera;
use Illuminate\Http\Request;
use Validator;

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

    public function horasComboAlumno()
    {
        if( \Request::get('combo') )
        {
            if( \Request::get('user_id') )
            {
                $combo = \Request::get('combo');
                $alumno = \Request::get('user_id');
                $horas = AlumnoBilletera::where('combo', $combo)->where('user_id', $alumno)->select('horas')->get();
                return response()->json($horas, 200);
            }
            else
            {
                return response()->json(['error' => 'Alumno no especificado'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'Combo no especificado'], 401);
        }
    }
    
    public function compraComboAlumno(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'combo' => 'required',
            'valor' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        } 
        if (!is_numeric($request['valor']))
        {
            return response()->json(['error' => 'Valor inválido'], 401);
        }
        $combo = Combo::where('nombre', $request['combo'])->first();
        if ($combo == null)
        {
            return response()->json(['error' => 'No existe el Combo'], 401);
        }
        $usuario = Alumno::where('user_id', $request['user_id'])->first();
        if ($usuario != null)
        {
            if ($usuario->activo)
            {
                $compra = AlumnoCompra::create([
                    'user_id' => $request['user_id'],
                    'combo' => $request['combo'],
                    'valor' => $request['valor'],
                    'estado' => 'Solicitado'
                ]);
                if ($compra->id)
                {
                    return response()->json(['success' => 'Compra de Combo Solicitada'], 200);
                }
                else
                {
                    return response()->json(['error' => 'Ocurrió un error al registrar solicitud!'], 401);
                }
            }
            else
            {
                return response()->json(['error' => 'El Alumno no puede comprar un Combo'], 401); 
            }
        }
        else
        {
            return response()->json(['error' => 'No existe el Alumno'], 401);
        }
    }
}