<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\User;
use App\Tarea;
use App\Clase;
use Carbon\Carbon;
use App\NotificacionesPushFcm;

class TerminarTareaClase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'terminar:tarea:clase';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Al pasar la fecha de entrega de la tarea o clase se procede a Terminarla';

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
        $newDate = date("Y-m-d");
        $newTime = date("H:i:s");
        $listado = Clase::where('estado','Aceptado')->where('fecha','<=', $newDate)
                            ->where('activa', true)->get();
        $clases = [];
        foreach($listado as $item)
        {
            if (($item->fecha != $newDate) || (($item->fecha == $newDate)
                    && ($item->hora_prof <= $newTime)))
            {
                $clases[] = $item;
            }
        }
        foreach($clases as $item)
        {
            $dataClase['estado'] = 'Terminado';
            $dataClase['activa'] = false;
            Clase::where('id', $item->id )->update( $dataClase );
        }

        $timestamp = Carbon::now()->addHours(-24);
        $listado = Tarea::where('estado','Aceptado')->where('fecha_entrega','<=', $timestamp->toDateString())
                        ->where('activa', true)->get();
        $tareas = [];
        foreach($listado as $item)
        {
            if (($item->fecha_entrega != $timestamp->toDateString()) || 
                (($item->fecha_entrega == $timestamp->toDateString())
                    && ($item->hora_fin <= $newTime)))
            {
                $tareas[] = $item;
            }
        }
        $pushClass = new NotificacionesPushFcm();
        $notificacion['titulo'] = 'Tarea Terminada';
        $notificacion['estado'] = 'NO';
        $notificacion['clase_id'] = 0;
        $notificacion['chat_id'] = 0;
        $notificacion['compra_id'] = 0;
        foreach($tareas as $item)
        {
            $dataTarea['estado'] = 'Terminado';
            $dataTarea['activa'] = false;
            Tarea::where('id', $item->id )->update( $dataTarea );
            //enviar notificacion
            $userAlumno = User::where('id', '=', $item->user_id )->first();
            $userProfesor = User::where('id', '=', $item->user_id_pro )->first();
            $notificacion['tarea_id'] = $item->id;
            $notificacion['texto'] = 'La Tarea de '.$item->materia.', '.$item->tema.', ha sido Terminada.';
            $notificacion['color'] = "alumno";
            $pushClass->enviarNotificacion($notificacion, $userAlumno);
            $notificacion['color'] = "profesor";
            $pushClass->enviarNotificacion($notificacion, $userProfesor);
        }
    }
}