<?php

namespace App\Console\Commands;
use Illuminate\Support\Facades\Mail;
use Illuminate\Console\Command;
use App\User;
use App\Clase;
use App\AlumnoPago;
use App\Mail\NotificacionTareas;

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
        $clases = Clase::where('estado','Solicitado')->where('updated_at','<=', $newDate)->get();
        foreach($clases as $item)
        {
            $profesores = Clase::join('profesor_materia', 'profesor_materia.materia', '=', 'clases.materia')
                                ->join('sedes', 'sedes.nombre', '=', 'clases.ubicacion')
                                ->join('profesores', function ($join) {
                                    $join->on('profesores.user_id', '=', 'profesor_materia.user_id');
                                        $join->on('sedes.ciudad', '=', 'profesores.ciudad');})
                                ->join('users', 'users.id', '=', 'profesores.user_id')
                                ->where('profesores.activo', true)
                                ->where('profesores.clases', true)
                                ->where('profesores.disponible', true)
                                ->where('clases.id', $item->id)
                                ->select('profesores.nombres', 'profesores.apellidos', 'profesores.correo', 
                                            'users.token', 'users.sistema', 'users.id')
                                ->get();
            //lanzar notificaciones
        }
    }
}