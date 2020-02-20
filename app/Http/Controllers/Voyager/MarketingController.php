<?php

namespace App\Http\Controllers\Voyager;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use TCG\Voyager\Database\Schema\SchemaManager;
use TCG\Voyager\Facades\Voyager;

use App\Alumno;
use App\Ciudad;
use App\Profesore;
use App\TareaProfesor;
use App\NotificacionesPushFcm;

class MarketingController extends Controller
{
    public function enviar(Request $request)
    {
        try
        {
            if (trim($request['mensaje']) == '')
            {
                \Request::session()->flash('error', 'Por favor escribir un mensaje para enviar.');
            }
            else
            {
                if (!$request['profesor'] && !$request['alumno'])
                {
                    \Request::session()->flash('error', 'Debe indicar a quienes se debe enviar el mensaje.');
                }
                else
                {
                    $pushClass = new NotificacionesPushFcm();
                    $cantidad = 0;
                    $notificacion['titulo'] = 'Anuncio BEMTE';
                    $notificacion['clase_id'] = 0;
                    $notificacion['tarea_id'] = 0;
                    $notificacion['chat_id'] = 0;
                    $notificacion['compra_id'] = 0;
                    $notificacion['estado'] = 'NO';
                    $notificacion['texto'] = $request['mensaje'];
                    if ($request['profesor'])
                    {
                        $profesores = Profesore::join('users', 'users.id', '=', 'profesores.user_id')
                                        ->where('profesores.activo', true)
                                        ->where('profesores.ciudad', $request['ciudad'])
                                        ->select('users.email', 'users.token', 'users.sistema', 'users.id', 'users.name')
                                        ->get();
                        //lanzar notificaciones profesores
                        $notificacion['color'] = "profesor";
                        foreach($profesores as $solicitar)
                            $pushClass->enviarNotificacion($notificacion, $solicitar);
                        
                        $cantidad += $profesores->count();
                    }
                    if ($request['alumno'])
                    {
                        $alumnos = Alumno::join('users', 'users.id', '=', 'alumnos.user_id')
                                            ->where('alumnos.activo', true)
                                            ->where('alumnos.ciudad', $request['ciudad'])
                                            ->select('users.email', 'users.token', 'users.sistema', 'users.id', 'users.name')
                                            ->get();
                        //lanzar notificaciones alumnos
                        $notificacion['color'] = "alumno";
                        foreach($alumnos as $solicitar)
                            $pushClass->enviarNotificacion($notificacion, $solicitar);
        
                        $cantidad += $alumnos->count();
                    }
                    \Request::session()->flash('success', 'Notificaciones Enviadas '.( $cantidad ));
                }
            }
        }
        catch (Exception $e) 
        {
            \Request::session()->flash('error', 'Ha ocurrido un error, vuelva a intentarlo.');
        }
        return redirect()->back();
    }

    public function load()
    {
        $ciudades = Ciudad::where('activa', '1' )->select('ciudad','pais', 'codigo')
                    ->orderBy('pais', 'asc')->orderBy('ciudad', 'asc')->get();
        return view('vendor.voyager.marketing.notificacion', 
                ['ciudades'=>$ciudades]);
    }
}