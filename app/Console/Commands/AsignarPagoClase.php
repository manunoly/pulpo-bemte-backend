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
        $newDate = date("Y-m-d H:i:s", strtotime(date("d/m/y H:i:s"). '-20 minutes'));
        $clases = Clase::whereIn('estado', ['Confirmado','Confirmando_Pago'])
                        ->where('activa', true)->where('updated_at','<=', $newDate)->get();
        $pushClass = new NotificacionesPushFcm();
        foreach($clases as $item)
        {
            $rechazado = true;
            $transfer = AlumnoPago::where('user_id', $item->user_id)->where('clase_id', $item->id)->first();
            $profesor = User::where('id', $item->user_id_pro)->first();
            if ($transfer != null)
            {
                if ($transfer->estado != 'Aprobado')
                {
                    $rechazado = false;
                    $dataClase['estado'] = 'Pago_Rechazado';
                    $dataClase['activa'] = false;
                    Clase::where('id', $item->id )->update( $dataClase );
                    $dataTransfer['estado'] = 'Rechazado';
                    if ($trasnfer->estado == 'Solicitado')
                        AlumnoPago::where('id', $transfer->id)->update( $dataTransfer );
                    if ($transfer->combo_id > 0)
                        AlumnoCompra::where('id', $transfer->combo_id)->update( $dataTransfer );
                    $alumno = User::where('id', $item->user_id)->first();
                    try 
                    {
                        Mail::to($alumno->email)->send(new Notificacion($alumno->name, 
                                'Su Pago para la Clase de '.$item->materia.', '.$item->tema.', no ha sido Aprobado.', '',
                                'Por favor, contactar con el administrador.', env('EMPRESA')));
                        Mail::to($profesor->email)->send(new Notificacion($profesor->name, 
                                'El Pago del Alumno para la Clase de '.$item->materia.', '.$item->tema.', no ha sido Aprobado.', '',
                                'Su clase ha sido Cancelada.', env('EMPRESA')));
                    }
                    catch (Exception $e) 
                    {
                        return response()->json(['success' => 'No se ha podido enviar el correo',
                                    'detalle' => $e->getMessage()], 200);
                    }
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
                //lanzar notificacion al profesores
                $notificacion['clase_id'] = $item->id;
                $notificacion['tarea_id'] = 0;
                $notificacion['chat_id'] = 0;
                $notificacion['compra_id'] = 0;
                $notificacion['estado'] = 'NO';
                $notificacion['texto'] = 'Lo sentimos la Clase de '.$item->materia.', '.$item->tema.', no ha sido confirmada.';
                $pushClass->enviarNotificacion($notificacion, $profesor);
            }
        }
    }
}