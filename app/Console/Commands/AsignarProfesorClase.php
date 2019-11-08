<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Clase;
use App\Alumno;
use App\User;
use App\NotificacionesPushFcm;
use Carbon\Carbon;

class AsignarProfesorClase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asignar:profesor:clase';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verificar asignación de un profesor a la clase solicitada al cumplirse el tiempo';

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
        $clases = Clase::whereIn('estado', ['Solicitado','Sin_Horas'])
                        ->where('activa', true)->where('updated_at','<=', $timestamp)->get();
        foreach($clases as $item)
        {
            if ($item->estado == 'Solicitado')
                $dataClase['estado'] = 'Sin_Profesor';
            else
                $dataClase['estado'] = 'Sin_Pago';
            $dataClase['activa'] = false;
            Clase::where('id', $item->id )->update( $dataClase );
        }
    }
}