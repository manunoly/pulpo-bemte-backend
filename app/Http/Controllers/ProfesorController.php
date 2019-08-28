<?php

namespace App\Http\Controllers;

use App\Tarea;
use App\Clase;
use App\Pago;
use App\Multa;
use App\Profesore;
use App\Alumno;
use App\User;
use App\TareaProfesor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Validator;
use App\Mail\Notificacion;
use App\Mail\NotificacionClases;
use App\Notificacione;
use App\NotificacionesPushFcm;

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
                    if ($request['calificacion'] > 0)
                        $calif = array("calificacion_profesor" => $request['calificacion'], 
                                    "comentario_profesor" => $coment);
                    else
                        $calif = array("comentario_profesor" => 'No Califica. '.$coment);
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
                    if ($request['calificacion'] > 0)
                        $calif = array("calificacion_profesor" => $request['calificacion'], 
                                    "comentario_profesor" => $coment);
                    else
                        $calif = array("comentario_profesor" => 'No Califica. '.$coment);
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
                        $userAlumno = User::where('id', $clase->user_id)->first();
                        $data['user_id_pro'] = $profe->user_id;
                        $data['hora_prof'] = $request['hora'];
                        $data['estado'] = 'Confirmado';
                        $data['aplica_prof'] = date("Y-m-d H:i:s");
                        $duracion = $clase->duracion + ($clase->personas - 1);
                        if ($duracion < 2)
                            $duracion = 2;
                        $estado  = 'Por favor, realizar el pago de su Clase.';
                        if ($clase->compra_id == 0)
                        {
                            //quitar las horas al alumno
                            $alumno = Alumno::where('user_id', $clase->user_id)->first();
                            $dataAlumno['billetera'] = $alumno->billetera - $duracion;
                            $actualizado = Alumno::where('user_id', $clase->user_id )->update( $dataAlumno );
                            if (!$actualizado)
                                return response()->json(['error' => 'No se pudo actualizar Billetera del Alumno'], 401);
                            $data['estado'] = 'Aceptado';
                            $estado = 'Muchas Gracias por el pago de su Clase.';
                            //pagar al profesor
                            $pagoProf = Pago::create([
                                        'user_id' => $profe->user_id,
                                        'tarea_id' => 0,
                                        'clase_id' => $clase->id,
                                        'valor' => ($clase->duracion * $profe->valor_clase) + ($clase->personas - 1),
                                        'horas' => $clase->duracion,
                                        'estado' => 'Solicitado'
                                        ]);
                            if (!$pagoProf->id)
                                return response()->json(['error' => 'No se pudo crear pago al Profesor'], 401);                            
                            $userProf = User::where('id', $clase->user_id_pro)->first();
                            try 
                            {
                                Mail::to($userAlumno->email)->send(new NotificacionClases($clase, $userAlumno->name, $userProf->name, 
                                                                env('EMPRESA'), true));
                                Mail::to($userProf->email)->send(new NotificacionClases($clase, $userAlumno->name, $userProf->name, 
                                                                env('EMPRESA'), false));
                            }
                            catch (Exception $e) 
                            {
                            } 
                        }

                        $actualizado = Clase::where('id', $clase->id )->update( $data );
                        if ($actualizado)
                        {
                            //enviar notificacion al profesor o alumno
                            $dateTime = date("Y-m-d H:i:s");
                            $errorNotif = 'OK';
                            $titulo = 'Clase Confirmada';
                            $texto = 'La Clase '.$clase->id.' ha sido confirmada por el profesor '
                                        .$profe->nombres.' '.$profe->apellidos.', '.$dateTime;
                            try 
                            {
                                if ($userAlumno != null && $userAlumno->token != null)
                                {
                                    $notificacionEnviar['to'] = $userAlumno->token;
                                    $notificacionEnviar['title'] = $titulo;
                                    $notificacionEnviar['body'] = $texto;
                                    $notificacionEnviar['priority'] = 'normal';
                                    $pushClass = new NotificacionesPushFcm();
                                    $pushClass->enviarNotificacion($notificacionEnviar);
                                }
                                else
                                    $errorNotif = 'No se pudo encontrar el Token del Usuario a notificar';
                            }
                            catch (Exception $e) 
                            {
                                $errorNotif = $e->getMessage();
                            }
                            $notifBD = Notificacione::create([
                                'user_id' => $clase->user_id,
                                'notificacion' => $titulo.'|'.$texto,
                                'estado' => $errorNotif
                                ]);
                            try 
                            {
                                Mail::to($userAlumno->email)->send(new Notificacion(
                                        $userAlumno->name, $texto, '', $estado, env('EMPRESA')));
                            }
                            catch (Exception $e) { }

                            return response()->json(['success' => 'Clase Solicitada'], 200);
                        }
                        else
                            return response()->json(['error' => 'Solicitud para la Clase no se pudo actualizar'], 401);
                    }
                    else
                        return response()->json(['error' => 'El profesor no se encuentra disponible para la clase'], 401);
                }
                else
                    return response()->json(['error' => 'No se encontró al Profesor para aplicar'], 401);
            }
            else
                return response()->json(['error' => 'Clase no disponible'], 401);
        }
        else
            return response()->json(['error' => 'No se encontró la Clase para aplicar'], 401);
    }

    public function actualizaCuenta(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'banco' => 'required',
            'numero' => 'required',
            'tipo' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }  
        $id_usuario = $request['user_id'];
        $profesor = Profesore::where('user_id', $id_usuario)->select('*')->first();
        if ($profesor)
        {
            $data['cuenta'] = $request['numero'];
            $data['banco'] = $request['banco'];
            $data['tipo_cuenta'] = $request['tipo'];
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

    public function cuentaProfesor()
    {
        if( \Request::get('user_id') )
        {
            $search = \Request::get('user_id');
            $clases = Pago::join('clases', 'pagos.clase_id', '=', 'clases.id')
                        ->where('pagos.user_id', $search)
                        ->where('clases.user_id_pro', $search)
                        ->select('clases.id', 'clases.materia', 'clases.personas', 
                        'clases.duracion', 'clases.fecha', 'pagos.horas',
                        'pagos.valor', 'pagos.created_at', 'pagos.estado')->get();
            $tareas = Pago::join('tareas', 'pagos.tarea_id', '=', 'tareas.id')
                        ->where('pagos.user_id', $search)
                        ->where('tareas.user_id_pro', $search)
                        ->select('tareas.id', 'tareas.materia', 'tareas.tiempo_estimado',  'pagos.horas',
                        'tareas.fecha_entrega', 'pagos.valor', 'pagos.created_at', 'pagos.estado')->get();
            $multas = Multa::where('multas.user_id', $search)
                        ->select('multas.clase_id', 'multas.tarea_id', 'multas.valor', 
                        'multas.comentario', 'multas.created_at', 'multas.estado')->get();
            $respuesta['total'] = $clases->where('estado', 'Aprobado')->sum('valor') 
                                    + $tareas->where('estado', 'Aprobado')->sum('valor') 
                                    - $multas->where('estado', 'Aprobado')->sum('valor');
            $respuesta['pendiente'] = $clases->where('estado', 'Solicitado')->sum('valor') 
                                    + $tareas->where('estado', 'Solicitado')->sum('valor') 
                                    - $multas->where('estado', 'Solicitado')->sum('valor');
            $respuesta['clases'] = $clases;
            $respuesta['tareas'] = $tareas;
            $respuesta['multas'] = $multas;
            return response()->json($respuesta, 200);
        }
        else
        {
            return response()->json(['error' => 'Usuario no especificado'], 401);
        }
    }
}