<?php

namespace App\Http\Controllers;

use App\Materia;
use App\User;
use App\Alumno;
use App\Profesore;
use App\Clase;
use App\Combo;
use App\ClaseEjercicio;
use Illuminate\Http\Request;
use Validator;
use Hash;

/* ESTADOS CLASE

Solicitado
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
                'selProfesor' => 'required',
                'combo' => 'required'
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
            if (($alumno == null) || ($alumno != null && !$alumno->activo) )
            {
                return response()->json([ 'error' => 'El usuario no puede solicitar Clase'], 401);
            }
            $materia = Materia::where('nombre', '=', $request['materia'] )->first();
            if ($materia == null)
            {
                return response()->json(['error' => 'La Materia enviada no es válida'], 401);
            }
            $combo = Combo::where('nombre', '=', $request['combo'] )->first();
            if ($combo == null)
            {
                return response()->json(['error' => 'El Combo enviado no es válido'], 401);
            }
            $claseAnterior = Clase::where('user_id', $request['user_id'])
                            ->whereIn('estado', ['Aceptado', 'Terminado', 'Calificado'])
                            ->orderBy('id', 'desc')->first();
            if ($request['selProfesor']==1 && $claseAnterior == nul)
            {
                return response()->json(['error' => 'No existe una Clase anterior para seleccionar al Profesor'], 401);
            }

            $hora1 = isset($request['hora1']) ? $request['hora1'] : NULL;
            $hora2 = isset($request['hora2']) ? $request['hora2'] : NULL;
            $clase = Clase::create([
                'user_id' => $request['user_id'],
                'materia' => $request['materia'],
                'tema' => $request['tema'],
                'fecha' => $request['fecha'],
                'hora1' => $hora1,
                'hora2' => $hora2,
                'personas' => $request['personas'],
                'duracion' => $request['duracion'],
                'combo' => $request['combo'],
                'ubicacion' => $request['ubicacion'],
                'estado' => 'Solicitado',
                'seleccion_profesor' => $request['selProfesor'] == 1,
                'activa' => true
            ]);

            if ($clase->id)
            {
                if ($request['selProfesor'] == 0)
                {
                    //lanzar notificaciones a todos los profesores
                }
                else
                {
                    //lanzar notificaciones al profesor de la clase anterior
                    //$claseAnterior
                }
                return response()->json(['success'=> 'Su Clase ha sido solicitada. Por favor espera que validemos su información'], 200);
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

    public function claseActiva()
    {
        if( \Request::get('user_id') )
        {
            $search = \Request::get('user_id');
            $clase = Clase::where('user_id', $search)
                        ->where('activa', true)
                        ->select('id', 'user_id', 'materia', 'tema', 'personas', 'duracion', 'hora1', 'hora2', 
                        'combo', 'ubicacion', 'seleccion_profesor', 'fecha', 'hora_prof',
                        'user_id_pro', 'estado', 'calle', 'referencia', 'quien_preguntar', 'activa', 
                        'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor')
                        ->first();
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
                        ->join('profesor_combo', function ($join) {
                            $join->on('profesor_combo.user_id', '=', 'profesores.user_id');
                                 $join->on('clases.combo', '=', 'profesor_combo.combo');})
                        ->where('clases.estado', 'Solicitado')
                        ->where('profesores.user_id', $search)
                        ->where('profesores.activo', true)
                        ->where('profesor_materia.activa', true)
                        ->where('profesor_combo.activo', true)
                        ->where('profesores.clases', true)
                        ->select('clases.id', 'clases.user_id', 'clases.materia', 'clases.tema', 
                        'clases.personas', 'clases.duracion', 'clases.hora1', 'clases.hora2', 'fecha',
                        'clases.combo', 'clases.ubicacion', 'clases.seleccion_profesor')->get();
            return response()->json($clases, 200);
        }
        else
        {
            return response()->json(['error' => 'Usuario no especificado'], 401);
        }
    }
    
    public function claseTerminar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'clase_id' => 'required|numeric'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }
        $clase = Clase::where('id', $request['clase_id'])->first();
        if ($clase != null)
        {
            $data['activa'] = false;
            $data['fecha_canc'] = date("Y-m-d H:i:s");
            $actualizado = Clase::where('id', $request['clase_id'] )->update( $data );
            if(!$actualizado )
            {
                return response()->json(['error' => 'Ocurrió un error al terminar la Clase.'], 401);
            }
            else
            {
                return response()->json(['success' => 'Clase terminada exitosamente'], 200);
            }  
        }
        else
        {
            return response()->json(['error' => 'No se encontró la Clase'], 401);
        }            
    }
    
    public function listaClases()
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
                $clases = Clase::join('users', 'users.id', '=', 'clases.user_id_pro')
                            ->where('user_id', $search)
                            ->select('clases.id','users.name', 'materia', 'tema', 'personas', 'duracion', 'hora1', 'hora2', 
                            'combo', 'ubicacion', 'seleccion_profesor', 'fecha', 'hora_prof', 'fecha_canc',
                            'user_id_pro', 'estado', 'calle', 'referencia', 'quien_preguntar', 'activa', 
                            'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor')
                            ->get();
            }
            else
            {
                $clases = Clase::join('users', 'users.id', '=', 'clases.user_id_pro')
                            ->where('user_id_pro', $search)
                            ->select('clases.id','users.name', 'materia', 'tema', 'personas', 'duracion', 'hora1', 'hora2', 
                            'combo', 'ubicacion', 'seleccion_profesor', 'fecha', 'hora_prof', 'fecha_canc',
                            'user_id_pro', 'estado', 'calle', 'referencia', 'quien_preguntar', 'activa', 
                            'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor')
                            ->get();
            }
            return response()->json($clases, 200);
        }
        else
        {
            return response()->json(['error' => 'Usuario no especificado'], 401);
        }
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
                    return response()->json(['error' => 'Ocurrió un error al Confirmar la Clase.'], 401);
                }
                else
                {
                    return response()->json(['success' => 'Clase confirmada exitosamente'], 200);
                }  
            }
            else
            {
                return response()->json(['error' => 'La Clase no puede ser modificada'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'No se encontró la Clase'], 401);
        }            
    }
}