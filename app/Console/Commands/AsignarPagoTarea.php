<?php

namespace App\Console\Commands;
use Illuminate\Support\Facades\Mail;
use Illuminate\Console\Command;
use App\User;
use App\Tarea;
use App\AlumnoPago;
use App\Mail\NotificacionTareas;

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
        $tareas = Tarea::where('estado','Confirmado')->where('updated_at','<=', $newDate)->get();
        foreach($tareas as $item)
        {
            $transfer = AlumnoPago::where('user_id', $item->user_id)->where('tarea_id', $item->id)
                            ->where('estado', 'Aprobado')->first();
            if ($transfer != null)
            {
                $dataTarea['estado'] = 'Aceptado';
                $alumno = User::where('id', $item->user_id)->first();
                $profesor = User::where('id', $item->user_id_pro)->first();
                try 
                {
                    Mail::to($alumno->email)->send(new NotificacionTareas($item, $alumno->name, $profesor->name, 
                                                    env('EMPRESA'), true));
                    Mail::to($profesor->email)->send(new NotificacionTareas($item, $alumno->name, $profesor->name, 
                                                    env('EMPRESA'), false));
                }
                catch (Exception $e) 
                {
                    return response()->json(
                                ['success' => 'No se ha podido enviar el correo',
                                'detalle' => $e->getMessage()], 200);
                }
            }
            else
            {
                $dataTarea['estado'] = 'Sin_Pago';
            }
            Tarea::where('id', $item->id )->update( $dataTarea );
        }
    }
}