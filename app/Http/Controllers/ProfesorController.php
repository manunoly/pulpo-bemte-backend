<?php

namespace App\Http\Controllers;

use App\Tarea;
use App\Clase;
use App\Pago;
use App\Multa;
use App\Profesore;
use App\Alumno;
use App\User;
use App\Bemte;
use App\TareaProfesor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Validator;
use App\Mail\Notificacion;
use App\Mail\NotificacionClases;
use App\NotificacionesPushFcm;
use App\Algoritmo;

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
                return response()->json(['success' => 'Tarea Actualizada'], 200);
            }
            else
            {
                return response()->json(['error' => 'Ocurrió un error al actualizar'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'No se encontró el Usuario'], 401);
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
                return response()->json(['success' => 'Clase Actualizada'], 200);
            }
            else
            {
                return response()->json(['error' => 'Ocurrió un error al actualizar'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'No se encontró el Usuario'], 401);
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
                return response()->json(['success' => 'Estado Actualizado'], 200);
            }
            else
            {
                return response()->json(['error' => 'Ocurrió un error al actualizar'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'No se encontró el Usuario'], 401);
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
            return response()->json(['error' => 'La calificación debe ser de 0 a 5'], 401);
        }

        $id_tarea = isset($request['tarea_id']) ? $request['tarea_id'] : 0;
        $id_clase = isset($request['clase_id']) ? $request['clase_id'] : 0;
        if ($id_tarea == 0 && $id_clase == 0)
        {
            return response()->json(['error' => 'Especifique Tarea o Clase a calificar'], 401);
        }

        $id_usuario = $request['user_id'];
        $id_calificado = $request['user_id_calif'];
        if ($id_usuario == $id_calificado)
        {
            return response()->json(['error' => 'No puede Calificar al mismo Usuario'], 401);
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
                    return response()->json(['error' => 'Usuarios no coinciden con la Tarea'], 401);
                }
            }
            else
            {
                return response()->json(['error' => 'No se encontró Tarea a calificar'], 401);
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
                    return response()->json(['error' => 'Usuarios no coinciden con la Clase'], 401);
                }
            }
            else
            {
                return response()->json(['error' => 'No se encontró Clase a calificar'], 401);
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
        return response()->json(['success' => 'Profesor Calificado'], 200);
    }

    public function aplicarTarea(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'tarea_id' => 'required',
            'tiempo' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        } 
        
        if (!is_numeric($request['tiempo']) || $request['tiempo'] <= 0)
        {
            return response()->json(['error' => 'Especifique Tiempo de la Tarea'], 401);
        }

        $tarea = Tarea::where('id', $request['tarea_id'])->first();
        if ($tarea != null)
        {
            if ($tarea->estado == 'Solicitado' && $tarea->user_id_pro == null)
            {
                $profe = Profesore::where('user_id', $request['user_id'])->first();
                if ($profe != null)
                {
                    if ($profe->activo && $profe->disponible && $profe->tareas)
                    {
                        $solicitud = TareaProfesor::where('tarea_id', $request['tarea_id'])->where('user_id', $request['user_id'])->first();
                        $valor_tarea = Bemte::select('valorTarea')->first();
                        if ($solicitud == null)
                        {
                            $aplicados = TareaProfesor::where('tarea_id', $request['tarea_id'])->where('estado', 'Solicitado')->count();
                            if ( 5 > $aplicados)
                            {
                                $aplica = TareaProfesor::create([
                                    'user_id' => $request['user_id'],
                                    'tarea_id' => $request['tarea_id'],
                                    'inversion' => $request['tiempo'] * $valor_tarea->valorTarea,
                                    'tiempo' => $request['tiempo'],
                                    'estado' => 'Solicitado'
                                ]);
                                if ($aplica->id)
                                {
                                    if ($aplicados == 4)
                                    {
                                        $algoritmo = new Algoritmo();
                                        $algoritmo->AsignarProfesorTarea($tarea);
                                    }
                                    return response()->json(['success' => 'Tarea Solicitada'], 200);
                                }
                                else
                                {
                                    return response()->json(['error' => 'Error al registrar solicitud!'], 401);
                                }
                            }
                            else
                            {
                                return response()->json(['error' => 'Solicitudes completas para la Tarea'], 401);
                            }
                        }
                        else
                        {
                            $data['inversion'] = $request['tiempo'] * $valor_tarea;
                            $data['tiempo'] = $request['tiempo'];
                            $data['estado'] = 'Solicitado';
                            $actualizado = TareaProfesor::where('id', $solicitud->id )->update( $data );
                            if($actualizado)
                            {
                                return response()->json(['success' => 'Tarea Solicitada'], 200);
                            }
                            else
                            {
                                return response()->json(['error' => 'La Solicitud no se pudo actualizar'], 401);
                            }
                        }
                    }
                    else
                    {
                        return response()->json(['error' => 'Profesor no disponible para la tarea'], 401);
                    }
                }
                else
                {
                    return response()->json(['error' => 'No se encontró Profesor para aplicar'], 401);
                }
            }
            else if ($tarea->user_id_pro == null)
                return response()->json(['error' => 'Ya no puede aplicar a la Tarea'], 401);
            else
                return response()->json(['error' => 'La Tarea ya ha sido Asignada'], 401);
        }
        else
        {
            return response()->json(['error' => 'No se encontró Tarea para aplicar'], 401);
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
            if ($clase->estado == 'Solicitado' && $clase->user_id_pro == null)
            {
                if (($clase->seleccion_profesor && $clase->user_pro_sel == $request['user_id'])
                    || (!$clase->seleccion_profesor))
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
                            $estado  = 'Por favor, realizar el pago de la Clase.';
                            if ($clase->compra_id == 0)
                            {
                                //quitar las horas al alumno
                                $alumno = Alumno::where('user_id', $clase->user_id)->first();
                                $dataAlumno['billetera'] = $alumno->billetera - $duracion;
                                $actualizado = Alumno::where('user_id', $clase->user_id )->update( $dataAlumno );
                                if (!$actualizado)
                                    return response()->json(['error' => 'Error al actualizar Billetera del Alumno'], 401);
                                $data['estado'] = 'Aceptado';
                                $estado = 'La Clase ha sido asignada.';
                                //pagar al profesor
                                $pagoProf = Pago::create([
                                            'user_id' => $profe->user_id,
                                            'tarea_id' => 0,
                                            'clase_id' => $clase->id,
                                            'valor' => ($clase->duracion + ($clase->personas - 1)) * $profe->valor_clase,
                                            'horas' => $clase->duracion,
                                            'estado' => 'Solicitado',
                                            'valorTotal' => 0,
                                            'calculoValor' => 0,
                                            'valorPendiente' => 0
                                            ]);
                                if (!$pagoProf->id)
                                    return response()->json(['error' => 'No se pudo crear pago al Profesor'], 401);                            
                                $userProf = User::where('id', $profe->user_id)->first();
                                try 
                                {
                                    Mail::to($userAlumno->email)->send(new NotificacionClases($clase,  '', '', '', $userAlumno->name, $userProf->name, 
                                                                    env('EMPRESA'), true));
                                    Mail::to($userProf->email)->send(new NotificacionClases($clase,  '', '', '', $userAlumno->name, $userProf->name, 
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
                                $notificacion['clase_id'] = $clase->id;
                                $notificacion['tarea_id'] = 0;
                                $notificacion['chat_id'] = 0;
                                $notificacion['compra_id'] = 0;
                                $notificacion['color'] = "alumno";
                                $notificacion['titulo'] = 'Clase Confirmada';
                                $notificacion['texto'] = 'La Clase de '.$clase->materia.', '.$clase->tema.', ha sido confirmada por el profesor '
                                        .$profe->nombres.' '.$profe->apellidos.'. Por favor, procede a realizar el pago.';
                                $notificacion['estado'] = $estado;
                                $pushClass = new NotificacionesPushFcm();
                                $pushClass->enviarNotificacion($notificacion, $userAlumno);
    
                                return response()->json(['success' => 'Clase Solicitada'], 200);
                            }
                            else
                                return response()->json(['error' => 'La Solicitud no se pudo actualizar'], 401);
                        }
                        else
                            return response()->json(['error' => 'Profesor no disponible para la clase'], 401);
                    }
                    else
                        return response()->json(['error' => 'No se encontró Profesor para aplicar'], 401);
                }
                else
                    return response()->json(['error' => 'Clase con Selección de Profesor, intente en unos minutos'], 401);
            }
            else if  ($clase->user_id_pro == null)
                return response()->json(['error' => 'Ya no puede aplicar a la Clase'], 401);
            else
                return response()->json(['error' => 'La Clase ya ha sido Asignada'], 401);
        }
        else
            return response()->json(['error' => 'No se encontró Clase para aplicar'], 401);
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
                return response()->json(['success' => 'Cuenta Actualizada'], 200);
            }
            else
            {
                return response()->json(['error' => 'Ocurrió un error al actualizar'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'No se encontró el Usuario'], 401);
        }
    }

    public function cuentaProfesor()
    {
        if( \Request::get('user_id') )
        {
            $search = \Request::get('user_id');
            $tipo = \Request::get('tipo');
            if ($tipo == 'MULTAS')
            {
                $multas = Multa::leftjoin('tareas', 'multas.tarea_id', '=', 'tareas.id')
                            ->leftjoin('clases', 'multas.clase_id', '=', 'clases.id')
                            ->leftjoin('materias as clasesMateria', 'clasesMateria.nombre', '=', 'clases.materia')
                            ->leftjoin('materias as tareasMateria', 'tareasMateria.nombre', '=', 'tareas.materia')
                            ->where('multas.user_id', $search)
                            ->where('multas.estado', 'Solicitado')
                            ->select('multas.id', 'multas.clase_id', 'multas.tarea_id', 'multas.valor', 
                            'multas.comentario', 'multas.created_at', 'multas.estado',
                            DB::raw('(CASE WHEN multas.tarea_id = 0 THEN clases.materia ELSE tareas.materia END) AS materia'),
                            DB::raw('(CASE WHEN multas.tarea_id = 0 THEN clases.tema ELSE tareas.tema END) AS tema'),
                            DB::raw('(CASE WHEN multas.tarea_id = 0 THEN clasesMateria.icono ELSE tareasMateria.icono END) AS icono'),
                            DB::raw('(CASE WHEN multas.tarea_id = 0 THEN clases.fecha ELSE tareas.fecha_entrega END) AS fecha'),
                            DB::raw('(CASE WHEN multas.tarea_id = 0 THEN clases.duracion ELSE TIMESTAMPDIFF(hour, tareas.hora_inicio, tareas.hora_fin) END) AS duracion'),
                            DB::raw('(CASE WHEN multas.tarea_id = 0 THEN TIME_FORMAT(clases.hora1, "%H:%i") ELSE TIME_FORMAT(tareas.hora_inicio, "%H:%i") END) AS hora1')
                            )->orderBy('fecha', 'desc')->orderBy('hora1', 'desc')->get();
                $respuesta['total'] = $multas->sum('valor');
                $respuesta['data'] = $multas;
            }
            else if ($tipo == 'TAREAS')
            {
                $tareas = Pago::join('tareas', 'pagos.tarea_id', '=', 'tareas.id')
                            ->join('materias', 'materias.nombre', '=', 'tareas.materia')
                            ->where('pagos.estado', 'Solicitado')
                            ->where('pagos.user_id', $search)
                            ->where('tareas.user_id_pro', $search)
                            ->select('tareas.id', 'tareas.materia', 'tareas.tema', 
                            DB::raw('TIME_FORMAT(tareas.hora_inicio, "%H:%i") as hora1'), 
                            DB::raw('TIME_FORMAT(tareas.hora_fin, "%H:%i") as hora2'), 
                            DB::raw('TIMESTAMPDIFF(hour, tareas.hora_inicio, tareas.hora_fin) as duracion'), 
                            'tareas.fecha_entrega as fecha', 'pagos.valor', 'pagos.created_at', 'pagos.horas',
                            'pagos.estado as pago', 'tareas.estado', 'materias.icono')
                            ->orderBy('fecha', 'desc')->orderBy('hora1', 'desc')->get();
                $respuesta['total'] = $tareas->sum('valor');
                $respuesta['data'] = $tareas;
            }
            else
            {
                $clases = Pago::join('clases', 'pagos.clase_id', '=', 'clases.id')
                            ->join('materias', 'materias.nombre', '=', 'clases.materia')
                            ->where('pagos.estado', 'Solicitado')
                            ->where('pagos.user_id', $search)
                            ->where('clases.user_id_pro', $search)
                            ->select('clases.id', 'clases.materia', 'clases.tema', 
                            DB::raw('TIME_FORMAT(clases.hora1, "%H:%i") as hora1'),
                            'clases.duracion', 'clases.fecha', 'pagos.horas', 'clases.estado',
                            'pagos.valor', 'pagos.created_at', 'pagos.estado as pago', 'materias.icono')
                            ->orderBy('fecha', 'desc')->orderBy('hora1', 'desc')->get();
                $respuesta['total'] = $clases->sum('valor');
                $respuesta['data'] = $clases;
            }
            return response()->json($respuesta, 200);
        }
        else
        {
            return response()->json(['error' => 'Usuario no especificado'], 401);
        }
    }

    public function calificacionPendiente()
    {
        $respuesta['tarea_id'] = 0;
        $respuesta['clase_id'] = 0;
        $respuesta['tarea'] = null;
        $respuesta['clase'] = null;
        $search = \Request::get('user_id');
        $clase = Clase::join('users', 'users.id', '=', 'clases.user_id')
                ->join('materias', 'clases.materia', '=', 'materias.nombre')
                ->join('profesores', 'profesores.user_id', '=', 'clases.user_id_pro')
                ->join('alumnos', 'alumnos.user_id', '=', 'clases.user_id')
                ->where('clases.user_id_pro', $search)->where('clases.estado', 'Terminado')
                ->where('califacion_alumno', null)->where('comentario_alumno', null)
                ->select('clases.id', 'clases.fecha', 'clases.hora_prof', 'clases.materia', 'clases.tema',
                        'clases.estado', 'clases.user_id', 'users.name', 'users.avatar', 'materias.icono',
                        'alumnos.apodo AS apodoAlumno', 'profesores.apodo AS apodoProfesor')->first();
        if ($clase != null)
        {
            $respuesta['clase_id'] = $clase->id;
            $respuesta['clase'] = $clase;
        }
        else
        {
            $tarea = Tarea::join('users', 'users.id', '=', 'tareas.user_id')
                    ->join('materias', 'tareas.materia', '=', 'materias.nombre')
                    ->join('profesores', 'profesores.user_id', '=', 'tareas.user_id_pro')
                    ->join('alumnos', 'alumnos.user_id', '=', 'tareas.user_id')
                    ->where('tareas.user_id_pro', $search)->where('tareas.estado', 'Terminado')
                    ->where('califacion_alumno', null)->where('comentario_alumno', null)
                    ->select('tareas.id', 'tareas.fecha_entrega', 'tareas.materia', 'tareas.tema',
                            'tareas.estado', 'tareas.user_id', 'users.name', 'users.avatar', 'materias.icono',
                            'alumnos.apodo AS apodoAlumno', 'profesores.apodo AS apodoProfesor')->first();
            if ($tarea != null)
            {
                $respuesta['tarea_id'] = $tarea->id;
                $respuesta['tarea'] = $tarea;
            }
        }
        return response()->json($respuesta, 200);
    }

    public function devuelveDisponible()
    {
        $search = \Request::get('user_id');
        $profesor = Profesore::where('user_id', $search)->first();
        $respuesta = $profesor != null ? $profesor->disponible : false;
        return response()->json($respuesta, 200);
    }

    public function profesorHeader()
    {
        $search = \Request::get('user_id');
        $profesor = Profesore::where('user_id', $search)->first();
        if ($profesor != null)
        {
            $clases = Clase::where('user_id_pro', $search)->where('user_canc', null)
                        ->select('clases.id', 'clases.materia', 'clases.personas', 
                        'clases.duracion', 'clases.fecha')->get();
            $tareas = Tarea::where('user_id_pro', $search)->where('user_canc', null)
                        ->select('tareas.id', 'tareas.materia', 'tareas.tiempo_estimado', 'tareas.fecha_entrega')->get();
            $respuesta['clases'] = $clases->count();
            $respuesta['tareas'] = $tareas->count();
            $respuesta['ranking'] = $profesor->calificacion == null ? 5 : $profesor->calificacion;
            
            //calcular ganancias
            $clases = Pago::join('clases', 'pagos.clase_id', '=', 'clases.id')
                        ->where('pagos.user_id', $search)
                        ->where('clases.user_id_pro', $search)
                        ->where('pagos.estado', 'Solicitado')
                        ->select('clases.id', 'clases.materia', 'clases.tema', 
                        'clases.duracion', 'clases.fecha', 'pagos.horas', 'clases.estado',
                        'pagos.valor', 'pagos.created_at', 'pagos.estado as pago')->get();
            $tareas = Pago::join('tareas', 'pagos.tarea_id', '=', 'tareas.id')
                        ->where('pagos.user_id', $search)
                        ->where('tareas.user_id_pro', $search)
                        ->where('pagos.estado', 'Solicitado')
                        ->select('tareas.id', 'tareas.materia', 'tareas.tema',  'pagos.horas',
                        'tareas.fecha_entrega', 'pagos.valor', 'pagos.created_at', 
                        'pagos.estado as pago', 'tareas.estado')->get();
            $multas = Multa::where('multas.user_id', $search)
                        ->where('estado', 'Solicitado')
                        ->select('multas.clase_id', 'multas.tarea_id', 'multas.valor', 
                        'multas.comentario', 'multas.created_at', 'multas.estado')->get();
            $respuesta['ganancia'] = $clases->sum('valor') + $tareas->sum('valor')
                                    - $multas->sum('valor');
            
            return response()->json($respuesta, 200);
        }
        else
            return response()->json(['error' => 'Profesor Incorrecto'], 401);
    }

    public function verificaHorarioCoincide()
    {
        $respuesta = true;
        $userID = \Request::get('user_id');
        $searchClase = \Request::get('clase_id');
        $clase = Clase::where('id', $searchClase)->first();
        
        if ($clase != null)
        {
            $horaInicio = $clase->hora_hora1;
            //$horaInicio = date("Y-m-d H:i:s", strtotime($clase->hora1. '-20 minutes'));
            $horaFin = date("H:i:s", strtotime($clase->hora1.$clase->duracion.' hours'));
            //$horaFin = date("H:i:s", strtotime($clase->hora1. '20 minutes'));

            $coincide = Clase::where('id', '!=', $clase->id)->where('user_id_pro', $userID)
                            ->where('fecha', $clase->fecha)
                            ->whereIn('estado', ['Confirmado','Aceptado', 'Confirmando_Pago', 'Pago_Aprobado'])
                            ->get();
            foreach($coincide as $item)
            {
                $hora = date("H:i:s", strtotime($item->hora_prof.$item->duracion.' hours'));
                if ($item->hora_prof >= $horaInicio && $item->hora_prof <= $horaFin
                    || $hora >= $horaInicio && $hora <= $horaFin)
                {
                    $respuesta = false;
                    break;
                }
            }
        }
        else
        {
            $searchTarea = \Request::get('tarea_id');
            $tarea = Tarea::where('id', $searchTarea)->first();
            if ($tarea != null)
            {
                $coincide = Tarea::where('id', '!=', $tarea->id)->where('user_id_pro', $userID)
                                ->where('fecha_entrega', $tarea->fecha_entrega)
                                ->whereIn('estado', ['Confirmado','Aceptado', 'Confirmando_Pago', 'Pago_Aprobado'])
                                ->get();
                foreach($coincide as $item)
                {
                    if ($item->hora_inicio >= $tarea->hora_inicio && $item->hora_inicio <= $tarea->hora_fin
                        || $item->hora_fin >= $tarea->hora_inicio && $item->hora_fin <= $tarea->hora_fin)
                    {
                        $respuesta = false;
                        break;
                    }  
                }
            }
        }
        
        return response()->json($respuesta, 200);
    }
}