<?php

namespace App\Http\Controllers;

use App\User;
use App\Alumno;
use App\Profesore;
use App\Tarea;
use App\Clase;
use App\TareaEjercicio;
use App\ClaseEjercicio;
use App\AlumnoPago;
use App\AlumnoCompra;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\Notificacion;
use Validator;
use Hash;

class FicherosController extends Controller
{
    public function subirArchivo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'file' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }
        $file = isset($request['file']) ? trim($request['file']) : NULL;
        if ($file == NULL)
        {
            return response()->json(['error' => 'Archivo sin especificar'], 401);
        }
        $usuario = User::where('id', $request['user_id'])->first();
        if ($usuario == NULL)
        {
            return response()->json(['error' => 'No se encontró el usuario'], 401);
        }
        if ($usuario->activo && ($usuario->tipo == 'Alumno' || $usuario->tipo == 'Profesor'))
        {
            try
            {
                $file = $request->file('file');
                $nombre = $file->getClientOriginalName();
                \Storage::disk('local')->put($request['user_id'].'\\'.$nombre,  \File::get($file));
            }
            catch(Exception $e) 
            {
                return response()->json(['error' => $e->getMessage()], 401);
            }
            return response()->json(['success' => 'Archivo Subido'], 200);
        }
        else
        {
            return response()->json(['error' => 'Usuario no autorizado'], 401);
        }
    }
    
    public function subirEjercicio(Request $request)
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
        $drive = isset($request['drive']) ? trim($request['drive']) : NULL;
        $archivo = isset($request['archivo']) ? 'uploads'.'/'.$request['user_id'].'/'.trim($request['archivo']) : NULL;
        if (($drive == NULL) && ($archivo == NULL))
        {
            return response()->json(['error' => 'Ejercicio sin especificar'], 401);
        }
        $tarea = Tarea::where('id', $request['tarea_id'])->first();
        $clase = Clase::where('id', $request['clase_id'])->first();
        if ($tarea == null && $clase == null)
        {
            return response()->json(['error' => 'No existe Tarea o Clase para el ejercicio'], 401);
        }
        if ($tarea != null)
        {
            if (($tarea->estado != 'Aceptado') && ($tarea->estado != 'Solicitado') && ($tarea->estado != 'Confirmado'))
            {
                return response()->json(['error' => 'La Tarea no se encuentra Disponible'], 401);
            }
            else if (($tarea->user_id != $request['user_id']) && ($tarea->user_id_pro != $request['user_id']))
            {
                return response()->json(['error' => 'Usuario no relacionado a la Tarea'], 401);
            }
        }
        if ($clase != null)
        {
            if (($clase->estado != 'Aceptado') && ($clase->estado != 'Solicitado') && ($clase->estado != 'Confirmado'))
            {
                return response()->json(['error' => 'La Clase no se encuentra Disponible'], 401);
            }
            else if (($clase->user_id != $request['user_id']) && ($clase->user_id_pro != $request['user_id']))
            {
                return response()->json(['error' => 'Usuario no relacionado a la Clase'], 401);
            }
        }
        $alumno = Alumno::where('user_id', $request['user_id'])->first();
        $profesor = Profesore::where('user_id', $request['user_id'])->first();
        if (($alumno != null) || ($profesor != null))
        {
            if ((($alumno != null) && ($alumno->activo)) || (($profesor != null) && ($profesor->activo)))
            {
                if ($tarea != null)
                {
                    $aplica = TareaEjercicio::create([
                        'user_id' => $request['user_id'],
                        'tarea_id' => $tarea->id,
                        'archivo' => $nombre,
                        'drive' => $drive
                    ]);
                    if (!$aplica->id)
                    {
                        return response()->json(['error' => 'Error al subir el ejercicio!'], 401);
                    }
                }
                if ($clase != null)
                {
                    $aplica = ClaseEjercicio::create([
                        'user_id' => $request['user_id'],
                        'clase_id' => $clase->id,
                        'archivo' => $nombre,
                        'drive' => $drive
                    ]);
                    if (!$aplica->id)
                    {
                        return response()->json(['error' => 'Error al subir el ejercicio!'], 401);
                    }
                }
                return response()->json(['success' => 'Ejercicio Guardado'], 200);
            }
            else
            {
                return response()->json(['error' => 'Usuario no se encuentra activo'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'No se encontró Usuario para subir Ejercicio'], 401);
        }
    }
    

    public function subirTransferencia(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required',
            'tarea_id' => 'required|numeric',
            'clase_id' => 'required|numeric',
            'combo_id' => 'required'
        ]);
        if ($validator->fails()) 
        {
            return response()->json(['error' => $validator->errors()], 406);
        }
        if (($request['tarea_id'] == 0) && ($request['clase_id'] == 0) && ($request['combo_id'] == '0'))
        {
            return response()->json(['error' => 'Especifique una opción'], 401);
        }
        $drive = isset($request['drive']) ? trim($request['drive']) : NULL;
        $archivo = isset($request['archivo']) ? 'uploads'.'/'.$request['user_id'].'/'.trim($request['archivo']) : NULL;
        if (($drive == NULL) && ($archivo == NULL))
        {
            return response()->json(['error' => 'Transferencia sin especificar'], 401);
        }
        $tarea = null;
        if ($request['tarea_id'] > 0)
        {
            if (($request['clase_id'] > 0) || ($request['combo_id'] != '0'))
            {
                return response()->json(['error' => 'Especifique una opción para la tarea'], 401);
            }
            $tarea = Tarea::where('id', $request['tarea_id'])->first();
            if ($tarea == null)
            {
                return response()->json(['error' => 'No existe Tarea a pagar'], 401);
            }
            else if ($tarea->estado != 'Confirmado')
            {
                return response()->json(['error' => 'Tarea no Confirmada para pagar'], 401);
            }
        }
        $clase = null;
        if ($request['clase_id'] > 0)
        {
            if ($request['tarea_id'] > 0 || ($request['combo_id'] != '0'))
            {
                return response()->json(['error' => 'Especifique una opción para la clase'], 401);
            }
            $clase = Clase::where('id', $request['clase_id'])->first();
            if ($clase == null)
            {
                return response()->json(['error' => 'No existe Clase a pagar'], 401);
            }
            else if ($clase->estado != 'Confirmado')
            {
                return response()->json(['error' => 'Clase no Confirmada para pagar'], 401);
            }
        }
        $combo = null;
        if ($request['combo_id'] != '0')
        {
            if ($request['tarea_id'] > 0 || $request['clase_id'] > 0)
            {
                return response()->json(['error' => 'Especifique una opción para el Combo'], 401);
            }
            if (is_numeric($request['combo_id']))
            {
                $combo = AlumnoCompra::where('id', $request['combo_id'])->where('user_id', $request['user_id'])->first();
                if ($combo != null && $combo->estado != 'Solicitado')
                {
                    return response()->json(['error' => 'La Solicitud ya fue procesada'], 401);
                }
            }
        } 
        $user = Alumno::where('user_id', $request['user_id'])->first();
        if ($user != null)
        {
            if ($user->activo)
            {
                $correoAdmin = 'Ha sido subida una transferencia por '.$user->nombres.' '.$user->apellidos;
                $detalle = '';
                if ($request['combo_id'] != '0' && $combo == null)
                {
                    $combo = AlumnoCompra::create([
                        'user_id' => $request['user_id'],
                        'combo' => $request['combo_id'],
                        'valor' => $request['valor'],
                        'horas' => $request['horas'],
                        'estado' => 'Solicitado'
                    ]);
                    if (!$combo->id)
                    {
                        return response()->json(['error' => 'Error al registrar solicitud!'], 401);
                    }
                }
                $solicitud = AlumnoPago::where('user_id', $request['user_id'])
                                        ->where('tarea_id', $request['tarea_id'])
                                        ->where('clase_id', $request['clase_id'])
                                        ->where('combo_id', $combo != null ? $combo->id : $request['combo_id'])->first();
                if ($solicitud == null)
                {
                    $aplica = AlumnoPago::create([
                        'user_id' => $request['user_id'],
                        'tarea_id' => $request['tarea_id'],
                        'clase_id' => $request['clase_id'],
                        'combo_id' => $combo != null ? $combo->id : $request['combo_id'],
                        'archivo' => $archivo,
                        'drive' => $drive,
                        'estado' => 'Solicitado'
                    ]);
                    if (!$aplica->id)
                    {
                        return response()->json(['error' => 'Error al registrar solicitud!'], 401);
                    }
                }
                else
                {
                    $data['archivo'] = $archivo;
                    $data['drive'] = $drive;
                    $data['estado'] = 'Solicitado';
                    $actualizado = AlumnoPago::where('id', $solicitud->id )->update( $data );
                    if(!$actualizado )
                    {
                        return response()->json(['error' => 'Error al actualizar solicitud'], 401);
                    }
                }
                if ($clase != null)
                {
                    $dataClase['estado'] = 'Confirmando_Pago';
                    $actualizado = Clase::where('id', $clase->id )->update( $dataClase );
                    $detalle = 'Para la Clase '.$clase->id;
                    if(!$actualizado )
                    {
                        return response()->json(['error' => 'Error al actualizar solicitud'], 401);
                    }
                }
                if ($tarea != null)
                {
                    $dataTarea['estado'] = 'Confirmando_Pago';
                    $actualizado = Tarea::where('id', $tarea->id )->update( $dataTarea );
                    $detalle = 'Para la Tarea '.$tarea->id;
                    if(!$actualizado )
                    {
                        return response()->json(['error' => 'Error al actualizar solicitud'], 401);
                    }
                }
                try 
                {
                    Mail::to(env('MAILADMIN'))->send(new Notificacion(
                            'Administrador de '.env('EMPRESA'), 
                            $correoAdmin, $detalle, 'Por favor, revisar la transferencia.', 
                            env('EMPRESA')));
                }
                catch (Exception $e) { }

                return response()->json(['success' => 'Transferencia Solicitada'], 200);
            }
            else
            {
                return response()->json(['error' => 'Alumno Inactivo'], 401);
            }
        }
        else
        {
            return response()->json(['error' => 'Sin Alumno para subir Transferencia'], 401);
        }
    }
}