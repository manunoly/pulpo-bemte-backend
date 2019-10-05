<?php

namespace App\Http\Controllers;

use App\Clase;
use App\Tarea;
use App\Combo;
use App\CombosHora;
use App\Alumno;
use App\User;
use App\Profesore;
use App\AlumnoCompra;
use App\AlumnoBilletera;
use Illuminate\Http\Request;
use Validator;
use App\NotificacionesPushFcm;

class CombosController extends Controller
{
    public function listaCombos()
    {
        $combos = Combo::where('activo', '1' )->select('nombre', 'descripcion', 'beneficios','direccion')->get();
        return response()->json($combos, 200);
    }

    public function listaCombosHoras()
    {
        $combos = CombosHora::where('activo', '1')->select('id', 'hora', 'inversion', 'descuento')->get();
        return response()->json($combos, 200);
    }

    public function horasComboAlumno()
    {
        if( \Request::get('combo') )
        {
            if( \Request::get('user_id') )
            {
                $combo = \Request::get('combo');
                $alumno = \Request::get('user_id');
                $horas = AlumnoBilletera::where('combo', $combo)->where('user_id', $alumno)->select('horas')->get();
                return response()->json($horas, 200);
            }
            else
            {
                return response()->json(['error' => 'Alumno no especificado'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'Combo no especificado'], 401);
        }
    }
    
    public function compraComboAlumno(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'horas' => 'required',
            'valor' => 'required',
            'tarea_id' => 'required|numeric',
            'clase_id' => 'required|numeric'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        } 
        if (!is_numeric($request['valor']))
        {
            return response()->json(['error' => 'Valor inválido'], 401);
        }
        if (!is_numeric($request['horas']))
        {
            return response()->json(['error' => 'Horas inválidas'], 401);
        }
        $tarea = Tarea::where('id', $request['tarea_id'])->first();
        $clase = Clase::where('id', $request['clase_id'])->first();
        if ($tarea != null && $clase != null)
            return response()->json(['error' => 'Especifique una opción, Clase o Tarea'], 401);
        else if ($tarea == null && $clase == null)
            return response()->json(['error' => 'Especifique una opción, Clase o Tarea'], 401);
        if ($tarea != null && $tarea->estado != 'Sin_Horas')
            return response()->json(['error' => 'Estado de Tarea no permite comprar horas'], 401);
        if ($tarea != null && $tarea->user_id != $request['user_id'])
            return response()->json(['error' => 'Usuario no relacionado a la Clase'], 401);
        if ($clase != null && $clase->estado != 'Sin_Horas')
            return response()->json(['error' => 'Estado de Clase no permite comprar horas'], 401);
        if ($clase != null && $clase->user_id != $request['user_id'])
            return response()->json(['error' => 'Usuario no relacionado a la Clase'], 401);
        $usuario = Alumno::where('user_id', $request['user_id'])->first();
        if ($usuario != null)
        {
            if ($usuario->activo)
            {
                if ($clase != null)
                {
                    $duracion = $clase->duracion + ($clase->personas - 1);
                    if ($duracion < 2)
                        $duracion = 2;
                    if ($usuario->billetera + $request['horas'] < $duracion)
                        return response()->json(['error' => 'Horas insuficientes. Mínimo: '.($duracion-$usuario->billetera )], 401);
                }
                $compra = AlumnoCompra::create([
                    'user_id' => $request['user_id'],
                    'combo' => 'COMBO',
                    'valor' => $request['valor'],
                    'horas' => $request['horas'],
                    'estado' => 'Solicitado'
                ]);
                if ($compra->id)
                {
                    $data['compra_id'] = $compra->id;
                    $data['estado'] = 'Solicitado';
                    if ($tarea != null)
                        $actualizado = Tarea::where('id', $tarea->id )->update( $data );
                    if ($clase != null)
                    {
                        $actualizado = Clase::where('id', $clase->id)->update( $data );
                        $profesores = Profesore::join('profesor_materia', 'profesor_materia.user_id', '=', 'profesores.user_id')
                                                ->join('users', 'users.id', '=', 'profesores.user_id')
                                                ->where('profesores.activo', true)
                                                ->where('profesores.clases', true)
                                                ->where('profesores.disponible', true)
                                                //->where('profesores.ciudad', $sede)
                                                ->where('profesor_materia.activa', true)
                                                ->where('profesor_materia.materia', $clase->materia)
                                                ->select('users.email', 'users.token', 'users.sistema', 'users.id', 'users.name')
                                                ->get();
                        if ($clase->seleccion_profesor)
                        {
                            $claseAnterior = Clase::where('user_id', $clase->user_id)
                                            ->whereIn('estado', ['Aceptado', 'Terminado', 'Calificado'])
                                            ->orderBy('id', 'desc')->first();
                            if ($claseAnterior != null)
                            {
                                $profSelccionado = $profesores->where('id', $claseAnterior->user_id_pro)->firts();
                                if ($profSelccionado != null)
                                    $profesores = $profSelccionado;
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
                                    .$clase->duracion.', por '.$usuario->nombres.' '.$usuario->apellidos
                                    .', '.$dateTime;
                        $notificacion['estado'] = 'NO';
                        $pushClass = new NotificacionesPushFcm();
                        foreach($profesores as $solicitar)
                            $pushClass->enviarNotificacion($notificacion, $solicitar);
                    }
                    return response()->json(['success' => 'Compra de Combo Solicitada'], 200);
                }
                else
                    return response()->json(['error' => 'Error al registrar solicitud!'], 401);
            }
            else
                return response()->json(['error' => 'Alumno no puede comprar Combo'], 401); 
        }
        else
            return response()->json(['error' => 'No existe el Alumno'], 401);
    }

    public function horasAlumno()
    {
        if( \Request::get('user_id') )
        {
            $alumno = \Request::get('user_id');
            $horas = AlumnoBilletera::where('user_id', $alumno)->select('combo', 'horas')->get();
            return response()->json($horas, 200);
        }
        else
        {
            return response()->json(['error' => 'Alumno no especificado'], 401);
        }
    }

    public function horasTotales()
    {
        if( \Request::get('user_id') )
        {
            $alumno = \Request::get('user_id');
            $horas = Alumno::where('user_id', $alumno)->sum('billetera');
            return response()->json($horas, 200);
        }
        else
        {
            return response()->json(['error' => 'Alumno no especificado'], 401);
        }
    }
}