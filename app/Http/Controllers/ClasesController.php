<?php

namespace App\Http\Controllers;

use App\Materia;
use App\User;
use App\Sede;
use App\Clase;
use App\Combo;
use App\Multa;
use App\Alumno;
use App\Profesore;
use App\ClasesGratis;
use App\ClaseEjercicio;
use App\AlumnoBilletera;
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
                $clase->estado == 'Sin_Pago' || $clase->estado == 'Terminado' || 
                $clase->estado == 'Calificado' || $clase->activa == 0)
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
                    $penHoras = 0;
                    $duracion = $clase->duracion - ($clase->personas - 1);
                    if ($duracion < 2)
                        $duracion = 2;
                    if ($request['user_id'] == $clase->user_id_pro)
                    {
                        $dateTime = date("Y-m-d H:i:s");
                        $nowDate = date_format(date_create($dateTime), "Y-m-d");
                        $nowTime = date_format(date_create($dateTime), "H:i:s");
                        $limit = date("Y-m-d H:i:s", strtotime($clase->fecha.' '.$clase->hora_prof. '-24 hours'));
                        $limitDate = date_format(date_create($limit), "Y-m-d");
                        $limitTime = date_format(date_create($limit), "H:i:s");
                        if (($nowDate > $limitDate) || (($nowDate == $limitDate)
                            && ($nowTime > $limitTime)))
                        {
                            //penalizar 1 hora al profesor
                            $profeClase = Profesore::where('user_id', $clase->user_id_pro)->first();
                            $multaProf = Multa::create([
                                'user_id' => $clase->user_id_pro,
                                'tarea_id' => 0,
                                'clase_id' => $clase->id,
                                'valor' => $profeClase->valor_clase,
                                'comentario' => 'Clase Cancelada dentro de las 24 horas',
                                'estado' => 'Solicitado'
                                ]);
                            if (!$multaProf->id)
                            {
                                return response()->json(['error' => 'Ocurrió un error al crear Multa al Profesor'], 401);
                            }
                        }
                        if ($clase->estado == 'Aceptado' || $clase->estado == 'Pago_Aprobado')
                        {
                            //devolver las horas al alumno 
                            $penHoras = $duracion;
                        }
                    }
                    else if ($request['user_id'] == $clase->user_id)
                    {
                        $dateTime = date("Y-m-d H:i:s");
                        $nowDate = date_format(date_create($dateTime), "Y-m-d");
                        $nowTime = date_format(date_create($dateTime), "H:i:s");
                        $limit = date("Y-m-d H:i:s", strtotime($clase->fecha.' '.$clase->hora_prof. '-3 hours'));
                        $limitDate = date_format(date_create($limit), "Y-m-d");
                        $limitTime = date_format(date_create($limit), "H:i:s");
                        if (($nowDate > $limitDate) || (($nowDate == $limitDate)
                            && ($nowTime > $limitTime)))
                        {
                            //penalizar todas las horas al alumno
                            if ($clase->estado == 'Confirmado' || $clase->estado == 'Confirmando_Pago')
                            {
                                $penHoras = -1 * $duracion;
                            }
                        }
                        else
                        {
                            if ($clase->estado == 'Aceptado' || $clase->estado == 'Pago_Aprobado')
                            {
                                //devolver las horas menos 1 por penalizacion al 
                                $penHoras = $duracion - 1;
                            }
                            if ($clase->estado == 'Confirmado' || $clase->estado == 'Confirmando_Pago')
                            {
                                //penalizar 1 hora al alumno
                                $penHoras = -1;
                            }
                        }
                    }
                    else
                        return response()->json(['error' => 'Usuario especificado no coincide con los datos de la Clase'], 401);
                    if ($penHoras != 0)
                    {
                        //quitar las horas del combo del Alumno
                        $bill = AlumnoBilletera::where('user_id', $clase->user_id)->where('combo', $clase->combo)->first();
                        $resto = 0;
                        if ($bill != null)
                        {
                            if ($bill->horas > $penHoras * -1)
                            {
                                $dataCombo['horas'] = $bill->horas + $penHoras;
                            }
                            else
                            {
                                $resto = $penHoras + $bill->horas;
                                $dataCombo['horas'] = 0;
                            }
                            $actCombo = AlumnoBilletera::where('id', $bill->id )->update( $dataCombo );
                            if(!$actCombo)
                                return response()->json(['error' => 'Ocurrió un error al Penalizar al Alumno.'], 401);
                        }
                        else
                            $resto = $penHoras;
                        if ($resto != 0)
                        {
                            $listaCombos = AlumnoBilletera::where('user_id', $clase->user_id)->orderBy('horas', 'desc')->get();
                            foreach($listaCombos as $item)
                            {
                                $restar = $resto;
                                if ($item->horas < $resto * -1)
                                {
                                    $restar = $item->horas * -1;
                                }
                                $dataCombo['horas'] = $item->horas + $restar;
                                $actCombo = AlumnoBilletera::where('id', $item->id )->update( $dataCombo );
                                if(!$actCombo )
                                {
                                    return response()->json(['error' => 'Ocurrió un error al Penalizar Horas del Alumno.'], 401);
                                }
                                $resto = $resto - $restar;
                                if ($resto == 0)
                                {
                                    break;
                                }
                            }
                        }
                    }
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
                        return response()->json(['error' => 'La Clase ya no permite modificación'], 401);

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
                        return response()->json(['error' => 'Usuario especificado no coincide con los datos de la Clase'], 401);
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
}