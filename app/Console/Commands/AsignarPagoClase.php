<?php

namespace App\Console\Commands;
use Illuminate\Support\Facades\Mail;
use Illuminate\Console\Command;
use App\User;
use App\Clase;
use App\AlumnoPago;
use App\AlumnoCompra;
use App\Mail\Notificacion;
use App\NotificacionesPushFcm;
use Carbon\Carbon;

class AsignarPagoClase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asignar:pago:clase';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comprobar el pago de la clase al cumplirse el tiempo';

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
        $clases = Clase::whereIn('estado', ['Confirmado','Confirmando_Pago'])
                        ->where('activa', true)->where('updated_at','<=', $timestamp)->get();
        $pushClass = new NotificacionesPushFcm();
        foreach($clases as $item)
        {
            $rechazado = true;
            $transfer = AlumnoPago::where('user_id', $item->user_id)->where('clase_id', $item->id)->first();
            if ($transfer != null)
            {
                if ($transfer->estado != 'Aprobado')
                {
                    $rechazado = false;
                    $dataClase['estado'] = 'Pago_Rechazado';
                    $dataClase['activa'] = false;
                    Clase::where('id', $item->id )->update( $dataClase );
                    $dataTransfer['estado'] = 'Rechazado';
                    if ($transfer->estado == 'Solicitado')
                        AlumnoPago::where('id', $transfer->id)->update( $dataTransfer );
                    if ($transfer->combo_id > 0)
                        AlumnoCompra::where('id', $transfer->combo_id)->update( $dataTransfer );
                    
                    //lanzar notificacion al alumno
                    $alumno = User::where('id', $item->user_id)->first();
                    $notificacion['clase_id'] = $item->id;
                    $notificacion['tarea_id'] = 0;
                    $notificacion['chat_id'] = 0;
                    $notificacion['compra_id'] = 0;
                    $notificacion['color'] = "cancelar";
                    $notificacion['estado'] = 'Por favor, contactar con el administrador.';
                    $notificacion['titulo'] = 'Pago No Aprobado';
                    $notificacion['texto'] = 'Su Pago para la Clase de '.$item->materia.', '.$item->tema.', no ha sido Aprobado.';
                    $pushClass->enviarNotificacion($notificacion, $alumno);
                }
            }
            else
            {
                $rechazado = false;
                $dataClase['estado'] = 'Sin_Pago';
                $dataClase['activa'] = false;
                Clase::where('id', $item->id )->update( $dataClase );
            }
            if (!$rechazado)
            {
                //lanzar notificacion al profesor
                $profesor = User::where('id', $item->user_id_pro)->first();
                $notificacion['clase_id'] = $item->id;
                $notificacion['tarea_id'] = 0;
                $notificacion['chat_id'] = 0;
                $notificacion['compra_id'] = 0;
                $notificacion['color'] = "cancelar";
                $notificacion['estado'] = 'Su clase ha sido Cancelada.';
                $notificacion['titulo'] = 'Clase No Confirmada';
                $notificacion['texto'] = 'Lo sentimos la Clase de '.$item->materia.', '.$item->tema.', no ha sido confirmada.';
                $pushClass->enviarNotificacion($notificacion, $profesor);
            }
        }
    }
}