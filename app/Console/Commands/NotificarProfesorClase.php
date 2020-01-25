<?php

namespace App\Console\Commands;
use Illuminate\Support\Facades\Mail;
use Illuminate\Console\Command;
use App\User;
use App\Clase;
use App\Profesore;
use App\NotificacionesPushFcm;
use Carbon\Carbon;

class NotificarProfesorClase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notificar:profesor:clase';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Notificar al resto de los profesores una clase no atentida por el profesor solicitado';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $timestamp = Carbon::now()->addMinutes(-15);
        $clases = Clase::join('alumnos', 'alumnos.user_id', '=', 'clases.user_id')
                ->where('clases.estado','Solicitado')->where('clases.activa', true)
                ->where('clases.seleccion_profesor', true)->where('clases.updated_at','<=', $timestamp)
                ->select('clases.*', 'alumnos.ciudad')->get();
        $notificacion['titulo'] = 'Solicitud de Clase';
        $notificacion['estado'] = 'NO';
        $pushClass = new NotificacionesPushFcm();
        foreach($clases as $item)
        {
            $actClase['seleccion_profesor'] = false;
            Clase::where('id', $item->id )->update( $actClase );
            $profesores = Profesore::join('profesor_materia', 'profesor_materia.user_id', '=', 'profesores.user_id')
                    ->join('users', 'users.id', '=', 'profesores.user_id')
                    ->where('profesores.activo', true)
                    ->where('profesores.clases', true)
                    ->where('profesores.disponible', true)
                    ->where('profesores.ciudad', $item->ciudad)
                    ->where('profesor_materia.activa', true)
                    ->where('profesor_materia.materia', $item->materia)
                    ->select('users.email', 'users.token', 'users.sistema', 'users.id', 'users.name')
                    ->get();

            //lanzar notificaciones a los profesores
            $user = User::where('id', '=', $item->user_id )->first();
            $notificacion['clase_id'] = $item->id;
            $notificacion['tarea_id'] = 0;
            $notificacion['chat_id'] = 0;
            $notificacion['compra_id'] = 0;
            $notificacion['color'] = "profesor";
            $notificacion['texto'] = 'Ha sido solicitada una Clase de '.$item->materia.', '.$item->tema
                    .', para el '.$item->fecha.' a las '.$item->hora1
                    .', en '.$item->ubicacion.' para '.$item->personas.' estudiantes con una duracion de '
                    .$item->duracion.' horas, por '.$user->name;
            foreach($profesores as $solicitar)
                $pushClass->enviarNotificacion($notificacion, $solicitar);
        }
    }
}