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
Cancelado
Sin_Profesor
Sin_Pago

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
                'estado' => 'Solicitado'
            ]);

            if( $tarea->id)
            {
                //lanzar notificaciones profesores
                return response()->json(['success'=> 'Su tarea ha sido solicitada. Por favor espera que validemos su información'], 200);
            }
            else
            {
                return response()->json(['error' => 'Lo sentimos, ocurrió un error al registrar!'], 401);
            }

            // send email
            /*
            Mail::queue('emails.verify', $data, function($message) use ($data) {
                $message->to($data['email'])->subject('Verify your email address');
            });*/
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
            $tarea = Tarea::where('user_id', $search)->where('estado', '!=', 'Terminado')
                        ->where('estado', '!=', 'Cancelado')
                        ->where('estado', '!=', 'Sin_Profesor')->where('estado', '!=', 'Sin_Pago')
                        ->select('user_id', 'materia', 'tema', 'fecha_entrega', 'hora_inicio', 'hora_fin', 
                        'descripcion', 'formato_entrega', 'estado', 'user_id_pro', 'tiempo_estimado', 'inversion', 
                        'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor')
                        ->first();
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

    
    public function subirEjercicio(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'tarea_id' => 'required|numeric'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }
        $drive = isset($request['drive']) ? trim($request['drive']) : NULL;
        $archivo = isset($request['archivo']) ? trim($request['archivo']) : NULL;
        if (($drive == NULL) && ($archivo == NULL))
        {
            return response()->json(['error' => 'Archivo con ejercicio sin especificar'], 401);
        }
        $tarea = Tarea::where('id', $request['tarea_id'])->first();
        if ($tarea == null)
        {
            return response()->json(['error' => 'No existe la tarea'], 401);
        }
        else if ($tarea->estado != 'Aceptado')
        {
            return response()->json(['error' => 'La Tarea no se encuentra Pagada'], 401);
        }
        else if (($tarea->user_id != $request['user_id']) && ($tarea->user_id_pro != $request['user_id']))
        {
            return response()->json(['error' => 'El usuario no tiene relación con la Tarea'], 401);
        }
        $alumno = Alumno::where('user_id', $request['user_id'])->first();
        $profesor = Profesore::where('user_id', $request['user_id'])->first();
        if (($alumno != null) || ($profesor != null))
        {
            if ((($alumno != null) && ($alumno->activo)) || (($profesor != null) && ($profesor->activo)))
            {
                if ($archivo != NULL)
                {
                    $file = $request->file('archivo');
                    $nombre = $file->getClientOriginalName();
                    \Storage::disk('local')->put($request['user_id'].'\\'.$nombre,  \File::get($file));
                }
                $aplica = TareaEjercicio::create([
                    'user_id' => $request['user_id'],
                    'tarea_id' => $request['tarea_id'],
                    'archivo' => $nombre,
                    'drive' => $drive
                ]);
                if (!$aplica->id)
                {
                    return response()->json(['error' => 'Ocurrió un error al registrar solicitud!'], 401);
                }
                return response()->json(['success' => 'Ejercicio guardado exitosamente'], 200);
            }
            else
            {
                return response()->json(['error' => 'El usuario no se encuentra activo'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'No se encontró al Usuario para subir el Ejercicio'], 401);
        }
    }
}