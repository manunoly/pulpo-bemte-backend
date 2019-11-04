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
        $newDate = date("Y-m-d H:i:s", strtotime(date("d/m/y H:i:s"). '-20 minutes'));
        $tareas = Tarea::whereIn('estado', ['Confirmado','Confirmando_Pago'])
                        ->where('activa', true)->where('updated_at','<=', $newDate)->get();
        $pushClass = new NotificacionesPushFcm();
        foreach($tareas as $item)
        {
            $rechazado = true;
            $transfer = AlumnoPago::where('user_id', $item->user_id)->where('tarea_id', $item->id)->first();
            $profesor = User::where('id', $item->user_id_pro)->first();
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
                    $alumno = User::where('id', $item->user_id)->first();
                    try 
                    {
                        Mail::to($alumno->email)->send(new Notificacion($alumno->name, 
                                'Su Pago para la Tarea de '.$item->materia.', '.$item->tema.', no ha sido Aprobado.', '',
                                'Por favor, contactar con el administrador.', env('EMPRESA')));
                        Mail::to($profesor->email)->send(new Notificacion($profesor->name, 
                                'El Pago del Alumno para la Clase de '.$item->materia.', '.$item->tema.', no ha sido Aprobado.', '',
                                'Su clase ha sido Cancelada.', env('EMPRESA')));
                    }
                    catch (Exception $e) 
                    {
                        $messages["error"] = 'No se ha podido enviar el correo';
                        return redirect()->back()->withErrors($messages)->withInput();
                    }
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
                //lanzar notificacion al profesores
                $notificacion['tarea_id'] = $item->id;
                $notificacion['clase_id'] = 0;
                $notificacion['chat_id'] = 0;
                $notificacion['compra_id'] = 0;
                $notificacion['estado'] = 'NO';
                $notificacion['texto'] = 'Lo sentimos la Tarea de '.$item->materia.', '.$item->tema.', no ha sido confirmada.';
                $pushClass->enviarNotificacion($notificacion, $profesor);
            }
        }
    }
}