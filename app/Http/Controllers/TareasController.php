<?php

namespace App\Http\Controllers;

use App\User;
use App\Tarea;
use App\Clase;
use App\Materia;
use App\Alumno;
use App\Pago;
use App\Profesore;
use App\TareaEjercicio;
use App\TareaProfesor;
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
            
            $limit = date("Y-m-d H:i:s", strtotime($request['fecha_entrega'].' '.$request['hora_inicio']. '-2 hours'));
            $limitDate = date_format(date_create($limit), "Y-m-d");
            $limitTime = date_format(date_create($limit), "H:i:s");
            $nowDate = date("Y-m-d");
            $nowTime = date("H:i:s");
            if (($nowDate > $limitDate) || (($nowDate == $limitDate) && ($nowTime > $limitTime)))
            {
                return response()->json([ 'error' => 'Tiempo insuficiente para solicitar Tarea'], 401);
            }
            $user = User::where('id', '=', $request['user_id'] )->first();
            if ($user == null) 
            {
                return response()->json([ 'error' => 'Usuario no existe!'], 401);
            }
            $alumno = Alumno::where('user_id', '=', $request['user_id'] )->first();
            if ($alumno == null) 
            {
                return response()->json([ 'error' => 'Usuario no puede solicitar Tarea'], 401);
            }
            $materia = Materia::where('nombre', '=', $request['materia'] )->first();
            if ($materia == null)
            {
                return response()->json(['error' => 'Materia enviada Inválida'], 401);
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
                                    ->where('profesores.ciudad', $alumno->ciudad)
                                    ->where('profesores.disponible', true)
                                    ->where('profesor_materia.activa', true)
                                    ->where('profesor_materia.materia', $tarea->materia)
                                    ->select('users.email', 'users.token', 'users.sistema', 'users.id', 'users.name')
                                    ->get();
                
                $notificacion['titulo'] = 'Solicitud de Tarea';
                $notificacion['texto'] = 'Ha sido solicitada una Tarea de '.$tarea->materia.', '.$tarea->tema
                                        .', para el '.$tarea->fecha_entrega.' de '
                                        .$tarea->hora_inicio.' a '.$tarea->hora_fin
                                        .', en '.$tarea->formato_entrega.', por '.$user->name;
                $notificacion['estado'] = 'NO';
                $notificacion['tarea_id'] = $tarea->id;
                $notificacion['clase_id'] = 0;
                $notificacion['chat_id'] = 0;
                $notificacion['compra_id'] = 0;
                $pushClass = new NotificacionesPushFcm();
                foreach($profesores as $solicitar)
                    $pushClass->enviarNotificacion($notificacion, $solicitar);

                return response()->json(['success'=> 'Tarea Solicitada!',
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
            $tarea = Tarea::join('materias', 'tareas.materia', '=', 'materias.nombre')
                        ->where('user_id', $search)
                        ->where('tareas.activa', true)
                        ->select('tareas.*', 'icono')->first();
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
                        ->join('users', 'users.id', '=', 'tareas.user_id')
                        ->join('materias', 'tareas.materia', '=', 'materias.nombre')
                        ->where('tareas.estado', 'Solicitado')
                        ->where('profesores.user_id', $search)
                        ->where('profesores.activo', true)
                        ->where('profesor_materia.activa', true)
                        ->where('profesores.tareas', true)
                        ->select('tareas.id', 'tareas.user_id', 'tareas.materia', 'tareas.tema', 
                        'tareas.fecha_entrega', 'tareas.hora_inicio', 'tareas.hora_fin', 
                        'tareas.descripcion', 'tareas.formato_entrega', 'tareas.archivo',
                        'profesores.valor_tarea',
                        'users.name', 'alumnos.calificacion', 'users.avatar', 'icono')
                        ->orderBy('tareas.id', 'desc')->take(100)->get();
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
                return response()->json(['error' => 'Usuario no relacionado a la Tarea'], 401);

            if ($tarea->estado == 'Sin_Profesor' || $tarea->estado == 'Pago_Rechazado' ||
                $tarea->estado == 'Sin_Pago' || $tarea->estado == 'Terminado' || $tarea->estado == 'Calificado')
                return response()->json(['error' => 'Tarea no permite modificación'], 401);
            
            $data['activa'] = false;
            if ($request['cancelar'] == 1)
            {
                $data['estado'] = 'Cancelado';
                $data['fecha_canc'] = date("Y-m-d H:i:s");
                $data['user_canc'] = $request['user_id'];
                $actualizado = Tarea::where('id', $request['tarea_id'] )->update( $data );
                if(!$actualizado )
                {
                    return response()->json(['error' => 'Error al Terminar Tarea'], 401);
                }
                else
                {
                    if ($tarea->estado == 'Aceptado')
                    {
                        //devolver las horas al alumno 
                        $penHoras = $tarea->tiempo_estimado;
                        if ($request['user_id'] == $tarea->user_id)
                        {
                            //penalizar 1 hora al alumno
                            $penHoras -= 1;
                        }
                        if ($penHoras != 0)
                        {
                            //quitar las horas del combo del Alumno
                            $bill = Alumno::where('user_id', $tarea->user_id)->first();
                            $resto = 0;
                            if ($bill->billetera > $penHoras * -1)
                                $dataCombo['billetera'] = $bill->billetera + $penHoras;
                            else
                            {
                                $resto = $penHoras + $bill->billetera;
                                $dataCombo['billetera'] = 0;
                                //ver q hacer con horas negativas
                            }
                            $dataCombo['billetera'] = $bill->billetera + $penHoras;
                            $actCombo = Alumno::where('user_id', $bill->user_id )->update( $dataCombo );
                            if(!$actCombo)
                                return response()->json(['error' => 'Error al Actualizar Billetera del Alumno'], 401);
                        }
                        //quitar pago al profesor
                        $pagoTarea = Pago::where('user_id', $tarea->user_id_pro)->where('tarea_id', $tarea->id)->first();
                        if ($pagoTarea != null) 
                        {
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
                                    return response()->json(['error' => 'Error al crear Multa al Profesor'], 401);
                            }
                            else if ($pagoTarea->estado == 'Solicitado')
                            {
                                $pago['estado'] = 'Cancelado';
                                $actualizado = Pago::where('user_id', $tarea->user_id_pro)->where('tarea_id', $tarea->id)->update( $pago );
                                if(!$actualizado)
                                    return response()->json(['error' => 'Error al Cancelar Pago al Profesor'], 401);
                            }
                        }
                    }
                    $correoAdmin = '';
                    if ($tarea->user_id_pro != null && $tarea->user_id != null)
                    {
                        //enviar notificacion al profesor o alumno
                        $dateTime = date("Y-m-d H:i:s");
                        $notificacion['titulo'] = 'Tarea Cancelada';
                        $notificacion['texto'] = 'Lamentamos informarte que el Alumno ha cancelado la Tarea de '.$tarea->materia.', '.$tarea->tema.', mantente pendiente a más solicitudes.';
                        $notificacion['estado'] = 'NO';
                        $notificacion['tarea_id'] = $tarea->id;
                        $notificacion['clase_id'] = 0;
                        $notificacion['chat_id'] = 0;
                        $notificacion['compra_id'] = 0;
                        if ($request['user_id'] == $tarea->user_id_pro)
                        {
                            $userNotif = User::where('id', $tarea->user_id)->first();
                            $correoAdmin = 'La Tarea de '.$tarea->materia.', '.$tarea->tema.', ha sido cancelada por el Profesor '.$userNotif->name.' a las '.$dateTime;
                            $notificacion['texto'] = 'Lamentamos informarte que el Profesor ha cancelado la Tarea de '.$tarea->materia.', '.$tarea->tema;
                        }
                        else
                        {
                            $userNotif = User::where('id', $tarea->user_id_pro)->first();
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

                    return response()->json(['success' => 'Tarea Cancelada!'], 200);
                }  
            }
            else
            {            
                $data['estado'] = 'Terminado';
                $actualizado = Tarea::where('id', $request['tarea_id'] )->update( $data );
                if(!$actualizado )
                    return response()->json(['error' => 'Error al Finalizar la Tarea'], 401);
                else
                    return response()->json(['success' => 'Tarea Finalizada'], 200);
            }  
        }
        else
        {
            return response()->json(['error' => 'No se encontró Tarea'], 401);
        }            
    }
    
    public function listaTareas()
    {
        if( \Request::get('user_id') )
        {
            $tipo = \Request::get('tipo');
            if ($tipo != 'ACTUAL' && $tipo != 'ANTERIOR')
                $tipo = 'ACTUAL';
            if ( $tipo == 'ACTUAL' || $tipo == 'ANTERIOR')
            {
                $search = \Request::get('user_id');
                $user = User::where('id', '=', $search )->first();
                if ($user == null) 
                {
                    return response()->json([ 'error' => 'Usuario no existe!'], 401);
                }
                if ($user['tipo'] == 'Alumno') 
                {
                    if ($tipo == 'ACTUAL')
                        $tareas = Tarea::leftJoin('users', 'users.id', '=', 'tareas.user_id_pro')
                                    ->leftJoin('profesores', 'profesores.user_id', '=', 'tareas.user_id_pro')
                                    ->join('materias', 'tareas.materia', '=', 'materias.nombre')
                                    ->where('tareas.user_id', $search)
                                    ->whereIn('estado', ['Solicitado', 'Sin_Horas', 'Confirmado', 'Aceptado', 'Pago_Aprobado', 'Confirmando_Pago'])
                                    ->select('tareas.id','users.name', 'materia', 'tema', 'fecha_entrega', 'hora_inicio', 'hora_fin', 
                                    'tareas.descripcion', 'formato_entrega', 'estado', 'user_id_pro', 'tiempo_estimado', 'inversion', 'archivo',
                                    'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor', 'fecha_canc',
                                    'users.avatar', 'profesores.calificacion', 'icono', 'tareas.activa')
                                    ->orderBy('tareas.id', 'desc')->get();
                    else
                        $tareas = Tarea::leftJoin('users', 'users.id', '=', 'tareas.user_id_pro')
                                    ->leftJoin('profesores', 'profesores.user_id', '=', 'tareas.user_id_pro')
                                    ->join('materias', 'tareas.materia', '=', 'materias.nombre')
                                    ->where('tareas.user_id', $search)
                                    ->whereIn('estado', ['Terminado', 'Calificado', 
                                                        'Cancelado', 'Sin_Profesor', 'Sin_Pago', 'Pago_Rechazado'])
                                    ->select('tareas.id','users.name', 'materia', 'tema', 'fecha_entrega', 'hora_inicio', 'hora_fin', 
                                    'tareas.descripcion', 'formato_entrega', 'estado', 'user_id_pro', 'tiempo_estimado', 'inversion', 'archivo',
                                    'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor', 'fecha_canc',
                                    'users.avatar', 'profesores.calificacion', 'icono', 'tareas.activa')
                                    ->orderBy('tareas.id', 'desc')->get();
                }
                else
                {
                    if ($tipo == 'ACTUAL')
                        $tareas = Tarea::join('users', 'users.id', '=', 'tareas.user_id')
                                ->join('alumnos', 'alumnos.user_id', '=', 'tareas.user_id')
                                ->join('materias', 'tareas.materia', '=', 'materias.nombre')
                                ->where('tareas.user_id_pro', $search)
                                ->whereIn('estado', ['Solicitado', 'Sin_Horas', 'Confirmado', 'Aceptado', 'Pago_Aprobado', 'Confirmando_Pago'])
                                ->select('tareas.id','users.name', 'materia', 'tema', 'fecha_entrega', 'hora_inicio', 'hora_fin', 
                                'tareas.descripcion', 'formato_entrega', 'estado', 'user_id_pro', 'tiempo_estimado', 'inversion', 'archivo',
                                'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor', 'fecha_canc',
                                'users.avatar', 'alumnos.calificacion', 'icono', 'tareas.activa')
                                ->orderBy('tareas.id', 'desc')->get();
                    else
                        $tareas = Tarea::join('users', 'users.id', '=', 'tareas.user_id')
                                ->join('alumnos', 'alumnos.user_id', '=', 'tareas.user_id')
                                ->join('materias', 'tareas.materia', '=', 'materias.nombre')
                                ->where('tareas.user_id_pro', $search)
                                ->whereIn('estado', ['Terminado', 'Calificado', 
                                                    'Cancelado', 'Sin_Profesor', 'Sin_Pago', 'Pago_Rechazado'])
                                ->select('tareas.id','users.name', 'materia', 'tema', 'fecha_entrega', 'hora_inicio', 'hora_fin', 
                                'tareas.descripcion', 'formato_entrega', 'estado', 'user_id_pro', 'tiempo_estimado', 'inversion', 'archivo',
                                'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor', 'fecha_canc',
                                'users.avatar', 'alumnos.calificacion', 'icono', 'tareas.activa')
                                ->orderBy('tareas.id', 'desc')->get();
                }
                return response()->json($tareas->take(100), 200);
            }
            else
                return response()->json(['error' => 'Tipo no válido'], 401);
        }
        else
            return response()->json(['error' => 'Usuario no especificado'], 401);
    }

    public function listaTareasHoras()
    {
        if( \Request::get('user_id') )
        {
            $tipo = \Request::get('tipo');
            if ($tipo != 'ACTUAL' && $tipo != 'ANTERIOR')
                $tipo = 'ACTUAL';
            if ( $tipo == 'ACTUAL' || $tipo == 'ANTERIOR')
            {
                $search = \Request::get('user_id');
                $user = User::where('id', '=', $search )->first();
                if ($user == null) 
                {
                    return response()->json([ 'error' => 'Usuario no existe!'], 401);
                }
                $nowDate = date_format(date_create(date("Y-m-d H:i:s")), "Y-m-d");
                if ($user['tipo'] == 'Alumno') 
                {
                    if ($tipo == 'ACTUAL')
                        $tareas = Tarea::leftJoin('users', 'users.id', '=', 'tareas.user_id_pro')
                                    ->leftJoin('profesores', 'profesores.user_id', '=', 'tareas.user_id_pro')
                                    ->join('materias', 'tareas.materia', '=', 'materias.nombre')
                                    ->where('tareas.user_id', $search)->where('fecha_entrega', '>=', $nowDate)
                                    ->select('tareas.id','users.name', 'materia', 'tema', 'fecha_entrega', 'hora_inicio', 'hora_fin', 
                                    'tareas.descripcion', 'formato_entrega', 'estado', 'user_id_pro', 'tiempo_estimado', 'inversion', 'archivo',
                                    'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor', 'fecha_canc',
                                    'users.avatar', 'profesores.calificacion', 'icono', 'tareas.activa')
                                    ->orderBy('tareas.id', 'desc')->get();
                    else
                        $tareas = Tarea::leftJoin('users', 'users.id', '=', 'tareas.user_id_pro')
                                    ->leftJoin('profesores', 'profesores.user_id', '=', 'tareas.user_id_pro')
                                    ->join('materias', 'tareas.materia', '=', 'materias.nombre')
                                    ->where('tareas.user_id', $search)->where('fecha_entrega', '<', $nowDate)
                                    ->select('tareas.id','users.name', 'materia', 'tema', 'fecha_entrega', 'hora_inicio', 'hora_fin', 
                                    'tareas.descripcion', 'formato_entrega', 'estado', 'user_id_pro', 'tiempo_estimado', 'inversion', 'archivo',
                                    'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor', 'fecha_canc',
                                    'users.avatar', 'profesores.calificacion', 'icono', 'tareas.activa')
                                    ->orderBy('tareas.id', 'desc')->get();
                }
                else
                {
                    if ($tipo == 'ACTUAL')
                        $tareas = Tarea::join('users', 'users.id', '=', 'tareas.user_id')
                                ->join('alumnos', 'alumnos.user_id', '=', 'tareas.user_id')
                                ->join('materias', 'tareas.materia', '=', 'materias.nombre')
                                ->where('tareas.user_id_pro', $search)->where('fecha_entrega', '>=', $nowDate)
                                ->select('tareas.id','users.name', 'materia', 'tema', 'fecha_entrega', 'hora_inicio', 'hora_fin', 
                                'tareas.descripcion', 'formato_entrega', 'estado', 'user_id_pro', 'tiempo_estimado', 'inversion', 'archivo',
                                'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor', 'fecha_canc',
                                'users.avatar', 'alumnos.calificacion', 'icono', 'tareas.activa')
                                ->orderBy('tareas.id', 'desc')->get();
                    else
                        $tareas = Tarea::join('users', 'users.id', '=', 'tareas.user_id')
                                ->join('alumnos', 'alumnos.user_id', '=', 'tareas.user_id')
                                ->join('materias', 'tareas.materia', '=', 'materias.nombre')
                                ->where('tareas.user_id_pro', $search)->where('fecha_entrega', '<', $nowDate)
                                ->select('tareas.id','users.name', 'materia', 'tema', 'fecha_entrega', 'hora_inicio', 'hora_fin', 
                                'tareas.descripcion', 'formato_entrega', 'estado', 'user_id_pro', 'tiempo_estimado', 'inversion', 'archivo',
                                'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor', 'fecha_canc',
                                'users.avatar', 'alumnos.calificacion', 'icono', 'tareas.activa')
                                ->orderBy('tareas.id', 'desc')->get();
                }
                return response()->json($tareas->take(100), 200);
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
        $tarea = Tarea::join('alumnos', 'alumnos.user_id', '=', 'tareas.user_id')
                    ->join('users', 'users.id', '=', 'tareas.user_id')
                    ->join('materias', 'tareas.materia', '=', 'materias.nombre')
                    ->leftJoin('profesores', 'profesores.user_id', '=', 'tareas.user_id_pro')
                    ->leftJoin('users as p', 'p.id', '=', 'tareas.user_id_pro')
                    ->where('tareas.id', $search)
                    ->select( 'tareas.*', 'profesores.descripcion as profDescripcion', 'icono',
                    'users.name as alumno', 'alumnos.calificacion as alumnoCalif', 'users.avatar as alumnoAvatar',
                    'p.name as profesor', 'profesores.calificacion as profCalif', 'p.avatar as profAvatar')
                    ->first();
        if ($tarea != null)
        {
            $tarea['profAplicados'] = [];
            $respProfAp = [];
            if ($tarea->estado == 'Solicitado')
            {
                $profesoresAplicados = TareaProfesor::where('tarea_id', $tarea->id)
                                        ->where('estado', 'Solicitado')->get();

                foreach($profesoresAplicados as $aplico)
                    $respProfAp[] = $aplico->user_id;
                
                $tarea['profAplicados'] = $respProfAp;
            }
            
            $tarea['profClases'] = 0;
            $tarea['profTareas'] = 0;
            if ($tarea->user_id_pro > 0)
            {
                $clases = Clase::where('user_id_pro', $tarea->user_id_pro)
                            ->where('estado', 'Terminado')->get();
                $tareas = Tarea::where('user_id_pro', $tarea->user_id_pro)
                            ->where('estado', 'Terminado')->get();
                $tarea['profClases'] = $clases->count();
                $tarea['profTareas'] = $tareas->count();
            }
            return response()->json($tarea, 200);
        }
        else
        {
            return response()->json(['error' => 'Tarea no Encontrada'], 401);
        }
    }
}