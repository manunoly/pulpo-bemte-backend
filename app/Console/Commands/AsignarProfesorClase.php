<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Clase;
use App\Alumno;
use App\User;
use App\NotificacionesPushFcm;

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
        $newDate = date("Y-m-d H:i:s", strtotime(date("d/m/y H:i:s"). '-60 minutes'));
        $clase = Clase::where('estado','Solicitado')->where('activa', true)
                        ->where('updated_at','<=', $newDate)->get();
        foreach($clases as $item)
        {
            $dataClase['estado'] = 'Sin_Profesor';
            $dataClase['activa'] = false;
            Clase::where('id', $item->id )->update( $dataClase );
        }
    }
}