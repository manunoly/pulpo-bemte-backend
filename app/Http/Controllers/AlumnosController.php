<?php

namespace App\Http\Controllers;

use App\Tarea;
use App\Alumno;
use Validator;
use Illuminate\Http\Request;

class AlumnosController extends Controller
{
    public function calificarAlumno(Request $request)
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
        if ($id_tarea== 0 && $id_clase == 0)
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
                if ($id_calificado == $tarea->user_id && $id_usuario == $tarea->user_id_pro)
                {
                    $coment = isset($request['comment']) ? $request['comment'] : NULL;
                    $calif = array("califacion_alumno" => $request['calificacion'], 
                                    "comentario_alumno" => $coment);
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
        $tareas_est = Tarea::where('user_id', $id_calificado)->where('estado', 'Terminado')
                                ->where('califacion_alumno', '!=',NULL)->get();
        if ($tareas_est->count() > 0)
        {
            $calif_est = array("calificacion" => $tareas_est->sum('califacion_alumno')/$tareas_est->count());
            Alumno::where('user_id',$id_calificado)->update($calif_est);
        }
        return response()->json(['success' => 'Alumno calificado correctamente'], 200);
    }
}