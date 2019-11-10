<?php

namespace App\Http\Controllers;

use App\User;
use App\Alumno;
use App\Profesore;
use App\Tarea;
use App\Clase;
use App\Chat;
use Illuminate\Http\Request;
use Validator;
use Hash;

class ChatController extends Controller
{
    public function obtenerChat()
    {
        if (is_numeric(\Request::get('user_id')))
        {
            if (is_numeric(\Request::get('tarea_id')))
            {
                if (is_numeric(\Request::get('clase_id')))
                {
                    $user = \Request::get('user_id');
                    $claseID = \Request::get('clase_id');
                    $tareaID = \Request::get('tarea_id');
                    $tarea = Tarea::where('id', $tareaID)->first();
                    $clase = Clase::where('id', $claseID)->first();
                    if ($tarea == null && $clase == null)
                        return response()->json(['error' => 'No existe Tarea o Clase para el Chat'], 401);
                    
                    if ($tarea != null && $clase != null)
                        return response()->json(['error' => 'Especifica una opción, Tarea o Clase'], 401);
                    
                    if ($tarea != null)
                    {
                        if ($tarea->user_id != $user && $tarea->user_id_pro != $user)
                            return response()->json(['error' => 'Usuario no relacionado a la Tarea'], 401);
                    }
                    else
                    {
                        if ($clase->user_id != $user && $clase->user_id_pro != $user)
                            return response()->json(['error' => 'Usuario no relacionado a la Clase'], 401);
                    }
                    $chats = Chat::where('clase_id', $claseID)->where('tarea_id', $tareaID)
                                    ->orderBy('id')->get();

                    $data['leidoA'] = true;
                    foreach($chats->where('leidoA', false)->where('user_id', $user) as $item)
                        Chat::where('id', $item->id )->update( $data );
                    
                    $data['leidoP'] = true;
                    foreach($chats->where('leidoP', false)->where('user_id_pro', $user) as $item)
                        Chat::where('id', $item->id )->update( $data );
                    
                    return response()->json($chats->take(100), 200);
                }
                else
                    return response()->json(['error' => 'Clase no especificada'], 401);
            }
            else
                return response()->json(['error' => 'Tarea no especificada'], 401);
        }
        else
            return response()->json(['error' => 'Usuario no especificado'], 401);
    }
    
    public function enviarChat(Request $request)
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
        $texto = isset($request['texto']) ? trim($request['texto']) : NULL;
        $imagen = isset($request['imagen']) ? trim($request['imagen']) : NULL;
        if (($texto == NULL) && ($imagen == NULL))
        {
            return response()->json(['error' => 'Chat sin especificar'], 401);
        }
        $tarea = Tarea::where('id', $request['tarea_id'])->first();
        $clase = Clase::where('id', $request['clase_id'])->first();
        if ($tarea == null && $clase == null)
        {
            return response()->json(['error' => 'No existe Tarea o Clase para el Chat'], 401);
        }
        if ($tarea != null && $clase != null)
        {
            return response()->json(['error' => 'Especifica una opción, Tarea o Clase'], 401);
        }
        if ($tarea != null)
        {
            if ($tarea->user_id != $request['user_id'] && $tarea->user_id_pro != $request['user_id'])
                return response()->json(['error' => 'Usuario no relacionado a la Tarea'], 401);
            if ($tarea->user_id_pro == null || $tarea->estado != 'Aceptado')
                return response()->json(['error' => 'Chat inactivo para la Tarea'], 401);
            $alumno = $tarea->user_id;
            $profesor = $tarea->user_id_pro;
        }
        else
        {
            if ($clase->user_id != $request['user_id'] && $clase->user_id_pro != $request['user_id'])
                return response()->json(['error' => 'Usuario no relacionado a la Clase'], 401);
            if ($clase->user_id_pro == null || $clase->estado != 'Aceptado')
                return response()->json(['error' => 'Chat inactivo para la Clase'], 401);
            $alumno = $clase->user_id;
            $profesor = $clase->user_id_pro;
        }
        $chat = Chat::create([
            'user_escribe' => $request['user_id'],
            'clase_id' => $request['clase_id'],
            'tarea_id' => $request['tarea_id'],
            'leidoA' => $alumno == $request['user_id'] ? true : false,
            'leidoP' => $profesor == $request['user_id'] ? true : false,
            'imagen' => $imagen,
            'texto' => $texto,
            'user_id' => $alumno,
            'user_id_pro' => $profesor
        ]);
        if (!$chat->id)
        {
            return response()->json(['error' => 'Error al subir el chat!'], 401);
        }
        return response()->json(['success'=> 'Chat Enviado'], 200);
    }
}