<?php

namespace App\Http\Controllers;

use App\Materia;
use App\User;
use App\Sede;
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
            $sede = Sede::where('nombre', $request['ubicacion'])->where('activa', true)->select('ciudad')->first();
            if ($sede == null && !$combo->direccion)
            {
                return response()->json(['error' => 'La sede enviada no es válida'], 401);
            }
            $coordenadas = isset($request['coordenadas']) ? $request['coordenadas'] : NULL;
            if ($sede == null)
            {
                $sede = $request['ubicacion'];
                if ($coordenadas == null)
                    return response()->json(['error' => 'Las coordenadas deben ser especificadas'], 401);
            }
            $claseAnterior = null;
            if ($request['selProfesor']==1)
            {
                $claseAnterior = Clase::where('user_id', $request['user_id'])
                                ->whereIn('estado', ['Aceptado', 'Terminado', 'Calificado'])
                                ->orderBy('id', 'desc')->first();
                if ($claseAnterior == nul)
                    return response()->json(['error' => 'No existe una Clase anterior para seleccionar al Profesor'], 401);
            }

            $hora1 = isset($request['hora1']) ? $request['hora1'] : NULL;
            $hora2 = isset($request['hora2']) ? $request['hora2'] : NULL;
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
                'combo' => $request['combo'],
                'ubicacion' => $request['ubicacion'],
                'coordenadas' => $coordenadas,
                'estado' => 'Solicitado',
                'seleccion_profesor' => $request['selProfesor'] == 1,
                'activa' => true,
                'horasCombo' => $horasCombo,
                'precioCombo' => $precioCombo
            ]);

            if ($clase->id)
            {
                $profesores = Profesore::join('profesor_materia', 'profesor_materia.user_id', '=', 'profesores.user_id')
                                        ->join('users', 'users.id', '=', 'profesores.user_id')
                                        ->where('profesores.activo', true)
                                        ->where('profesores.clases', true)
                                        ->where('profesores.disponible', true)
                                        ->where('profesores.ciudad', $sede)
                                        ->where('profesor_materia.activa', true)
                                        ->where('profesor_materia.materia', $clase->materia)
                                        ->select('profesores.nombres', 'profesores.apellidos', 'profesores.correo', 
                                                    'users.token', 'users.sistema', 'users.id')
                                        ->get();
                if ($claseAnterior != null)
                {
                    $profSelccionado = $profesores->where('id', $claseAnterior->user_id_pro)->firts();
                    if ($profSelccionado != null)
                    {
                        $profesores = $profSelccionado;
                    }
                }
                //lanzar notificaciones a los profesores
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
                        'combo', 'ubicacion', 'seleccion_profesor', 'fecha', 'hora_prof', 'horasCombo', 'precioCombo',
                        'user_id_pro', 'estado', 'calle', 'referencia', 'quien_preguntar', 'activa', 'coordenadas',
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
                        'clases.combo', 'clases.ubicacion', 'clases.coordenadas', 'clases.seleccion_profesor')->get();
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
            'clase_id' => 'required|numeric',
            'user_id' => 'required|numeric',
            'profesor' => 'required',
            'cancelar' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }
        $clase = Clase::where('id', $request['clase_id'])->first();
        if ($clase != null)
        {
            if ($clase->estado == 'Sin_Profesor' || $clase->estado == 'Pago_Rechazado' ||
                $clase->estado == 'Sin_Pago' || $clase->estado == 'Terminado' || $clase->estado == 'Calificado')
                return response()->json(['error' => 'La Clase ya no permite modificación'], 401);
            
            if ($request['cancelar'] == 1)
            {
                $data['activa'] = false;
                $data['fecha_canc'] = date("Y-m-d H:i:s");
                $data['user_canc'] = $request['user_id'];
                $actualizado = Clase::where('id', $request['clase_id'] )->update( $data );
                if(!$actualizado )
                {
                    return response()->json(['error' => 'Ocurrió un error al terminar la Clase.'], 401);
                }
                else
                {
                    /*
                    if ($request['profesor'] == 1)
                    {
                        $dateTime = date("Y-m-d H:i:s");
                        $limit = date("Y-m-d H:i:s", strtotime(date("d/m/y H:i:s"). '-24*60 minutes'));
                        $newDate = date("Y-m-d", $dateTime);
                        $newTime = date("H:i:s", $dateTime);
                        if (!(($clase->fecha < $newDate) || (($clase->fecha == $newDate)
                            && ($clase->hora_prof <= $newTime))))
                        {
                            //penalizar 1 hora al profesor
                        }
                    }
                    else
                    {
                        $dateTime = date("Y-m-d H:i:s", strtotime(date("d/m/y H:i:s"). '-3*60 minutes'));
                        $newDate = date("Y-m-d", $dateTime);
                        $newTime = date("H:i:s", $dateTime);
                        if (($clase->fecha < $newDate) || (($clase->fecha == $newDate)
                            && ($clase->hora_prof <= $newTime)))
                        {
                            //penalizar 1 hora al profesor
                        }

                    }
                    */
                    return response()->json(['success' => 'Clase terminada exitosamente'], 200);
                }
            }
            else
            {            
                $data['estado'] = 'Terminado';
                $actualizado = Clase::where('id', $request['clase_id'] )->update( $data );
                if(!$actualizado )
                    return response()->json(['error' => 'Ocurrió un error al Finalizar la Clase.'], 401);
                else
                    return response()->json(['success' => 'Clase Finalizada exitosamente'], 200);
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
                $clases = Clase::leftJoin('users', 'users.id', '=', 'clases.user_id_pro')
                            ->where('user_id', $search)
                            ->select('clases.id','users.name', 'materia', 'tema', 'personas', 'duracion', 'hora1', 'hora2', 
                            'combo', 'ubicacion', 'seleccion_profesor', 'fecha', 'hora_prof', 'fecha_canc', 'precioCombo',
                            'user_id_pro', 'estado', 'calle', 'referencia', 'quien_preguntar', 'activa', 'horasCombo',
                            'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor',
                            'coordenadas')
                            ->get();
            }
            else
            {
                $clases = Clase::join('users', 'users.id', '=', 'clases.user_id')
                            ->where('user_id_pro', $search)
                            ->select('clases.id','users.name', 'materia', 'tema', 'personas', 'duracion', 'hora1', 'hora2', 
                            'combo', 'ubicacion', 'seleccion_profesor', 'fecha', 'hora_prof', 'fecha_canc', 'precioCombo',
                            'user_id_pro', 'estado', 'calle', 'referencia', 'quien_preguntar', 'activa', 'horasCombo',
                            'califacion_alumno', 'comentario_alumno', 'calificacion_profesor', 'comentario_profesor',
                            'coordenadas')
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