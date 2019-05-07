<?php

namespace App\Http\Controllers;

use App\Tarea;
use App\Alumno;
use App\AlumnoPago;
use App\AlumnoCompra;
use App\AlumnoBilletera;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
            return response()->json(['error' => 'La calificación debe estar en el rango de 0 a 5'], 401);
        }

        $id_tarea = isset($request['tarea_id']) ? $request['tarea_id'] : 0;
        $id_clase = isset($request['clase_id']) ? $request['clase_id'] : 0;
        if ($id_tarea== 0 && $id_clase == 0)
        {
            return response()->json(['error' => 'Debe especificar la tarea o clase que se califica'], 401);
        }

        $id_usuario = $request['user_id'];
        $id_calificado = $request['user_id_calif'];
        if ($id_usuario == $id_calificado)
        {
            return response()->json(['error' => 'No se puede calificar al mismo usuario'], 401);
        }
        if ($id_tarea != 0)
        {
            $tarea = Tarea::where('id', $id_tarea)->first();
            if ($tarea != null)
            {
                if ($id_calificado == $tarea->user_id && $id_usuario == $tarea->user_id_pro)
                {
                    $coment = isset($request['comment']) ? $request['comment'] : NULL;
                    $calif = array("califacion_alumno" => $request['calificacion'], 
                                    "comentario_alumno" => $coment);
                    Tarea::where('id',$id_tarea)->update($calif);
                }
                else
                {
                    return response()->json(['error' => 'Los usuarios no coinciden con la tarea especificada'], 401);
                }
            }
            else
            {
                return response()->json(['error' => 'No se encontró la tarea a calificar'], 401);
            }
        }
        $tareas_est = Tarea::where('user_id', $id_calificado)->where('estado', 'Terminado')
                                ->where('califacion_alumno', '!=',NULL)->get();
        if ($tareas_est->count() > 0)
        {
            $calif_est = array("calificacion" => $tareas_est->sum('califacion_alumno')/$tareas_est->count());
            Alumno::where('user_id',$id_calificado)->update($calif_est);
        }
        return response()->json(['success' => 'Alumno calificado correctamente'], 200);
    }


    public function subirTransferencia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'tarea_id' => 'required|numeric',
            'clase_id' => 'required|numeric',
            'combo_id' => 'required|numeric'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }
        if (($request['tarea_id'] == 0) && ($request['clase_id'] == 0) && ($request['combo_id'] == 0))
        {
            return response()->json(['error' => 'Especifique una sola opción'], 401);
        }
        $drive = isset($request['drive']) ? trim($request['drive']) : NULL;
        $archivo = isset($request['archivo']) ? trim($request['archivo']) : NULL;
        if (($drive == NULL) && ($archivo == NULL))
        {
            return response()->json(['error' => 'Archivo de transferencia sin especificar'], 401);
        }
        $tarea = null;
        if ($request['tarea_id'] > 0)
        {
            if (($request['clase_id'] > 0) || ($request['combo_id'] > 0))
            {
                return response()->json(['error' => 'Especifique una sola opción'], 401);
            }
            $tarea = Tarea::where('id', $request['tarea_id'])->first();
            if ($tarea == null)
            {
                return response()->json(['error' => 'No existe la tarea a pagar'], 401);
            }
            else if ($tarea->estado != 'Confirmado')
            {
                return response()->json(['error' => 'La Tarea no se encuentra Confirmada para pagar'], 401);
            }
        }
        $clase = null;
        if ($request['clase_id'] > 0)
        {
            if (($request['tarea_id'] > 0) || ($request['combo_id'] > 0))
            {
                return response()->json(['error' => 'Especifique una sola opción'], 401);
            }
            $clase = Tarea::where('id', $request['clase_id'])->first();
            if ($clase == null)
            {
                return response()->json(['error' => 'No existe la clase a pagar'], 401);
            }
            else if ($clase->estado != 'Confirmado')
            {
                return response()->json(['error' => 'La Clase no se encuentra Confirmada para pagar'], 401);
            }
        }
        $combo = null;
        if ($request['combo_id'] > 0)
        {
            if (($request['clase_id'] > 0) || ($request['tarea_id'] > 0))
            {
                return response()->json(['error' => 'Especifique una sola opción'], 401);
            }
            $combo = AlumnoCompra::where('id', $request['combo_id'])->first();
            if ($combo == null)
            {
                return response()->json(['error' => 'No existe la solicitud de compra a pagar'], 401);
            }
            else if ($combo->estado != 'Solicitado')
            {
                return response()->json(['error' => 'La Solicitud ya fue procesada'], 401);
            }
        } 
        $user = Alumno::where('user_id', $request['user_id'])->first();
        if ($user != null)
        {
            if ($user->activo)
            {
                if ($archivo != NULL)
                {
                    $file = $request->file('archivo');
                    $nombre = $file->getClientOriginalName();
                    \Storage::disk('local')->put($request['user_id'].'\\'.$nombre,  \File::get($file));
                }
                $solicitud = AlumnoPago::where('user_id', $request['user_id'])
                                        ->where('tarea_id', $request['tarea_id'])
                                        ->where('clase_id', $request['clase_id'])
                                        ->where('combo_id', $request['combo_id'])->first();
                if ($solicitud == null)
                {
                    $aplica = AlumnoPago::create([
                        'user_id' => $request['user_id'],
                        'tarea_id' => $request['tarea_id'],
                        'clase_id' => $request['clase_id'],
                        'combo_id' => $request['combo_id'],
                        'archivo' => $nombre,
                        'drive' => $drive,
                        'estado' => 'Solicitado'
                    ]);
                    if (!$aplica->id)
                    {
                        return response()->json(['error' => 'Ocurrió un error al registrar solicitud!'], 401);
                    }
                }
                else
                {
                    $data['archivo'] = $nombre;
                    $data['drive'] = $drive;
                    $data['estado'] = 'Solicitado';
                    $actualizado = AlumnoPago::where('id', $solicitud->id )->update( $data );
                    if(!$actualizado )
                    {
                        return response()->json(['error' => 'Ocurrió un error al actualizar solicitud.'], 401);
                    }
                }
                return response()->json(['success' => 'Archivo guardado exitosamente'], 200);
            }
            else
            {
                return response()->json(['error' => 'El alumno no se encuentra activo'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'No se encontró al Alumno para subir Transferencia'], 401);
        }
    }
    
    public function pagarConCombo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'tarea_id' => 'required|numeric',
            'combo' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }
        $tarea = Tarea::where('id', $request['tarea_id'])->first();
        if ($tarea == null)
        {
            return response()->json(['error' => 'No existe la tarea'], 401);
        }
        else if ($tarea->estado != 'Confirmado')
        {
            return response()->json(['error' => 'La Tarea no se encuentra Confirmada para pagar'], 401);
        }
        else if ($tarea->user_id != $request['user_id'])
        {
            return response()->json(['error' => 'El usuario no tiene relación con la Tarea'], 401);
        }
        $combo = AlumnoBilletera::where('user_id', $request['user_id'])
                                    ->where('combo', $request['combo'])->first();
        if ($combo == null)
        {
            return response()->json(['error' => 'Combo no disponible para pagar'], 401);
        }
        else if ($combo->horas < $tarea->tiempo_estimado)
        {
            return response()->json(['error' => 'Combos sin horas para pagar'], 401);
        }
        $user = Alumno::where('user_id', $request['user_id'])->first();
        if ($user != null)
        {
            if ($user->activo)
            {
                $dataTarea['estado'] = 'Aceptado';
                $actTarea = Tarea::where('id', $tarea->id )->update( $dataTarea );
                if(!$actTarea )
                {
                    return response()->json(['error' => 'Ocurrió un error al actualizar la Tarea.'], 401);
                }
                $dataCombo['horas'] = $combo->horas - $tarea->tiempo_estimado;
                $actCombo = AlumnoBilletera::where('id', $combo->id )->update( $dataCombo );
                if(!$actCombo )
                {
                    return response()->json(['error' => 'Ocurrió un error al actualizar pago.'], 401);
                }
                return response()->json(['success' => 'Pago con Combo exitoso'], 200);
            }
            else
            {
                return response()->json(['error' => 'El alumno no se encuentra activo'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'No se encontró al Alumno para subir el pago'], 401);
        }
    }
}