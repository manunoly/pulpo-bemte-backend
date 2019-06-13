<?php

namespace App\Http\Controllers;

use App\Materia;
use App\User;
use App\Alumno;
use App\Profesore;
use App\Tarea;
use App\TareaEjercicio;
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
                'activa' => true
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
                                    ->select('profesores.nombres', 'profesores.apellidos', 'profesores.correo', 
                                                'users.token', 'users.sistema')
                                    ->get();

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
                        ->where('activa', true)
                        ->select('id','user_id', 'materia', 'tema', 'fecha_entrega', 'hora_inicio', 'hora_fin', 
                        'descripcion', 'formato_entrega', 'estado', 'user_id_pro', 'tiempo_estimado', 'inversion', 
                        'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor')
                        ->first();
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
                        'tareas.descripcion', 'tareas.formato_entrega')->get();
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
            'tarea_id' => 'required|numeric'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }
        $tarea = Tarea::where('id', $request['tarea_id'])->first();
        if ($tarea != null)
        {
            $data['activa'] = false;
            $data['fecha_canc'] = date("Y-m-d H:i:s");
            $actualizado = Tarea::where('id', $request['tarea_id'] )->update( $data );
            if(!$actualizado )
            {
                return response()->json(['error' => 'Ocurrió un error al terminar la tarea.'], 401);
            }
            else
            {
                return response()->json(['success' => 'Tarea terminada exitosamente'], 200);
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
                            'descripcion', 'formato_entrega', 'estado', 'user_id_pro', 'tiempo_estimado', 'inversion', 
                            'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor', 'fecha_canc')
                            ->get();
            }
            else
            {
                $tareas = Tarea::join('users', 'users.id', '=', 'tareas.user_id')
                            ->where('user_id_pro', $search)
                            ->select('tareas.id','users.name', 'materia', 'tema', 'fecha_entrega', 'hora_inicio', 'hora_fin', 
                            'descripcion', 'formato_entrega', 'estado', 'user_id_pro', 'tiempo_estimado', 'inversion', 
                            'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor', 'fecha_canc')
                            ->get();
            }
            return response()->json($tareas, 200);
        }
        else
        {
            return response()->json(['error' => 'Usuario no especificado'], 401);
        }
    }
}