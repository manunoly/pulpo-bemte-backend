<?php

namespace App\Console\Commands;
use Illuminate\Support\Facades\Mail;
use Illuminate\Console\Command;
use App\User;
use App\Clase;
use App\Profesore;
use App\NotificacionesPushFcm;

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
        $newDate = date("Y-m-d H:i:s", strtotime(date("d/m/y H:i:s"). '-15 minutes'));
        $clases = Clase::where('estado','Solicitado')->where('activa', true)
                ->where('seleccion_profesor', true)->where('updated_at','<=', $newDate)->get();
        $notificacion['titulo'] = 'Solicitud de Clase';
        $dateTime = date("Y-m-d H:i:s");
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
                    //->where('profesores.ciudad', $sede)
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
            $notificacion['texto'] = 'Ha sido solicitada la Clase '.$item->id.' de '.$item->materia
                    .', para el '.$item->fecha.' a las '.$item->hora1
                    .', en '.$item->ubicacion.' para '.$item->personas.' estudiantes con una duracion de '
                    .$item->duracion.', por '.$user->name.', '.$dateTime;
            foreach($profesores as $solicitar)
                $pushClass->enviarNotificacion($notificacion, $solicitar);
        }
    }
}