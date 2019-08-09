<?php

namespace App\Http\Controllers;

use App\Tarea;
use App\Clase;
use App\Profesore;
use App\TareaProfesor;
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
        
        if (!is_numeric($request['calificacion']) || $request['calificacion'] > 5 || $request['calificacion'] < 0)
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
        $coment = isset($request['comment']) ? $request['comment'] : NULL;
        if ($id_tarea != 0)
        {
            $tarea = Tarea::where('id', $id_tarea)->first();
            if ($tarea != null)
            {
                if ($id_usuario == $tarea->user_id && $id_calificado == $tarea->user_id_pro)
                {
                    $calif = array("calificacion_profesor" => $request['calificacion'], 
                                    "comentario_profesor" => $coment, "estado" => "Calificado");
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
        if ($id_clase != 0)
        {
            $clase = Clase::where('id', $id_clase)->first();
            if ($clase != null)
            {
                if ($id_usuario == $clase->user_id && $id_calificado == $clase->user_id_pro)
                {
                    $calif = array("calificacion_profesor" => $request['calificacion'], 
                                    "comentario_profesor" => $coment, "estado" => "Calificado");
                    Clase::where('id',$id_clase)->update($calif);
                }
                else
                {
                    return response()->json(['error' => 'Los usuarios no coinciden con la Clase especificada'], 401);
                }
            }
            else
            {
                return response()->json(['error' => 'No se encontró la Clase a calificar'], 401);
            }
        }
        $tareas_prof = Tarea::where('user_id_pro', $id_calificado)->where('estado', 'Terminado')
                        ->where('calificacion_profesor', '!=',NULL)->get();
        $clases_prof = Clase::where('user_id_pro', $id_calificado)->where('estado', 'Terminado')
                        ->where('calificacion_profesor', '!=',NULL)->get();
        if ($tareas_prof->count() + $clases_prof->count()  > 10)
        {
            $calif_prof = array("calificacion" =>
                    ($tareas_prof->sum('calificacion_profesor') + $clases_prof->sum('calificacion_profesor'))
                    / ($tareas_prof->count() + $clases_prof->count()));
            Profesore::where('user_id',$id_calificado)->update($calif_prof);
        }
        return response()->json(['success' => 'Profesor calificado correctamente'], 200);
    }

    public function aplicarTarea(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'tarea_id' => 'required',
            'tiempo' => 'required',
            'inversion' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        } 
        
        if (!is_numeric($request['tiempo']) || $request['tiempo'] <= 0)
        {
            return response()->json(['error' => 'Especifique un tiempo para la Tarea'], 401);
        }
        if (!is_numeric($request['inversion']) || $request['inversion'] <= 0)
        {
            return response()->json(['error' => 'Especifique una inversión para la Tarea'], 401);
        }

        $tarea = Tarea::where('id', $request['tarea_id'])->first();
        if ($tarea != null)
        {
            if ($tarea->estado == 'Solicitado')
            {
                $profe = Profesore::where('user_id', $request['user_id'])->first();
                if ($profe != null)
                {
                    if ($profe->activo && $profe->disponible && $profe->tareas)
                    {
                        $solicitud = TareaProfesor::where('tarea_id', $request['tarea_id'])->where('user_id', $request['user_id'])->first();
                        if ($solicitud == null)
                        {
                            if ( 10 > TareaProfesor::where('tarea_id', $request['tarea_id'])->where('estado', 'Solicitada')->count())
                            {
                                $aplica = TareaProfesor::create([
                                    'user_id' => $request['user_id'],
                                    'tarea_id' => $request['tarea_id'],
                                    'inversion' => $request['inversion'],
                                    'tiempo' => $request['tiempo'],
                                    'estado' => 'Solicitada'
                                ]);
                                if ($aplica->id)
                                {
                                    return response()->json(['success' => 'Tarea Solicitada'], 200);
                                }
                                else
                                {
                                    return response()->json(['error' => 'Ocurrió un error al registrar solicitud!'], 401);
                                }
                            }
                            else
                            {
                                return response()->json(['error' => 'Solicitudes completas para la Tarea'], 401);
                            }
                        }
                        else
                        {
                            $data['inversion'] = $request['inversion'];
                            $data['tiempo'] = $request['tiempo'];
                            $data['estado'] = 'Solicitada';
                            $actualizado = TareaProfesor::where('id', $solicitud->id )->update( $data );
                            if($actualizado)
                            {
                                return response()->json(['success' => 'Tarea Solicitada'], 200);
                            }
                            else
                            {
                                return response()->json(['error' => 'Solicitud para la Tarea no se pudo actualizar'], 401);
                            }
                        }
                    }
                    else
                    {
                        return response()->json(['error' => 'El profesor no se encuentra disponible para la tarea'], 401);
                    }
                }
                else
                {
                    return response()->json(['error' => 'No se encontró al Profesor para aplicar'], 401);
                }
            }
            else
            {
                return response()->json(['error' => 'Tarea no disponible'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'No se encontró la Tarea para aplicar'], 401);
        }
    }
    
    public function aplicarClase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'clase_id' => 'required',
            'hora' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        } 

        $clase = Clase::where('id', $request['clase_id'])->first();
        if ($clase != null)
        {
            if ($clase->estado == 'Solicitado')
            {
                $profe = Profesore::where('user_id', $request['user_id'])->first();
                if ($profe != null)
                {
                    if ($profe->activo && $profe->disponible && $profe->clases)
                    {
                        $data['user_id_pro'] = $profe->user_id;
                        $data['hora_prof'] = $request['hora'];
                        $data['estado'] = 'Confirmado';
                        $actualizado = Clase::where('id', $clase->id )->update( $data );
                        if($actualizado)
                        {
                            return response()->json(['success' => 'Clase Solicitada'], 200);
                        }
                        else
                        {
                            return response()->json(['error' => 'Solicitud para la Clase no se pudo actualizar'], 401);
                        }
                    }
                    else
                    {
                        return response()->json(['error' => 'El profesor no se encuentra disponible para la clase'], 401);
                    }
                }
                else
                {
                    return response()->json(['error' => 'No se encontró al Profesor para aplicar'], 401);
                }
            }
            else
            {
                return response()->json(['error' => 'Clase no disponible'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'No se encontró la Clase para aplicar'], 401);
        }
    }
}