<?php

namespace App\Http\Controllers;

use App\Materia;
use App\User;
use App\Clase;
use App\Tarea;
use App\Multa;
use App\Alumno;
use App\Pago;
use App\Profesore;
use App\ClasesGratis;
use App\ClaseEjercicio;
use App\NotificacionesPushFcm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\Notificacion;
use Validator;
use Hash;

/* ESTADOS CLASE

Solicitado
Sin_Horas
Confirmado
Aceptado (pagado)
Terminado
Calificado
Cancelado
Sin_Profesor
Sin_Pago
Pago_Rechazado
Pago_Aprobado
Confirmando_Pago
*/

class ClasesController extends Controller
{
    public function solicitarClase(Request $request)
    {
        if ($request) 
        {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'materia' => 'required',
                'tema' => 'required',
                'fecha' => 'required',
                'personas' => 'required',
                'duracion' => 'required',
                'ubicacion' => 'required',
                'selProfesor' => 'required'
            ]);
            if ($validator->fails()) 
            {
                return response()->json(['error' => $validator->errors()], 406);
            }
            
            $user = User::where('id', '=', $request['user_id'] )->first();
            if ($user == null) 
            {
                return response()->json([ 'error' => 'Usuario no existe!'], 401);
            }
            $alumno = Alumno::where('user_id', '=', $request['user_id'] )->first();
            if (($alumno == null) || ($alumno != null && !$alumno->activo) )
            {
                return response()->json([ 'error' => 'Usuario no puede solicitar Clase'], 401);
            }
            $materia = Materia::where('nombre', '=', $request['materia'] )->first();
            if ($materia == null)
            {
                return response()->json(['error' => 'Materia enviada inválida'], 401);
            }
            $claseAnterior = null;
            if ($request['selProfesor']==1)
            {
                $claseAnterior = Clase::where('user_id', $request['user_id'])
                                ->whereIn('estado', ['Aceptado', 'Terminado', 'Calificado'])
                                ->orderBy('id', 'desc')->first();
                if ($claseAnterior == nul)
                    return response()->json(['error' => 'Sin Clase Anterior para seleccionar Profesor'], 401);
            }
            $duracion = $request['duracion'] + ($request['personas'] - 1);
            if ($duracion < 2)
                $duracion = 2;

            $coordenadas = isset($request['coordenadas']) ? $request['coordenadas'] : NULL;
            $hora1 = isset($request['hora1']) ? $request['hora1'] : NULL;
            $hora2 = isset($request['hora2']) ? $request['hora2'] : NULL;
            $descripcion = isset($request['descripcion']) ? $request['descripcion'] : NULL;
            $horasCombo = isset($request['horasCombo']) ? $request['horasCombo'] : NULL;
            $precioCombo = isset($request['precioCombo']) ? $request['precioCombo'] : NULL;
            $clase = Clase::create([
                'user_id' => $request['user_id'],
                'materia' => $request['materia'],
                'tema' => $request['tema'],
                'fecha' => $request['fecha'],
                'hora1' => $hora1,
                'hora2' => $hora2,
                'personas' => $request['personas'],
                'duracion' => $request['duracion'],
                'descripcion' => $descripcion,
                'combo' => 'COMBO',
                'ubicacion' => $request['ubicacion'],
                'coordenadas' => $coordenadas,
                'estado' => $alumno->billetera < $duracion ? 'Sin_Horas' : 'Solicitado',
                'seleccion_profesor' => $request['selProfesor'] == 1,
                'activa' => true,
                'horasCombo' => $horasCombo,
                'precioCombo' => $precioCombo
            ]);

            if ($clase->id)
            {
                if ($clase->estado == 'Solicitado')
                {
                    $profesores = Profesore::join('profesor_materia', 'profesor_materia.user_id', '=', 'profesores.user_id')
                                            ->join('users', 'users.id', '=', 'profesores.user_id')
                                            ->where('profesores.activo', true)
                                            ->where('profesores.clases', true)
                                            ->where('profesores.disponible', true)
                                            ->where('profesores.ciudad', $alumno->ciudad)
                                            ->where('profesor_materia.activa', true)
                                            ->where('profesor_materia.materia', $clase->materia)
                                            ->select('users.email', 'users.token', 'users.sistema', 'users.id', 'users.name')
                                            ->get();
                    if ($claseAnterior != null)
                    {
                        $profSelccionado = $profesores->where('id', $claseAnterior->user_id_pro)->firts();
                        if ($profSelccionado != null)
                        {
                            $profesores = $profSelccionado;
                        }
                        else
                        {
                            $actClase['seleccion_profesor'] = false;
                            $actualizado = Clase::where('id', $clase->id )->update( $actClase );
                        }
                    }
                    //lanzar notificaciones a los profesores
                    $notificacion['titulo'] = 'Solicitud de Clase';
                    $dateTime = date("Y-m-d H:i:s");
                    $notificacion['clase_id'] = $clase->id;
                    $notificacion['tarea_id'] = 0;
                    $notificacion['chat_id'] = 0;
                    $notificacion['compra_id'] = 0;
                    $notificacion['texto'] = 'Ha sido solicitada la Clase '.$clase->id.' de '.$clase->materia
                            .', para el '.$clase->fecha.' a las '.$clase->hora1
                            .', en '.$clase->ubicacion.' para '.$clase->personas.' estudiantes con una duracion de '
                            .$clase->duracion.', por '.$user->name.', '.$dateTime;
                    $notificacion['estado'] = 'NO';
                    $pushClass = new NotificacionesPushFcm();
                    foreach($profesores as $solicitar)
                        $pushClass->enviarNotificacion($notificacion, $solicitar);
                }
                return response()->json(['success'=> 'Clase Solicitada',
                                        'clase' => $clase], 200);
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

    public function claseActiva()
    {
        if( \Request::get('user_id') )
        {
            $search = \Request::get('user_id');
            $clase = Clase::join('materias', 'clases.materia', '=', 'materias.nombre')
                        ->where('user_id', $search)
                        ->where('clases.activa', true)
                        ->select('clases.id', 'user_id', 'materia', 'tema', 'personas', 'duracion', 'hora1', 'hora2', 
                        'ubicacion', 'seleccion_profesor', 'fecha', 'hora_prof', 'horasCombo', 'precioCombo',
                        'user_id_pro', 'estado', 'calle', 'referencia', 'quien_preguntar', 'clases.activa', 'coordenadas',
                        'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor', 
                        'aplica_prof', 'descripcion', 'icono')->first();
            if (isset($clase->user_id_pro) && $clase->user_id_pro > 0)
            {
                $prof = User::where('id', $clase->user_id_pro)->first();
                $clase['profesor'] = $prof->name;
            }
            return response()->json($clase, 200);
        }
        else
        {
            return response()->json(['error' => 'Usuario no especificado'], 401);
        }
    }

    public function clasesDisponibles()
    {
        if( \Request::get('user_id') )
        {
            $search = \Request::get('user_id');
            $clases = Profesore::join('profesor_materia', 'profesor_materia.user_id', '=', 'profesores.user_id')
                        ->join('clases', 'profesor_materia.materia', '=', 'clases.materia')
                        ->join('alumnos', 'alumnos.user_id', '=', 'clases.user_id')
                        ->join('users', 'users.id', '=', 'clases.user_id')
                        ->join('materias', 'clases.materia', '=', 'materias.nombre')
                        ->where('clases.estado', 'Solicitado')
                        ->where('profesores.user_id', $search)
                        ->where('profesores.activo', true)
                        ->where('profesor_materia.activa', true)
                        ->where('profesores.clases', true)
                        ->select('clases.id', 'clases.user_id', 'clases.materia', 'clases.tema', 
                        'clases.personas', 'clases.duracion', 'clases.hora1', 'clases.hora2', 'fecha',
                        'clases.ubicacion', 'clases.coordenadas', 'clases.seleccion_profesor', 
                        'clases.descripcion', 'profesores.valor_clase',
                        'users.name', 'alumnos.calificacion', 'users.avatar', 'icono')
                        ->orderBy('clases.id', 'desc')->take(100)->get();
            $respuesta = [];
            foreach ($clases as $item)
            {
                $respuesta[] = $item;
                $respuesta[count($respuesta) - 1]['valor'] = ($item->duracion * $item->valor_clase) + ($item->personas - 1);
            }
            return response()->json($respuesta, 200);
        }
        else
        {
            return response()->json(['error' => 'Usuario no especificado'], 401);
        }
    }
    
    public function claseTerminar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clase_id' => 'required|numeric',
            'user_id' => 'required|numeric',
            'cancelar' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }
        $clase = Clase::where('id', $request['clase_id'])->first();
        if ($clase != null)
        {
            if ($request['user_id'] != $clase->user_id_pro && $request['user_id'] != $clase->user_id)
                return response()->json(['error' => 'Usuario no relacionado a la Clase'], 401);

            if ($clase->estado == 'Sin_Profesor' || $clase->estado == 'Pago_Rechazado' ||
                $clase->estado == 'Sin_Pago' || $clase->estado == 'Terminado' || 
                $clase->estado == 'Calificado' || $clase->activa == 0)
                return response()->json(['error' => 'Clase no permite modificación'], 401);

            $data['activa'] = false;
            if ($request['cancelar'] == 1)
            {
                $data['estado'] = 'Cancelado';
                $data['fecha_canc'] = date("Y-m-d H:i:s");
                $data['user_canc'] = $request['user_id'];
                $actualizado = Clase::where('id', $request['clase_id'] )->update( $data );
                if(!$actualizado )
                {
                    return response()->json(['error' => 'Error al Cancelar la Clase.'], 401);
                }
                else
                {
                    $dateTime = date("Y-m-d H:i:s");
                    if ($clase->user_id_pro != null && $clase->user_id != null)
                    {
                        //enviar notificacion al profesor o alumno
                        $correoAdmin = '';
                        $notificacion['titulo'] = 'Clase Cancelada';
                        $notificacion['texto'] = 'La Clase '.$clase->id.' ha sido cancelada por el ';
                        $notificacion['estado'] = 'NO';
                        $notificacion['clase_id'] = $clase->id;
                        $notificacion['tarea_id'] = 0;
                        $notificacion['chat_id'] = 0;
                        $notificacion['compra_id'] = 0;
                        if ($request['user_id'] == $clase->user_id_pro)
                        {
                            $userNotif = User::where('id', $clase->user_id)->first();
                            $correoAdmin = $notificacion['texto'].'Profesor '.$userNotif->name.' a las '.$dateTime;
                            $notificacion['texto'] = $notificacion['texto'].'Profesor, '.$dateTime;
                        }
                        else
                        {
                            $userNotif = User::where('id', $clase->user_id_pro)->first();
                            $notificacion['texto'] = $notificacion['texto'].'Alumno, '.$dateTime;
                        }
                        $pushClass = new NotificacionesPushFcm();
                        $pushClass->enviarNotificacion($notificacion, $userNotif);
                    }

                    $penHoras = 0;
                    $duracion = $clase->duracion + ($clase->personas - 1);
                    if ($duracion < 2)
                        $duracion = 2;
                    if ($request['user_id'] == $clase->user_id_pro)
                    {
                        //penalizar 2 hora al profesor
                        $profeClase = Profesore::where('user_id', $clase->user_id_pro)->first();
                        $multaProf = Multa::create([
                            'user_id' => $clase->user_id_pro,
                            'tarea_id' => 0,
                            'clase_id' => $clase->id,
                            'valor' => 2 * $profeClase->valor_clase,
                            'comentario' => 'Clase Cancelada',
                            'estado' => 'Solicitado'
                            ]);
                        if (!$multaProf->id)
                        {
                            return response()->json(['error' => 'Error al crear Multa al Profesor'], 401);
                        }
                        if ($clase->estado == 'Aceptado' || $clase->estado == 'Pago_Aprobado')
                        {
                            //devolver las horas al alumno 
                            $penHoras = $duracion;
                        }
                        //verificar tercera cancelacion para avisar al ADMIN
                        $mes = date("Y-m-1");
                        $cant = Clase::where('user_canc', $clase->user_id_pro)
                                ->where('fecha_canc', '>=', $mes)->count();
                        if ($cant > 2)
                        {
                            try 
                            {
                                Mail::to(env('MAILADMIN'))->send(new Notificacion(
                                        'Administrador de '.env('EMPRESA'), $correoAdmin, '',
                                        'El profesor este mes ya ha cancelado un total de '.$cant.' clases', 
                                        env('EMPRESA')));
                            }
                            catch (Exception $e) { }
                        }
                    }
                    else if ($request['user_id'] == $clase->user_id)
                    {
                        //penalizar 1 hora al alumno
                        $penHoras = -1;
                    }
                    if ($clase->estado == 'Aceptado' || $clase->estado == 'Pago_Aprobado')
                    {
                        //quitar pago al profesor
                        $pagoClase = Pago::where('user_id', $clase->user_id_pro)->where('clase_id', $clase->id)->first();
                        if ($pagoClase->estado == 'Aprobado')
                        {
                            $multaProf = Multa::create([
                                        'user_id' => $clase->user_id_pro,
                                        'tarea_id' => 0,
                                        'clase_id' => $clase->id,
                                        'valor' => $pagoClase->valor,
                                        'comentario' => 'Clase Cancelada por Profesor con Pago Aprobado',
                                        'estado' => 'Solicitado'
                                        ]);
                            if (!$multaProf->id)
                                return response()->json(['error' => 'Error al crear Multa al Profesor'], 401);
                        }
                        else if ($pagoClase->estado == 'Solicitado')
                        {
                            $pago['estado'] = 'Cancelado';
                            $actualizado = Pago::where('user_id', $clase->user_id_pro)->where('clase_id', $clase->id)->update( $pago );
                            if(!$actualizado )
                                return response()->json(['error' => 'Error al Cancelar Pago al Profesor'], 401);
                        }
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
                            return response()->json(['error' => 'Error al Actualizar Billetera del Alumno'], 401);
                    }
                    return response()->json(['success' => 'Clase Cancelada'], 200);
                }
            }
            else
            {            
                $data['estado'] = 'Terminado';
                $actualizado = Clase::where('id', $request['clase_id'] )->update( $data );
                if(!$actualizado )
                    return response()->json(['error' => 'Error al Finalizar la Clase'], 401);
                else
                    return response()->json(['success' => 'Clase Finalizada'], 200);
            }  
        }
        else
        {
            return response()->json(['error' => 'No se encontró la Clase'], 401);
        }            
    }
    
    public function listaClases()
    {
        if ( \Request::get('user_id') )
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
                        $clases = Clase::leftJoin('users', 'users.id', '=', 'clases.user_id_pro')
                                ->leftJoin('profesores', 'profesores.user_id', '=', 'clases.user_id_pro')
                                ->join('materias', 'clases.materia', '=', 'materias.nombre')
                                ->where('clases.user_id', $search)
                                ->whereIn('estado', ['Solicitado', 'Confirmado', 'Aceptado', 'Pago_Aprobado', 'Confirmando_Pago'])
                                ->select('clases.id','users.name', 'materia', 'tema', 'personas', 'duracion', 'hora1', 'hora2', 
                                'clases.ubicacion', 'seleccion_profesor', 'fecha', 'hora_prof', 'fecha_canc', 'precioCombo',
                                'user_id_pro', 'estado', 'calle', 'referencia', 'quien_preguntar', 'clases.activa', 'horasCombo',
                                'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor',
                                'coordenadas', 'clases.descripcion', 'users.avatar', 'profesores.calificacion', 'icono')
                                ->orderBy('clases.id', 'desc')->get();
                    else
                        $clases = Clase::leftJoin('users', 'users.id', '=', 'clases.user_id_pro')
                            ->leftJoin('profesores', 'profesores.user_id', '=', 'clases.user_id_pro')
                            ->join('materias', 'clases.materia', '=', 'materias.nombre')
                            ->where('clases.user_id', $search)
                            ->whereIn('estado', ['Sin_Horas', 'Terminado', 'Calificado', 
                                                'Cancelado', 'Sin_Profesor', 'Sin_Pago', 'Pago_Rechazado'])
                            ->select('clases.id','users.name', 'materia', 'tema', 'personas', 'duracion', 'hora1', 'hora2', 
                            'clases.ubicacion', 'seleccion_profesor', 'fecha', 'hora_prof', 'fecha_canc', 'precioCombo',
                            'user_id_pro', 'estado', 'calle', 'referencia', 'quien_preguntar', 'clases.activa', 'horasCombo',
                            'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor',
                            'coordenadas', 'clases.descripcion', 'users.avatar', 'profesores.calificacion', 'icono')
                            ->orderBy('clases.id', 'desc')->get();
                }
                else
                {
                    if ($tipo == 'ACTUAL')
                        $clases = Clase::join('users', 'users.id', '=', 'clases.user_id')
                                ->join('alumnos', 'alumnos.user_id', '=', 'clases.user_id')
                                ->join('materias', 'clases.materia', '=', 'materias.nombre')
                                ->where('user_id_pro', $search)
                                ->whereIn('estado', ['Solicitado', 'Confirmado', 'Aceptado', 'Pago_Aprobado', 'Confirmando_Pago'])
                                ->select('clases.id','users.name', 'materia', 'tema', 'personas', 'duracion', 'hora1', 'hora2', 
                                'clases.ubicacion', 'seleccion_profesor', 'fecha', 'hora_prof', 'fecha_canc', 'precioCombo',
                                'user_id_pro', 'estado', 'calle', 'referencia', 'quien_preguntar', 'clases.activa', 'horasCombo',
                                'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor',
                                'coordenadas', 'clases.descripcion', 'users.avatar', 'alumnos.calificacion', 'icono')
                                ->orderBy('clases.id', 'desc')->get();
                    else
                        $clases = Clase::join('users', 'users.id', '=', 'clases.user_id')
                                ->join('alumnos', 'alumnos.user_id', '=', 'clases.user_id')
                                ->join('materias', 'clases.materia', '=', 'materias.nombre')
                                ->where('user_id_pro', $search)
                                ->whereIn('estado', ['Sin_Horas', 'Terminado', 'Calificado', 
                                                    'Cancelado', 'Sin_Profesor', 'Sin_Pago', 'Pago_Rechazado'])
                                ->select('clases.id','users.name', 'materia', 'tema', 'personas', 'duracion', 'hora1', 'hora2', 
                                'clases.ubicacion', 'seleccion_profesor', 'fecha', 'hora_prof', 'fecha_canc', 'precioCombo',
                                'user_id_pro', 'estado', 'calle', 'referencia', 'quien_preguntar', 'clases.activa', 'horasCombo',
                                'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor',
                                'coordenadas', 'clases.descripcion', 'users.avatar', 'alumnos.calificacion', 'icono')
                                ->orderBy('clases.id', 'desc')->get();
                }
                return response()->json($clases->take(100), 200);
            }
            else
                return response()->json(['error' => 'Tipo no válido'], 401);
        }
        else
            return response()->json(['error' => 'Usuario no especificado'], 401);
    }

    public function listaClasesHora()
    {
        if ( \Request::get('user_id') )
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
                        $clases = Clase::leftJoin('users', 'users.id', '=', 'clases.user_id_pro')
                                ->leftJoin('profesores', 'profesores.user_id', '=', 'clases.user_id_pro')
                                ->join('materias', 'clases.materia', '=', 'materias.nombre')
                                ->where('clases.user_id', $search)->where('fecha', '>=', $nowDate)
                                ->select('clases.id','users.name', 'materia', 'tema', 'personas', 'duracion', 'hora1', 'hora2', 
                                'clases.ubicacion', 'seleccion_profesor', 'fecha', 'hora_prof', 'fecha_canc', 'precioCombo',
                                'user_id_pro', 'estado', 'calle', 'referencia', 'quien_preguntar', 'clases.activa', 'horasCombo',
                                'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor',
                                'coordenadas', 'clases.descripcion', 'users.avatar', 'profesores.calificacion', 'icono')
                                ->orderBy('clases.id', 'desc')->get();
                    else
                        $clases = Clase::leftJoin('users', 'users.id', '=', 'clases.user_id_pro')
                            ->leftJoin('profesores', 'profesores.user_id', '=', 'clases.user_id_pro')
                            ->join('materias', 'clases.materia', '=', 'materias.nombre')
                            ->where('clases.user_id', $search)->where('fecha', '<', $nowDate)
                            ->select('clases.id','users.name', 'materia', 'tema', 'personas', 'duracion', 'hora1', 'hora2', 
                            'clases.ubicacion', 'seleccion_profesor', 'fecha', 'hora_prof', 'fecha_canc', 'precioCombo',
                            'user_id_pro', 'estado', 'calle', 'referencia', 'quien_preguntar', 'clases.activa', 'horasCombo',
                            'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor',
                            'coordenadas', 'clases.descripcion', 'users.avatar', 'profesores.calificacion', 'icono')
                            ->orderBy('clases.id', 'desc')->get();
                }
                else
                {
                    if ($tipo == 'ACTUAL')
                        $clases = Clase::join('users', 'users.id', '=', 'clases.user_id')
                                ->join('alumnos', 'alumnos.user_id', '=', 'clases.user_id')
                                ->join('materias', 'clases.materia', '=', 'materias.nombre')
                                ->where('user_id_pro', $search)->where('fecha', '>=', $nowDate)
                                ->select('clases.id','users.name', 'materia', 'tema', 'personas', 'duracion', 'hora1', 'hora2', 
                                'clases.ubicacion', 'seleccion_profesor', 'fecha', 'hora_prof', 'fecha_canc', 'precioCombo',
                                'user_id_pro', 'estado', 'calle', 'referencia', 'quien_preguntar', 'clases.activa', 'horasCombo',
                                'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor',
                                'coordenadas', 'clases.descripcion', 'users.avatar', 'alumnos.calificacion', 'icono')
                                ->orderBy('clases.id', 'desc')->get();
                    else
                        $clases = Clase::join('users', 'users.id', '=', 'clases.user_id')
                                ->join('alumnos', 'alumnos.user_id', '=', 'clases.user_id')
                                ->join('materias', 'clases.materia', '=', 'materias.nombre')
                                ->where('user_id_pro', $search)->where('fecha', '<', $nowDate)
                                ->select('clases.id','users.name', 'materia', 'tema', 'personas', 'duracion', 'hora1', 'hora2', 
                                'clases.ubicacion', 'seleccion_profesor', 'fecha', 'hora_prof', 'fecha_canc', 'precioCombo',
                                'user_id_pro', 'estado', 'calle', 'referencia', 'quien_preguntar', 'clases.activa', 'horasCombo',
                                'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor',
                                'coordenadas', 'clases.descripcion', 'users.avatar', 'alumnos.calificacion', 'icono')
                                ->orderBy('clases.id', 'desc')->get();
                }
                return response()->json($clases->take(100), 200);
            }
            else
                return response()->json(['error' => 'Tipo no válido'], 401);
        }
        else
            return response()->json(['error' => 'Usuario no especificado'], 401);
    }
    
    public function claseConfirmar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clase_id' => 'required|numeric',
            'calle' => 'required',
            'referencia' => 'required',
            'quien_preguntar' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }
        $clase = Clase::where('id', $request['clase_id'])->first();
        if ($clase != null)
        {
            if ($clase->estado == 'Pago_Aprobado' && $clase->activa)
            {
                $data['calle'] = $request['calle'];
                $data['referencia'] = $request['referencia'];
                $data['quien_preguntar'] = $request['quien_preguntar'];
                $data['estado'] = 'Aceptado';
                $actualizado = Clase::where('id', $request['clase_id'] )->update( $data );
                if(!$actualizado )
                {
                    return response()->json(['error' => 'Error al Confirmar la Clase'], 401);
                }
                else
                {
                    //enviar notificacion al profesor
                    $dateTime = date("Y-m-d H:i:s");
                    $notificacion['titulo'] = 'Clase Confirmada';
                    $notificacion['texto'] = 'Clase '.$clase->id.' confirmada en Calle: '.$request['calle']
                        .', Referencia: '.$request['referencia'].', Preguntar: '.$request['quien_preguntar']
                        .', '.$dateTime;
                    $notificacion['estado'] = 'NO';
                    $notificacion['clase_id'] = $clase->id;
                    $notificacion['tarea_id'] = 0;
                    $notificacion['chat_id'] = 0;
                    $notificacion['compra_id'] = 0;
                    $userNotif = User::where('id', $clase->user_id_pro)->first();
                    $pushClass = new NotificacionesPushFcm();
                    $pushClass->enviarNotificacion($notificacion, $userNotif);

                    return response()->json(['success' => 'Clase Confirmada'], 200);
                }  
            }
            else
            {
                return response()->json(['error' => 'Clase no se puede modificar'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'No se encontró la Clase'], 401);
        }            
    }

    public function validarPenalizacion()
    {
        if( \Request::get('user_id') )
        {
            if( \Request::get('clase_id') )
            {
                $user = \Request::get('user_id');
                $penHoras = 0 ;
                $penValor = 0;
                $dateTime = date("Y-m-d H:i:s");
                $nowDate = date_format(date_create($dateTime), "Y-m-d");
                $nowTime = date_format(date_create($dateTime), "H:i:s");
                $datos['now'] = $dateTime;
                $datos['nowDate'] = $nowDate;
                $datos['nowTime'] = $nowTime;
                $clase = Clase::where('id', \Request::get('clase_id'))->first();
                if ($clase != null)
                {
                    if ($clase->estado == 'Sin_Profesor' || $clase->estado == 'Pago_Rechazado' ||
                        $clase->estado == 'Sin_Pago' || $clase->estado == 'Terminado' || 
                        $clase->estado == 'Calificado' || $clase->activa == 0)
                        return response()->json(['error' => 'Clase no permite modificación'], 401);

                    $duracion = $clase->duracion - ($clase->personas - 1);
                    if ($duracion < 2)
                        $duracion = 2;
                    
                    //Solicitado, Confirmado, Aceptado, Confirmando_Pago, Pago_Aprobado
                    $datos['dateClass'] = $clase->fecha.' '.$clase->hora_prof;
                    if ($clase->user_id == $user)
                    {
                        $limit = date("Y-m-d H:i:s", strtotime($clase->fecha.' '.$clase->hora_prof. '-3 hours'));
                        $limitDate = date_format(date_create($limit), "Y-m-d");
                        $limitTime = date_format(date_create($limit), "H:i:s");
                        $datos['limit'] = $limit;
                        $datos['dateLimit'] = $limitDate;
                        $datos['timeLimit'] = $limitTime;
                        if (($nowDate > $limitDate) || (($nowDate == $limitDate)
                            && ($nowTime > $limitTime)))
                        {
                            //penalizar todas las horas al alumno
                            if ($clase->estado == 'Confirmado' || $clase->estado == 'Confirmando_Pago')
                            {
                                $penHoras = $duracion;
                            }
                        }
                        else 
                        {
                            //penalizar 1 hora al alumno
                            $penHoras = 1;
                        }
                    }
                    else if ($clase->user_id_pro == $user)
                    {
                        $limit = date("Y-m-d H:i:s", strtotime($clase->fecha.' '.$clase->hora_prof. '-24 hours'));
                        $limitDate = date_format(date_create($limit), "Y-m-d");
                        $limitTime = date_format(date_create($limit), "H:i:s");
                        $datos['limit'] = $limit;
                        $datos['dateLimit'] = $limitDate;
                        $datos['timeLimit'] = $limitTime;
                        if (($nowDate > $limitDate) || (($nowDate == $limitDate)
                            && ($nowTime > $limitTime)))
                        {
                            //penalizar 1 hora al profesor
                            $profeClase = Profesore::where('user_id', $clase->user_id_pro)->first();
                            $penValor = $profeClase->valor_clase;
                            $penHoras = 1;
                        }
                    }
                    else
                        return response()->json(['error' => 'Usuario no relacionado a la Clase'], 401);
                }
                else
                {
                    return response()->json(['error' => 'Clase no registrada'], 401);
                }
                return response()->json(['horas' => $penHoras, 'valor' => $penValor, 
                                            'datos' => $datos], 200);
            }
            else
            {
                return response()->json(['error' => 'Clase no especificada'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'Usuario no especificado'], 401);
        }
    }

    public function clasesGratis()
    {
        $clases = ClasesGratis::where('activa', true)
                    ->select('clases_gratis.nombre', 'clases_gratis.descripcion', 'clases_gratis.url')
                    ->get();
        return response()->json($clases, 200);
    }

    public function devuelveClase()
    {
        $search = \Request::get('clase_id');
        $clase = Clase::join('alumnos', 'alumnos.user_id', '=', 'clases.user_id')
                ->join('users', 'users.id', '=', 'clases.user_id')
                ->join('materias', 'clases.materia', '=', 'materias.nombre')
                ->leftJoin('profesores', 'profesores.user_id', '=', 'clases.user_id_pro')
                ->leftJoin('users as p', 'p.id', '=', 'clases.user_id_pro')
                ->where('clases.id', $search)
                ->select( 'clases.*', 'profesores.descripcion as profDescripcion', 'icono',
                'users.name as alumno', 'alumnos.calificacion as alumnoCalif', 'users.avatar as alumnoAvatar',
                'p.name as profesor', 'profesores.calificacion as profCalif', 'p.avatar as profAvatar')
                ->first();
        if ($clase != null)
        {
            $clase['profClases'] = 0;
            $clase['profTareas'] = 0;
            if ($clase->user_id_pro > 0)
            {
                $clases = Clase::where('user_id_pro', $clase->user_id_pro)
                            ->where('estado', 'Terminado')->get();
                $tareas = Tarea::where('user_id_pro', $clase->user_id_pro)
                            ->where('estado', 'Terminado')->get();
                $clase['profClases'] = $clases->count();
                $clase['profTareas'] = $tareas->count();
            }
            return response()->json($clase, 200);
        }
        else
        {
            return response()->json(['error' => 'Clase no Encontrada'], 401);
        }
    }

    public function seleccionarProfesor()
    {
        $user = \Request::get('user_id');
        $materia = \Request::get('materia_id');
        $clase = Clase::where('user_id', $user)->where('materia', $materia)->first();
        if ($clase != null)
            return response()->json(true, 200);
        else
            return response()->json(false, 200);
    }
}