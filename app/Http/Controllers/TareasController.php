<?php

namespace App\Http\Controllers;

use App\User;
use App\Tarea;
use App\Materia;
use App\Alumno;
use App\Pago;
use App\Profesore;
use App\TareaEjercicio;
use App\NotificacionesPushFcm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\Notificacion;
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
                                    ->select('users.email', 'users.token', 'users.sistema', 'users.id', 'users.name')
                                    ->get();
                
                $notificacion['titulo'] = 'Solicitud de Tarea';
                $dateTime = date("Y-m-d H:i:s");
                $notificacion['texto'] = 'Ha sido solicitada la Tarea '.$tarea->id.' de '.$tarea->materia
                                        .' ('.$tarea->tema.'), para el '.$tarea->fecha_entrega.' de '
                                        .$tarea->hora_inicio.' a '.$tarea->hora_fin
                                        .', en '.$tarea->formato_entrega.', por '.$user->name.', '.$dateTime;
                $notificacion['estado'] = 'NO';
                $pushClass = new NotificacionesPushFcm();
                foreach($profesores as $solicitar)
                    $pushClass->enviarNotificacion($notificacion, $solicitar);

                return response()->json(['success'=> 'Tarea solicitada exitosamente',
                                        'tarea' => $tarea], 200);
            }
            else
            {
                return response()->json(['error' => 'Ocurrió un error al registrar!'], 401);
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
                return response()->json(['error' => 'Usuario no coincide con datos de la Tarea'], 401);

            if ($tarea->estado == 'Sin_Profesor' || $tarea->estado == 'Pago_Rechazado' ||
                $tarea->estado == 'Sin_Pago' || $tarea->estado == 'Terminado' || $tarea->estado == 'Calificado')
                return response()->json(['error' => 'La Tarea no permite modificación'], 401);
            
            if ($request['cancelar'] == 1)
            {
                $data['activa'] = false;
                $data['estado'] = 'Cancelado';
                $data['fecha_canc'] = date("Y-m-d H:i:s");
                $data['user_canc'] = $request['user_id'];
                $actualizado = Tarea::where('id', $request['tarea_id'] )->update( $data );
                if(!$actualizado )
                {
                    return response()->json(['error' => 'Ocurrió un error al terminar la tarea'], 401);
                }
                else
                {
                    if ($tarea->estado == 'Aceptado')
                    {
                        $penHoras = 0;
                        if ($request['user_id'] == $tarea->user_id_pro)
                        {
                            //devolver las horas al alumno 
                            $penHoras = $tarea->tiempo_estimado;
                        }
                        else
                        {
                            //devolver las horas al alumno 
                            $penHoras = $$tarea->tiempo_estimado;
                            //quitar pago al profesor
                        }
                        if ($penHoras != 0)
                        {
                            //quitar las horas del combo del Alumno
                            $bill = Alumno::where('user_id', $clase->user_id)->first();
                            $resto = 0;
                            if ($bill->billetera > $penHoras * -1)
                                $dataCombo['billetera'] = $bill->billetera + $penHoras;
                            else
                            {
                                $resto = $penHoras + $bill->billetera;
                                $dataCombo['billetera'] = 0;
                                //ver q hacer con horas negativas
                            }
                            $actCombo = Alumno::where('user_id', $bill->user_id )->update( $dataCombo );
                            if(!$actCombo)
                                return response()->json(['error' => 'Ocurrió un error al Actualizar Billetera del Alumno'], 401);
                        }
                        //quitar pago al profesor
                        $pagoTarea = Pago::where('user_id', $tarea->user_id_pro)->where('tarea_id', $tarea->id)->first();
                        if ($pagoTarea->estado == 'Aprobado')
                        {
                            $multaProf = Multa::create([
                                        'user_id' => $tarea->user_id_pro,
                                        'clase_id' => 0,
                                        'tarea_id' => $tarea->id,
                                        'valor' => $pagoTarea->valor,
                                        'comentario' => 'Tarea Cancelada por Profesor con Pago Aprobado',
                                        'estado' => 'Solicitado'
                                        ]);
                            if (!$multaProf->id)
                                return response()->json(['error' => 'Ocurrió un error al crear Multa al Profesor'], 401);
                        }
                        else if ($pagoTarea->estado == 'Solicitado')
                        {
                            $pago['estado'] = 'Cancelado';
                            $actualizado = Pago::where('user_id', $tarea->user_id_pro)->where('tarea_id', $tarea->id)->update( $pago );
                            if(!$actualizado)
                                return response()->json(['error' => 'Ocurrió un error al Cancelar Pago al Profesor'], 401);
                        }
                    }
                    $correoAdmin = '';
                    if ($tarea->user_id_pro != null && $tarea->user_id != null)
                    {
                        //enviar notificacion al profesor o alumno
                        $dateTime = date("Y-m-d H:i:s");
                        $notificacion['titulo'] = 'Tarea Cancelada';
                        $notificacion['texto'] = 'La Tarea '.$tarea->id.' ha sido cancelada por el ';
                        $notificacion['estado'] = 'NO';
                        if ($request['user_id'] == $tarea->user_id_pro)
                        {
                            $userNotif = User::where('id', $tarea->user_id)->first();
                            $correoAdmin = $texto.'Profesor '.$userNotif->name.' a las '.$dateTime;
                            $texto = $texto.'Profesor, '.$dateTime;
                        }
                        else
                        {
                            $userNotif = User::where('id', $tarea->user_id_pro)->first();
                            $texto = $texto.'Alumno, '.$dateTime;
                        }
                        $pushClass = new NotificacionesPushFcm();
                        $pushClass->enviarNotificacion($notificacion, $userNotif);
                    }
                    //verificar tercera cancelacion del profesor para avisar al ADMIN
                    if ($request['user_id'] == $tarea->user_id_pro)
                    {
                        $mes = date("Y-m-1");
                        $cant = Tarea::where('user_canc', $tarea->user_id_pro)
                                    ->where('fecha_canc', '>=', $mes)->count();
                        if ($cant > 2)
                        {
                            try 
                            {
                                Mail::to(env('MAILADMIN'))->send(new Notificacion(
                                        'Administrador de '.env('EMPRESA'), $correoAdmin, '',
                                        'El profesor este mes ya ha cancelado un total de '.$cant.' tareas', 
                                        env('EMPRESA')));
                            }
                            catch (Exception $e) { }
                        }
                    }

                    return response()->json(['success' => 'Tarea Cancelada exitosamente'], 200);
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
            $tipo = \Request::get('tipo');
            if ( $tipo == 'ACTUAL' || $tipo == 'ANTERIOR')
            {
                $search = \Request::get('user_id');
                $user = User::where('id', '=', $search )->first();
                if ($user == null) 
                {
                    return response()->json([ 'error' => 'El usuario no existe!'], 401);
                }
                $nowDate = date_format(date_create(date("Y-m-d H:i:s")), "Y-m-d");
                if ($user['tipo'] == 'Alumno') 
                {
                    if ($tipo == 'ACTUAL')
                        $tareas = Tarea::leftJoin('users', 'users.id', '=', 'tareas.user_id_pro')
                                    ->where('user_id', $search)->where('fecha_entrega', $nowDate)
                                    ->select('tareas.id','users.name', 'materia', 'tema', 'fecha_entrega', 'hora_inicio', 'hora_fin', 
                                    'descripcion', 'formato_entrega', 'estado', 'user_id_pro', 'tiempo_estimado', 'inversion', 'archivo',
                                    'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor', 'fecha_canc')
                                    ->orderBy('tareas.id', 'desc')->get();
                    else
                        $tareas = Tarea::leftJoin('users', 'users.id', '=', 'tareas.user_id_pro')
                                    ->where('user_id', $search)->where('fecha_entrega', '<', $nowDate)
                                    ->select('tareas.id','users.name', 'materia', 'tema', 'fecha_entrega', 'hora_inicio', 'hora_fin', 
                                    'descripcion', 'formato_entrega', 'estado', 'user_id_pro', 'tiempo_estimado', 'inversion', 'archivo',
                                    'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor', 'fecha_canc')
                                    ->orderBy('tareas.id', 'desc')->get();
                }
                else
                {
                    if ($tipo == 'ACTUAL')
                        $tareas = Tarea::join('users', 'users.id', '=', 'tareas.user_id')
                                ->where('user_id_pro', $search)->where('fecha_entrega', $nowDate)
                                ->select('tareas.id','users.name', 'materia', 'tema', 'fecha_entrega', 'hora_inicio', 'hora_fin', 
                                'descripcion', 'formato_entrega', 'estado', 'user_id_pro', 'tiempo_estimado', 'inversion', 'archivo',
                                'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor', 'fecha_canc')
                                ->orderBy('tareas.id', 'desc')->get();
                    else
                        $tareas = Tarea::join('users', 'users.id', '=', 'tareas.user_id')
                                ->where('user_id_pro', $search)->where('fecha_entrega', '<', $nowDate)
                                ->select('tareas.id','users.name', 'materia', 'tema', 'fecha_entrega', 'hora_inicio', 'hora_fin', 
                                'descripcion', 'formato_entrega', 'estado', 'user_id_pro', 'tiempo_estimado', 'inversion', 'archivo',
                                'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor', 'fecha_canc')
                                ->orderBy('tareas.id', 'desc')->get();
                }
                return response()->json($tareas, 200);
            }
            else
                return response()->json(['error' => 'Tipo no válido'], 401);
        }
        else
            return response()->json(['error' => 'Usuario no especificado'], 401);
    }

    public function devuelveTarea()
    {
        $search = \Request::get('tarea_id');
        $tarea = Tarea::where('id', $search)->first();
        if ($tarea != null)
        {
            return response()->json($tarea, 200);
        }
        else
        {
            return response()->json(['error' => 'Tarea no Encontrada'], 401);
        }
    }
}