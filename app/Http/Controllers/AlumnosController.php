<?php

namespace App\Http\Controllers;

use App\User;
use App\Tarea;
use App\Clase;
use App\Alumno;
use App\Profesore;
use App\Formulario;
use App\AlumnoBilletera;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\NotificacionClases;
use App\Mail\NotificacionTareas;

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
        
        if (!is_numeric($request['calificacion']) || $request['calificacion'] > 5 || $request['calificacion'] < 0)
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
        $coment = isset($request['comment']) ? $request['comment'] : NULL;
        if ($id_tarea != 0)
        {
            $tarea = Tarea::where('id', $id_tarea)->first();
            if ($tarea != null)
            {
                if ($id_calificado == $tarea->user_id && $id_usuario == $tarea->user_id_pro)
                {
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
        if ($id_clase != 0)
        {
            $clase = Clase::where('id', $id_clase)->first();
            if ($clase != null)
            {
                if ($id_calificado == $clase->user_id && $id_usuario == $clase->user_id_pro)
                {
                    $calif = array("califacion_alumno" => $request['calificacion'], 
                                    "comentario_alumno" => $coment);
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
        $tareas_est = Tarea::where('user_id', $id_calificado)->where('estado', 'Terminado')
                                ->where('califacion_alumno', '!=',NULL)->get();
        $clases_est = Clase::where('user_id', $id_calificado)->where('estado', 'Terminado')
                                ->where('califacion_alumno', '!=',NULL)->get();
        if ($tareas_est->count() + $clases_est->count() > 10)
        {
            $calif_est = array("calificacion" => 
                ($tareas_est->sum('califacion_alumno') + $clases_est->sum('califacion_alumno'))
                / ($tareas_est->count() + $clases_est->count()));
            Alumno::where('user_id',$id_calificado)->update($calif_est);
        }
        return response()->json(['success' => 'Alumno calificado correctamente'], 200);
    }
    
    public function pagarConCombo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'tarea_id' => 'required|numeric',
            'clase_id' => 'required|numeric',
            'combo' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }
        $tarea = null;
        if ($request['tarea_id'] > 0)
        {
            if ($request['clase_id'] > 0)
            {
                return response()->json(['error' => 'Especifique una sola opción'], 401);
            }
            $tarea = Tarea::where('id', $request['tarea_id'])->first();
            if ($tarea == null)
            {
                return response()->json(['error' => 'No existe la Tarea'], 401);
            }
            else if ($tarea->estado != 'Confirmado')
            {
                return response()->json(['error' => 'La Tarea no se encuentra Confirmada para pagar'], 401);
            }
            else if ($tarea->user_id != $request['user_id'])
            {
                return response()->json(['error' => 'El usuario no tiene relación con la Tarea'], 401);
            }
            else if ($tarea->user_canc != null)
            {
                return response()->json(['error' => 'La Tarea ha sido cancelada, no se puede pagar'], 401);
            }
        }
        $clase = null;
        if ($request['clase_id'] > 0)
        {
            if ($request['tarea_id'] > 0)
            {
                return response()->json(['error' => 'Especifique una sola opción'], 401);
            }
            $clase = Clase::where('id', $request['clase_id'])->first();
            if ($clase == null)
            {
                return response()->json(['error' => 'No existe la Clase'], 401);
            }
            else if ($clase->estado != 'Confirmado')
            {
                return response()->json(['error' => 'La Clase no se encuentra Confirmada para pagar'], 401);
            }
            else if ($clase->user_id != $request['user_id'])
            {
                return response()->json(['error' => 'El usuario no tiene relación con la Clase'], 401);
            }
            else if ($clase->user_canc != null)
            {
                return response()->json(['error' => 'La Clase ha sido cancelada, no se puede pagar'], 401);
            }
        }
        $combo = AlumnoBilletera::where('user_id', $request['user_id'])
                                    ->where('combo', $request['combo'])->first();
        if ($combo == null)
        {
            $listaCombos = AlumnoBilletera::where('user_id', $request['user_id'])->orderBy('horas', 'desc')->get();
        }
        $duracion = 0;
        if ($tarea != null)
        {
            if ($tarea != null && 
                (($combo != null && $combo->horas < $tarea->tiempo_estimado) ||
                ($combo == null && $listaCombos->sum('horas') < $tarea->tiempo_estimado)))
            {
                return response()->json(['error' => 'Combos sin horas para pagar'], 401);
            }
            $duracion = $tarea->tiempo_estimado;
        }
        if ($clase != null)
        {
            $duracion = $clase->duracion + ($clase->personas - 1);
            if ($duracion < 2)
                $duracion = 2;
            if ($clase != null && 
                (($combo != null && $combo->horas < $duracion) ||
                ($combo == null && $listaCombos->sum('horas') < $duracion)))
            {
                return response()->json(['error' => 'Combos sin horas para pagar'], 401);
            }
        }
        $user = Alumno::where('user_id', $request['user_id'])->first();
        if ($user != null)
        {
            if ($user->activo)
            {
                $data['estado'] = 'Aceptado';
                if ($tarea != null)
                {
                    $profeTarea = Profesore::where('user_id', $tarea->user_id_pro)->first();
                    $pagoProf = Pago::create([
                            'user_id' => $tarea->user_id_pro,
                            'tarea_id' => $tarea->id,
                            'clase_id' => 0,
                            'valor' => $duracion * $profeTarea->valor_tarea,
                            'horas' => $duracion,
                            'horas' => 'Solicitado'
                            ]);
                    if (!$pagoProf->id)
                    {
                        return response()->json(['error' => 'Ocurrió un error al crear Pago al Profesor'], 401);
                    }
                    $actTarea = Tarea::where('id', $tarea->id )->update( $data );
                    if(!$actTarea )
                    {
                        return response()->json(['error' => 'Ocurrió un error al actualizar la Tarea.'], 401);
                    }
                    try 
                    {
                        $userAlum = User::where('id', $tarea->user_id)->first();
                        $userProf = User::where('id', $tarea->user_id_pro)->first();
                        Mail::to($userAlum->email)->send(new NotificacionTareas($tarea, $userAlum->name, $userProf->name, 
                                                        env('EMPRESA'), true));
                        Mail::to($userProf->email)->send(new NotificacionTareas($tarea, $userAlum->name, $userProf->name, 
                                                        env('EMPRESA'), false));
                    }
                    catch (Exception $e) 
                    {
                        return response()->json(['success' => 'No se ha podido enviar el correo',
                                    'detalle' => $e->getMessage()], 200);
                    }
                }
                if ($clase != null)
                {
                    $profeClase = Profesore::where('user_id', $clase->user_id_pro)->first();
                    $pagoProf = Pago::create([
                            'user_id' => $clase->user_id_pro,
                            'clase_id' => $clase->id,
                            'tarea_id' => 0,
                            'valor' => $duracion * $profeClase->valor_clase,
                            'horas' => $clase->$duracion,
                            'horas' => 'Solicitado'
                            ]);
                    if (!$pagoProf->id)
                    {
                        return response()->json(['error' => 'Ocurrió un error al crear Pago al Profesor'], 401);
                    }
                    $actClase = Clase::where('id', $clase->id )->update( $data );
                    if(!$actClase )
                    {
                        return response()->json(['error' => 'Ocurrió un error al actualizar la Clase.'], 401);
                    }
                    try 
                    {
                        $userAlum = User::where('id', $clase->user_id)->first();
                        $userProf = User::where('id', $clase->user_id_pro)->first();
                        Mail::to($userAlum->email)->send(new NotificacionClases($clase, $userAlum->name, $userProf->name, 
                                                        env('EMPRESA'), true));
                        Mail::to($userProf->email)->send(new NotificacionClases($clase, $userAlum->name, $userProf->name, 
                                                        env('EMPRESA'), false));
                    }
                    catch (Exception $e) 
                    {
                        return response()->json(['success' => 'No se ha podido enviar el correo',
                                    'detalle' => $e->getMessage()], 200);
                    }
                }
                if ($combo != null)
                {
                    $dataCombo['horas'] = $combo->horas - $duracion;
                    $actCombo = AlumnoBilletera::where('id', $combo->id )->update( $dataCombo );
                    if(!$actCombo )
                    {
                        return response()->json(['error' => 'Ocurrió un error al actualizar pago.'], 401);
                    }
                }
                else
                {
                    foreach($listaCombos as $item)
                    {
                        $restar = $duracion;
                        if ($item->horas < $duracion)
                        {
                            $restar = $item->horas;
                        }
                        $dataCombo['horas'] = $combo->horas - $restar;
                        $actCombo = AlumnoBilletera::where('id', $combo->id )->update( $dataCombo );
                        if(!$actCombo )
                        {
                            return response()->json(['error' => 'Ocurrió un error al actualizar pago.'], 401);
                        }
                        $duracion = $duracion - $restar;
                        if ($duracion == 0)
                        {
                            break;
                        }
                    }
                }
                return response()->json(['success' => 'Pago con Combo exitoso'], 200);
            }
            else
            {
                return response()->json(['error' => 'El alumno no se encuentra activo'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'No se encontró al Alumno para subir el pago'], 401);
        }
    }
    
    public function aplicarProfesor(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'cedula' => 'required',
            'clases' => 'required',
            'tareas' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        } 
        $cedula = isset($request['cedula']) ? trim($request['cedula']) : NULL;
        if (strlen($cedula) != 10)
        {
            return response()->json(['error' => 'Cédula inválida'], 401);
        }
        if ($request['clases'] != 0 && $request['clases'] != 1)
        {
            return response()->json(['error' => 'Disponibilidad para las Clases Inválida'], 401);
        }
        if ($request['tareas'] != 0 && $request['tareas'] != 1)
        {
            return response()->json(['error' => 'Disponibilidad para las Tareas Inválida'], 401);
        }
        if ($request['clases'] == 0 && $request['tareas'] == 0)
        {
            return response()->json(['error' => 'Para solicitar ser Profesor debe tener al menos una Disponibilidad activa (Clases o Tareas)'], 401);
        }
        $alumno = Alumno::where('user_id', $request['user_id'])->first();
        if ($alumno == null)
        {
            return response()->json(['error' => 'El usuario no es Alumno para solicitar ser Profesor'], 401);
        }
        $solicitud = Formulario::where('user_id', $request['user_id'])->where('estado', 'Solicitado')->first();
        if ($solicitud != null)
        {
            return response()->json(['error' => 'El Alumno ya tiene una solicitud en Proceso'], 401);
        }

        $data['ser_profesor'] = true;
        $act = Alumno::where('user_id', $request['user_id'])->update($data);
        if(!$act )
        {
            return response()->json(['error' => 'Ocurrió un error al actualizar el Alumno.'], 401);
        }
        $hojaVida  = isset($request['hojaVida ']) ? trim($request['hojaVida ']) : NULL;
        $titulo = isset($request['titulo']) ? trim($request['titulo']) : NULL;
        $new = Formulario::create([
            'user_id' => $request['user_id'],
            'cedula' => $cedula,
            'clases' => $request['clases'] == 1 ? true : false,
            'tareas' => $request['tareas'] == 1 ? true : false,
            'hoja_vida' => $hojaVida ,
            'titulo' => $titulo,
            'estado' => 'Solicitada'
        ]);
        if ($new == null || !$new->id)
        {
            return response()->json(['error' => 'Ocurrió un error al registrar solicitud!'], 401);
        }
        return response()->json(['success' => 'Solicitud ser Profesor realizada'], 200);
    }
}