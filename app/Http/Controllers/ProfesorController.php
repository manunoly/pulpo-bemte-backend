<?php

namespace App\Http\Controllers;

use App\Profesore;
use Illuminate\Http\Request;
use Validator;

class ProfesorController extends Controller
{
    public function actualizarProyectos(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'proyectos' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        } 
        if ($request['proyectos'] != "0" && $request['proyectos'] != "1")
        {
            return response()->json(['error' => 'Opción Proyectos incorrecta'], 401);
        }

        $id_usuario = $request['user_id'];
        $profesor = Profesore::where('user_id', $id_usuario)->select('*')->first();
        if ($profesor)
        {
            $data['proyectos'] = $request['proyectos'] == "1" ? true : false;
            $actualizado = Profesore::where('user_id', $id_usuario )->update( $data );
            if( $actualizado )
            {
                return response()->json(['success' => 'Datos actualizados correctamente'], 200);
            }
            else
            {
                return response()->json(['error' => 'Ocurrió un error al actualizar.'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'No se encontró el usuario'], 401);
        }
    }
    
    public function actualizarClases(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'clases' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }   
        if ($request['clases'] != "0" && $request['clases'] != "1")
        {
            return response()->json(['error' => 'Opción Clases incorrecta'], 401);
        }

        $id_usuario = $request['user_id'];
        $profesor = Profesore::where('user_id', $id_usuario)->select('*')->first();
        if ($profesor)
        {
            $data['clases'] = $request['clases'] == "1" ? true : false;
            $actualizado = Profesore::where('user_id', $id_usuario )->update( $data );
            if( $actualizado )
            {
                return response()->json(['success' => 'Datos actualizados correctamente'], 200);
            }
            else
            {
                return response()->json(['error' => 'Ocurrió un error al actualizar.'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'No se encontró el usuario'], 401);
        }
    }
    
    
    public function actualizarDisponible(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'disponible' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }  
        if ($request['disponible'] != "0" && $request['disponible'] != "1")
        {
            return response()->json(['error' => 'Opción Disponible incorrecta'], 401);
        }

        $id_usuario = $request['user_id'];
        $profesor = Profesore::where('user_id', $id_usuario)->select('*')->first();
        if ($profesor)
        {
            $data['disponible'] = $request['disponible'] == "1" ? true : false;
            $actualizado = Profesore::where('user_id', $id_usuario )->update( $data );
            if( $actualizado )
            {
                return response()->json(['success' => 'Datos actualizados correctamente'], 200);
            }
            else
            {
                return response()->json(['error' => 'Ocurrió un error al actualizar.'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'No se encontró el usuario'], 401);
        }
    }
}