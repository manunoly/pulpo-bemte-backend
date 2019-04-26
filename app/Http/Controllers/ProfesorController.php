<?php

namespace App\Http\Controllers;

use App\Tarea;
use App\Profesore;
use Illuminate\Http\Request;
use Validator;

class ProfesorController extends Controller
{
    public function actualizarTareas(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'tareas' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        } 
        if ($request['tareas'] != "0" && $request['tareas'] != "1")
        {
            return response()->json(['error' => 'Opción Tareas incorrecta'], 401);
        }

        $id_usuario = $request['user_id'];
        $profesor = Profesore::where('user_id', $id_usuario)->select('*')->first();
        if ($profesor)
        {
            $data['tareas'] = $request['tareas'] == "1" ? true : false;
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
    
    public function calificarProfesor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'user_id_calif' => 'required',
            'calificacion' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        } 
        
        if ($request['calificacion'] > 5 || $request['calificacion'] < 0)
        {
            return response()->json(['error' => 'La calificación debe estar en el rango de 0 a 5'], 401);
        }

        $id_tarea = isset($request['tarea_id']) ? $request['tarea_id'] : 0;
        $id_clase = isset($request['clase_id']) ? $request['clase_id'] : 0;
        if ($id_tarea == 0 && $id_clase == 0)
        {
            return response()->json(['error' => 'Debe especificar la tarea o clase que se califica'], 401);
        }

        $id_usuario = $request['user_id'];
        $id_calificado = $request['user_id_calif'];
        if ($id_usuario == $id_calificado)
        {
            return response()->json(['error' => 'No se puede calificar al mismo usuario'], 401);
        }
        if ($id_tarea != 0)
        {
            $tarea = Tarea::where('id', $id_tarea)->first();
            if ($tarea != null)
            {
                if ($id_usuario == $tarea->user_id && $id_calificado == $tarea->user_id_pro)
                {
                    $coment = isset($request['comment']) ? $request['comment'] : NULL;
                    $calif = array("calificacion_profesor" => $request['calificacion'], 
                                    "comentario_profesor" => $coment);
                    Tarea::where('id',$id_tarea)->update($calif);
                }
                else
                {
                    return response()->json(['error' => 'Los usuarios no coinciden con la tarea especificada'], 401);
                }
            }
            else
            {
                return response()->json(['error' => 'No se encontró la tarea a calificar'], 401);
            }
        }
        $tareas_prof = Tarea::where('user_id_pro', $id_calificado)->where('estado', 'Terminado')
                        ->where('calificacion_profesor', '!=',NULL)->get();
        if ($tareas_prof->count() > 0)
        {
            $calif_prof = array("calificacion" => $tareas_prof->sum('calificacion_profesor')/$tareas_prof->count());
            Profesore::where('user_id',$id_calificado)->update($calif_prof);
        }
        return response()->json(['success' => 'Profesor calificado correctamente'], 200);
    }
}