<?php

namespace App\Http\Controllers;

use App\User;
use App\Tarea;
use App\Materia;
use App\Alumno;
use App\Profesore;
use App\TareaEjercicio;
use App\Notificacione;
use App\NotificacionesPushFcm;
use Illuminate\Http\Request;
use Validator;
use Hash;

/* ESTADOS TAREA
Solicitado
Confirmado
Aceptado (pagado)
Terminado
Calificado
Cancelado
Sin_Profesor
Sin_Pago
Pago_Rechazado
Confirmando_Pago
*/
class TareasController extends Controller
{
    public function solicitarTarea(Request $request)
    {
        if ($request) 
        {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'materia' => 'required',
                'tema' => 'required',
                'fecha_entrega' => 'required',
                'hora_inicio' => 'required',
                'hora_fin' => 'required',
                'descripcion' => 'required',
                'formato_entrega' => 'required'
            ]);
            if ($validator->fails()) 
            {
                return response()->json(['error' => $validator->errors()], 406);
            }
            
            $user = User::where('id', '=', $request['user_id'] )->first();
            if ($user == null) 
            {
                return response()->json([ 'error' => 'El usuario no existe!'], 401);
            }
            $alumno = Alumno::where('user_id', '=', $request['user_id'] )->first();
            if ($alumno == null) 
            {
                return response()->json([ 'error' => 'El usuario no puede solicitar Tarea'], 401);
            }
            $materia = Materia::where('nombre', '=', $request['materia'] )->first();
            if ($materia == null)
            {
                return response()->json(['error' => 'La Materia enviada no es válida'], 401);
            }

            $archivo = isset($request['archivo']) ? $request['archivo'] : NULL;
            $tarea = Tarea::create([
                'user_id' => $request['user_id'],
                'materia' => $request['materia'],
                'tema' => $request['tema'],
                'fecha_entrega' => $request['fecha_entrega'],
                'hora_inicio' => $request['hora_inicio'],
                'hora_fin' => $request['hora_fin'],
                'descripcion' => $request['descripcion'],
                'formato_entrega' => $request['formato_entrega'],
                'estado' => 'Solicitado',
                'activa' => true,
                'archivo' => $archivo
            ]);

            if( $tarea->id)
            {
                //lanzar notificaciones profesores
                $profesores = Profesore::join('profesor_materia', 'profesor_materia.user_id', '=', 'profesores.user_id')
                                    ->join('users', 'users.id', '=', 'profesores.user_id')
                                    ->where('profesores.activo', true)
                                    ->where('profesores.tareas', true)
                                    ->where('profesores.disponible', true)
                                    ->where('profesor_materia.activa', true)
                                    ->where('profesor_materia.materia', $tarea->materia)
                                    ->select('profesores.correo', 'users.token', 'users.sistema', 'users.id')
                                    ->get();
                
                $titulo = 'Solicitud de Tarea';
                $texto = 'Ha sido solicitada la Tarea '.$tarea->id.' de '.$tarea->materia
                        .' ('.$tarea->tema.'), para el '.$tarea->fecha_entrega.' de '
                        .$tarea->hora_inicio.' a '.$tarea->hora_fin
                        .', en '.$tarea->formato_entrega.', por '.$user->name.', '.$dateTime;
                foreach($profesores as $solicitar)
                {
                    $errorNotif = 'OK';
                    $dateTime = date("Y-m-d H:i:s");
                    try 
                    {
                        if ($solicitar != null && $solicitar->token != null)
                        {
                            $notificacionEnviar['to'] = $solicitar->token;
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
                        'user_id' => $solicitar->id,
                        'notificacion' => $titulo.'|'.$texto,
                        'estado' => $errorNotif
                        ]);
                }

                return response()->json(['success'=> 'Su tarea ha sido solicitada. Por favor espera que validemos su información'], 200);
            }
            else
            {
                return response()->json(['error' => 'Lo sentimos, ocurrió un error al registrar!'], 401);
            }
        } 
        else 
        {
            return response()->json(['error' => 'Formulario vacío!'], 401);
        }
    }

    public function tareaActiva()
    {
        if( \Request::get('user_id') )
        {
            $search = \Request::get('user_id');
            $tarea = Tarea::where('user_id', $search)
                        ->where('activa', true)->first();
            if (isset($tarea->user_id_pro) && $tarea->user_id_pro > 0)
            {
                $prof = User::where('id', $tarea->user_id_pro)->first();
                $tarea['profesor'] = $prof->name;
            }
            return response()->json($tarea, 200);
        }
        else
        {
            return response()->json(['error' => 'Usuario no especificado'], 401);
        }
    }

    public function tareasDisponibles()
    {
        if( \Request::get('user_id') )
        {
            $search = \Request::get('user_id');
            $tarea = Profesore::join('profesor_materia', 'profesor_materia.user_id', '=', 'profesores.user_id')
                        ->join('tareas', 'profesor_materia.materia', '=', 'tareas.materia')
                        ->join('alumnos', 'alumnos.user_id', '=', 'tareas.user_id')
                        ->where('tareas.estado', 'Solicitado')
                        ->where('profesores.user_id', $search)
                        ->where('profesores.activo', true)
                        ->where('profesor_materia.activa', true)
                        ->where('profesores.tareas', true)
                        ->select('tareas.id', 'tareas.user_id', 'tareas.materia', 'tareas.tema', 
                        'tareas.fecha_entrega', 'tareas.hora_inicio', 'tareas.hora_fin', 
                        'tareas.descripcion', 'tareas.formato_entrega', 'tareas.archivo')
                        ->orderBy('tareas.id', 'desc')->get();
            return response()->json($tarea, 200);
        }
        else
        {
            return response()->json(['error' => 'Usuario no especificado'], 401);
        }
    }

    public function tareaTerminar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tarea_id' => 'required|numeric',
            'user_id' => 'required|numeric',
            'profesor' => 'required',
            'cancelar' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }
        $tarea = Tarea::where('id', $request['tarea_id'])->first();
        if ($tarea != null)
        {
            if ($request['user_id'] != $tarea->user_id_pro && $request['user_id'] != $tarea->user_id)
                return response()->json(['error' => 'Usuario especificado no coincide con los datos de la Tarea'], 401);

            if ($tarea->estado == 'Sin_Profesor' || $tarea->estado == 'Pago_Rechazado' ||
                $tarea->estado == 'Sin_Pago' || $tarea->estado == 'Terminado' || $tarea->estado == 'Calificado')
                return response()->json(['error' => 'La Tarea ya no permite modificación'], 401);
            
            if ($request['cancelar'] == 1)
            {
                $data['activa'] = false;
                $data['fecha_canc'] = date("Y-m-d H:i:s");
                $data['user_canc'] = $request['user_id'];
                $actualizado = Tarea::where('id', $request['tarea_id'] )->update( $data );
                if(!$actualizado )
                {
                    return response()->json(['error' => 'Ocurrió un error al terminar la tarea.'], 401);
                }
                else
                {
                    //enviar notificacion al profesor o alumno
                    $errorNotif = 'OK';
                    $dateTime = date("Y-m-d H:i:s");
                    $titulo = 'Tarea Cancelada';
                    $texto = 'La Tarea '.$tarea->id.' ha sido cancelada por el ';
                    try 
                    {
                        if ($request['user_id'] == $tarea->user_id_pro)
                        {
                            $userNotif = User::where('id', $tarea->user_id)->first();
                            $texto = $texto.'Profesor, '.$dateTime;
                        }
                        else
                        {
                            $userNotif = User::where('id', $tarea->user_id_pro)->first();
                            $texto = $texto.'Alumno, '.$dateTime;
                        }
                        if ($userNotif != null && $userNotif->token != null)
                        {
                            $notificacionEnviar['to'] = $userNotif->token;
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
                        'user_id' => $request['user_id'] == $tarea->user_id_pro ? $tarea->user_id : $tarea->user_id_pro,
                        'notificacion' => $titulo.'|'.$texto,
                        'estado' => $errorNotif
                        ]);

                    return response()->json(['success' => 'Tarea terminada exitosamente'], 200);
                }  
            }
        }
        else
        {
            return response()->json(['error' => 'No se encontró la Tarea'], 401);
        }            
    }
    
    public function listaTareas()
    {
        if( \Request::get('user_id') )
        {
            $search = \Request::get('user_id');
            $user = User::where('id', '=', $search )->first();
            if ($user == null) 
            {
                return response()->json([ 'error' => 'El usuario no existe!'], 401);
            }
            if ($user['tipo'] == 'Alumno') 
            {
                $tareas = Tarea::leftJoin('users', 'users.id', '=', 'tareas.user_id_pro')
                            ->where('user_id', $search)
                            ->select('tareas.id','users.name', 'materia', 'tema', 'fecha_entrega', 'hora_inicio', 'hora_fin', 
                            'descripcion', 'formato_entrega', 'estado', 'user_id_pro', 'tiempo_estimado', 'inversion', 'archivo',
                            'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor', 'fecha_canc')
                            ->orderBy('tareas.id', 'desc')->get();
            }
            else
            {
                $tareas = Tarea::join('users', 'users.id', '=', 'tareas.user_id')
                            ->where('user_id_pro', $search)
                            ->select('tareas.id','users.name', 'materia', 'tema', 'fecha_entrega', 'hora_inicio', 'hora_fin', 
                            'descripcion', 'formato_entrega', 'estado', 'user_id_pro', 'tiempo_estimado', 'inversion', 'archivo',
                            'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor', 'fecha_canc')
                            ->orderBy('tareas.id', 'desc')->get();
            }
            return response()->json($tareas, 200);
        }
        else
        {
            return response()->json(['error' => 'Usuario no especificado'], 401);
        }
    }
}