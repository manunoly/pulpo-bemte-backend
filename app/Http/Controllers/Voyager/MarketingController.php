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
        $ciudades = Ciudad::where('activa', '1' )->select('ciudad','pais', 'codigo')->get();
        $paises = Ciudad::where('activa', '1' )->select('pais', 'codigo')->distinct()->get();
        try
        {
            $pushClass = new NotificacionesPushFcm();
            $profesores = Profesore::join('users', 'users.id', '=', 'profesores.user_id')
                            ->where('profesores.activo', true)
                            ->where('profesores.ciudad', $request['ciudad'])
                            ->select('users.email', 'users.token', 'users.sistema', 'users.id', 'users.name')
                            ->get();
            //lanzar notificaciones profesores
            $notificacion['titulo'] = 'Anuncio BEMTE';
            $notificacion['clase_id'] = 0;
            $notificacion['tarea_id'] = 0;
            $notificacion['chat_id'] = 0;
            $notificacion['compra_id'] = 0;
            $notificacion['color'] = "profesor";
            $notificacion['estado'] = 'NO';
            $notificacion['texto'] = $request['mensaje'];
            foreach($profesores as $solicitar)
                $pushClass->enviarNotificacion($notificacion, $solicitar);
            
            $alumnos = Alumno::join('users', 'users.id', '=', 'alumnos.user_id')
                                ->where('alumnos.activo', true)
                                ->where('alumnos.ciudad', $request['ciudad'])
                                ->select('users.email', 'users.token', 'users.sistema', 'users.id', 'users.name')
                                ->get();
            //lanzar notificaciones alumnos
            $notificacion['color'] = "alumno";
            foreach($alumnos as $solicitar)
                $pushClass->enviarNotificacion($notificacion, $solicitar);
            
            \Request::session()->flash('success', 'Notificaciones Enviadas '.($profesores->count() + $alumnos->count()));
        }
        catch (Exception $e) 
        {
            \Request::session()->flash('error', 'No se pudieron realizar las Multas.');
        }

        return view('vendor.voyager.marketing.notificacion', 
                ['paises'=>$paises, 'ciudades'=>$ciudades]);
    }

    public function load()
    {
        $ciudades = Ciudad::where('activa', '1' )->select('ciudad','pais', 'codigo')->get();
        $paises = Ciudad::where('activa', '1' )->select('pais', 'codigo')->distinct()->get();
        return view('vendor.voyager.marketing.notificacion', 
                ['paises'=>$paises, 'ciudades'=>$ciudades]);
    }
}