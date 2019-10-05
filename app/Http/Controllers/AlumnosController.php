<?php

namespace App\Http\Controllers;

use App\User;
use App\Tarea;
use App\Clase;
use App\Alumno;
use App\Pago;
use App\Profesore;
use App\Formulario;
use App\Notificacione;
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
            return response()->json(['error' => 'Califique de 0 a 5'], 401);
        }

        $id_tarea = isset($request['tarea_id']) ? $request['tarea_id'] : 0;
        $id_clase = isset($request['clase_id']) ? $request['clase_id'] : 0;
        if ($id_tarea== 0 && $id_clase == 0)
        {
            return response()->json(['error' => 'Especifique Tarea o Clase a calificar'], 401);
        }

        $id_usuario = $request['user_id'];
        $id_calificado = $request['user_id_calif'];
        if ($id_usuario == $id_calificado)
        {
            return response()->json(['error' => 'No puede calificar al mismo usuario'], 401);
        }
        $coment = isset($request['comment']) ? $request['comment'] : NULL;
        if ($id_tarea != 0)
        {
            $tarea = Tarea::where('id', $id_tarea)->first();
            if ($tarea != null)
            {
                if ($id_calificado == $tarea->user_id && $id_usuario == $tarea->user_id_pro)
                {
                    if ($request['calificacion'] > 0)
                        $calif = array("califacion_alumno" => $request['calificacion'], 
                                    "comentario_alumno" => $coment);
                    else
                        $calif = array("comentario_alumno" => 'No Califica. '.$coment);
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
                if ($id_calificado == $clase->user_id && $id_usuario == $clase->user_id_pro)
                {
                    if ($request['calificacion'] > 0)
                        $calif = array("califacion_alumno" => $request['calificacion'], 
                                    "comentario_alumno" => $coment);
                    else
                        $calif = array("comentario_alumno" => 'No Califica. '.$coment);
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
        return response()->json(['success' => 'Alumno Calificado'], 200);
    }
    
    public function pagarConCombo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'tarea_id' => 'required|numeric',
            'clase_id' => 'required|numeric'
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
                return response()->json(['error' => 'Tarea no Confirmada para pagar'], 401);
            }
            else if ($tarea->user_id != $request['user_id'])
            {
                return response()->json(['error' => 'Usuario no relacionado a la Tarea'], 401);
            }
            else if ($tarea->user_canc != null)
            {
                return response()->json(['error' => 'Tarea Cancelada, no puede pagar'], 401);
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
                return response()->json(['error' => 'Clase Confirmada para pagar'], 401);
            }
            else if ($clase->user_id != $request['user_id'])
            {
                return response()->json(['error' => 'Usuario no relacionado a la Clase'], 401);
            }
            else if ($clase->user_canc != null)
            {
                return response()->json(['error' => 'Clase Cancelada, no puede pagar'], 401);
            }
        }
        $user = Alumno::where('user_id', $request['user_id'])->first();
        if ($user == null)
            return response()->json(['error' => 'No se encontró Alumno para el pago'], 401);
        if (!$user->activo)
            return response()->json(['error' => 'Alumno Inactivo'], 401);

        $duracion = 0;
        if ($tarea != null)
            $duracion = $tarea->tiempo_estimado;
        if ($clase != null)
        {
            $duracion = $clase->duracion + ($clase->personas - 1);
            if ($duracion < 2)
                $duracion = 2;
        }
        if ($user->billetera < $duracion)
            return response()->json(['error' => 'Combos sin horas para pagar'], 401);

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
                            'estado' => 'Solicitado'
                            ]);
            if (!$pagoProf->id)
                return response()->json(['error' => 'Error al crear Pago al Profesor'], 401);
            
            $actTarea = Tarea::where('id', $tarea->id )->update( $data );
            if(!$actTarea )
                return response()->json(['error' => 'Error al actualizar la Tarea'], 401);
            
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
                return response()->json(['success' => 'No se pudo enviar el correo',
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
                            'valor' => ($clase->duracion * $profeClase->valor_clase) + ($clase->personas - 1),
                            'horas' => $clase->duracion,
                            'estado' => 'Solicitado'
                            ]);
            if (!$pagoProf->id)
                return response()->json(['error' => 'Error al crear Pago al Profesor'], 401);
            
            $actClase = Clase::where('id', $clase->id )->update( $data );
            if(!$actClase )
                return response()->json(['error' => 'Error al actualizar la Clase'], 401);
            
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
                return response()->json(['success' => 'No se pudo enviar el correo',
                                    'detalle' => $e->getMessage()], 200);
            }
        }
        $dataCombo['billetera'] = $user->billetera - $duracion;
        $actCombo = Alumno::where('user_id', $user->user_id )->update( $dataCombo );
        if(!$actCombo )
            return response()->json(['error' => 'Error al actualizar Pago'], 401);
                
        return response()->json(['success' => 'Pago con Combo exitoso'], 200);
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
            return response()->json(['error' => 'Disponibilidad de Clases Inválida'], 401);
        }
        if ($request['tareas'] != 0 && $request['tareas'] != 1)
        {
            return response()->json(['error' => 'Disponibilidad de Tareas Inválida'], 401);
        }
        if ($request['clases'] == 0 && $request['tareas'] == 0)
        {
            return response()->json(['error' => 'Especifique Disponibilidad (Clases o Tareas)'], 401);
        }
        $alumno = Alumno::where('user_id', $request['user_id'])->first();
        if ($alumno == null)
        {
            return response()->json(['error' => 'No es Alumno para solicitar ser Profesor'], 401);
        }
        $solicitud = Formulario::where('user_id', $request['user_id'])->where('estado', 'Solicitado')->first();
        if ($solicitud != null)
        {
            return response()->json(['error' => 'Ya tiene una solicitud en Proceso'], 401);
        }

        $data['ser_profesor'] = true;
        $act = Alumno::where('user_id', $request['user_id'])->update($data);
        if(!$act )
        {
            return response()->json(['error' => 'Error al actualizar el Alumno'], 401);
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
            return response()->json(['error' => 'Error al registrar solicitud!'], 401);
        }
        return response()->json(['success' => 'Solicitud ser Profesor realizada'], 200);
    }

    public function alumnoHeader()
    {
        $search = \Request::get('user_id');
        $alumno = Alumno::where('user_id', $search)->first();
        if ($alumno != null)
        {
            $clases = Clase::where('user_id', $search)
                        ->select('clases.id', 'clases.materia', 'clases.personas', 
                        'clases.duracion', 'clases.fecha')->get();
            $tareas = Tarea::where('user_id', $search)
                        ->select('tareas.id', 'tareas.materia', 'tareas.tiempo_estimado', 'tareas.fecha_entrega')->get();
            $respuesta['clases'] = $clases->count();
            $respuesta['tareas'] = $tareas->count();
            $respuesta['horas'] = $alumno->billetera;
            $respuesta['ranking'] = $alumno->calificacion == null ? 5 : $alumno->calificacion;
            return response()->json($respuesta, 200);
        }
        else
            return response()->json(['error' => 'Alumno Incorrecto'], 401);
    }

    public function calificacionPendiente()
    {
        $respuesta['tarea_id'] = 0;
        $respuesta['clase_id'] = 0;
        $respuesta['tarea'] = null;
        $respuesta['clase'] = null;
        $search = \Request::get('user_id');
        $clase = Clase::join('users', 'users.id', '=', 'clases.user_id_pro')
                ->join('materias', 'clases.materia', '=', 'materias.nombre')
                ->where('user_id', $search)->where('estado', 'Terminado')
                ->where('calificacion_profesor', null)->where('comentario_profesor', null)
                ->select('clases.id', 'clases.fecha', 'clases.hora_prof', 'clases.materia', 'clases.tema',
                        'clases.estado', 'clases.user_id_pro', 'users.name', 'users.avatar', 'materias.icono')->first();
        if ($clase != null)
        {
            $respuesta['clase_id'] = $clase->id;
            $respuesta['clase'] = $clase;
        }
        else
        {
            $tarea = Tarea::join('users', 'users.id', '=', 'tareas.user_id_pro')
                    ->join('materias', 'tareas.materia', '=', 'materias.nombre')
                    ->where('user_id', $search)->where('estado', 'Terminado')
                    ->where('calificacion_profesor', null)->where('comentario_profesor', null)
                    ->select('tareas.id', 'tareas.fecha_entrega', 'tareas.materia', 'tareas.tema',
                            'tareas.estado', 'tareas.user_id_pro', 'users.name', 'users.avatar', 'materias.icono')->first();
            if ($tarea != null)
            {
                $respuesta['tarea_id'] = $tarea->id;
                $respuesta['tarea'] = $tarea;
            }
        }
        return response()->json($respuesta, 200);
    }

    public function listadoNotificaciones()
    {
        $search = \Request::get('user_id');
        $respuesta = Notificacione::where('user_id', $search)
                                     ->orderBy('id', 'desc')->get();
        $data['leida'] = true;
        foreach($respuesta->where('leida', false) as $item)
            Notificacione::where('id', $item->id )->update( $data );

        return response()->json($respuesta->take(100), 200);
    }

    public function devuelveDisponible()
    {
        $search = \Request::get('user_id');
        $alumno = Alumno::where('user_id', $search)->first();
        $respuesta = $alumno != null ? $alumno->activo : false;
        return response()->json($respuesta, 200);
    }

    public function nuevasNotificaciones()
    {
        $search = \Request::get('user_id');
        $respuesta = Notificacione::where('user_id', $search)->where('leida', false)
                                     ->orderBy('id', 'desc')->get();
        return response()->json($respuesta->count() > 0, 200);
    }
}