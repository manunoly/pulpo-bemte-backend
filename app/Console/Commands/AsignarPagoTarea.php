<?php

namespace App\Console\Commands;
use Illuminate\Support\Facades\Mail;
use Illuminate\Console\Command;
use App\User;
use App\Tarea;
use App\AlumnoPago;
use App\AlumnoCompra;
use App\Mail\Notificacion;
use App\NotificacionesPushFcm;
use Carbon\Carbon;

class AsignarPagoTarea extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asignar:pago:tarea';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comprobar el pago de la tarea al cumplirse el tiempo';

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
        $timestamp = Carbon::now()->addMinutes(-60);
        $tareas = Tarea::whereIn('estado', ['Confirmado','Confirmando_Pago'])
                        ->where('activa', true)->where('updated_at','<=', $timestamp)->get();
        $pushClass = new NotificacionesPushFcm();
        foreach($tareas as $item)
        {
            $rechazado = true;
            $transfer = AlumnoPago::where('user_id', $item->user_id)->where('tarea_id', $item->id)->first();
            if ($transfer != null)
            {
                if ($transfer->estado != 'Aprobado')
                {
                    $dataTarea['estado'] = 'Pago_Rechazado';
                    $dataTarea['activa'] = false;
                    $rechazado = false;
                    Tarea::where('id', $item->id )->update( $dataTarea );
                    $dataTransfer['estado'] = 'Rechazado';
                    if ($transfer->estado == 'Solicitado')
                        AlumnoPago::where('id', $transfer->id)->update( $dataTransfer );
                    if ($transfer->combo_id > 0)
                        AlumnoCompra::where('id', $transfer->combo_id)->update( $dataTransfer );

                    //lanzar notificacion al alumno
                    $alumno = User::where('id', $item->user_id)->first();
                    $notificacion['tarea_id'] = $item->id;
                    $notificacion['clase_id'] = 0;
                    $notificacion['chat_id'] = 0;
                    $notificacion['compra_id'] = 0;
                    $notificacion['color'] = "cancelar";
                    $notificacion['estado'] = 'Por favor, contactar con el administrador.';
                    $notificacion['texto'] = 'Su Pago para la Tarea de '.$item->materia.', '.$item->tema.', no ha sido Aprobado.';
                    $pushClass->enviarNotificacion($notificacion, $alumno);
                }
            }
            else
            {
                $rechazado = false;
                $dataTarea['estado'] = 'Sin_Pago';
                $dataTarea['activa'] = false;
                Tarea::where('id', $item->id )->update( $dataTarea );
            }
            if (!$rechazado)
            {
                //lanzar notificacion al profesor
                $profesor = User::where('id', $item->user_id_pro)->first();
                $notificacion['tarea_id'] = $item->id;
                $notificacion['clase_id'] = 0;
                $notificacion['chat_id'] = 0;
                $notificacion['compra_id'] = 0;
                $notificacion['color'] = "cancelar";
                $notificacion['estado'] = 'Su Tarea ha sido Cancelada.';
                $notificacion['texto'] = 'Lo sentimos la Tarea de '.$item->materia.', '.$item->tema.', no ha sido confirmada.';
                $pushClass->enviarNotificacion($notificacion, $profesor);
            }
        }
    }
}