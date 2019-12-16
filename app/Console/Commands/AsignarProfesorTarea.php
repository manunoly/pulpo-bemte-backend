<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Clase;
use App\Tarea;
use App\Pago;
use App\Multa;
use App\Alumno;
use App\User;
use App\Profesore;
use App\TareaProfesor;
use App\Mail\NotificacionTareas;
use App\NotificacionesPushFcm;
use Carbon\Carbon;

class AsignarProfesorTarea extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'asignar:profesor:tarea';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Asignar un profesor a la tarea solicitada al cumplirse el tiempo';

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
        $tareas = Tarea::where('estado','Solicitado')->where('activa', true)
                        ->where('updated_at','<=', $timestamp)->get();
        $pesoExp = 30;
        $pesoAct = 25;
        $pesoCal = 20;
        $pesoMul = 25;
        foreach($tareas as $item)
        {
            $profesores = TareaProfesor::where('tarea_id', $item->id)->where('estado', 'Solicitado')->get();
            $propuestaSeleccionada = null;

            $maxExp = 0;
            $maxAct = 0;
            $maxMul = 0;
            $datosProfesores = [];
            
            foreach($profesores as $aplica)
            {
                $profe = Profesore::where('user_id', $aplica->user_id)->first();
                if ($profe != null && $profe->activo && $profe->disponible && $profe->tareas)
                {
                    $multas = 0;
                    $datosMultas = Multa::where('user_id', $aplica->user_id)->where('estado', '!=', 'Cancelado')->get();
                    foreach ($datosMultas as $itemM)
                    {
                        if ($itemM->clase_id > 0)
                        {
                            $claseMulta = Clase::where('id', $itemM->clase_id)->first();
                            if ($claseMulta != null)
                                $multas += $claseMulta->duracion;
                        }
                        if ($itemM->tarea_id > 0)
                        {
                            $tareaMulta = Tarea::where('id', $itemM->tarea_id)->first();
                            if ($tareaMulta != null)
                                $multas += $tareaMulta->tiempo_estimado;
                        }
                    }
                
                    $experiencia = Pago::where('user_id', $aplica->user_id)
                                        ->where('estado', '!=', 'Cancelado')->sum('horas');
                    $novato = 0;
                    if ($experiencia < 10)
                        $novato = 1;
                    
                    $calificacion = 5;
                    if ($profe->calificacion != null)
                        $calificacion = $profe->calificacion;

                    $actividad = 0;

                    $datosProfesores[] = array("novato" => $novato, "experiencia" => $experiencia,
                                            "multas" => $multas, "calificacion" => $calificacion,
                                            "actividad" => $actividad, "formula" => 0,
                                            "id" => $aplica->id, "user_id" => $aplica->user_id,
                                            "tiempo" => $aplica->tiempo, "inversion" => $aplica->inversion);
                    if ($maxExp < $experiencia)
                        $maxExp = $experiencia;
                    if ($maxMul < $multas)
                        $maxMul = $multas;
                    if ($maxAct < $actividad)
                        $maxAct = $actividad;
                }
            }
            foreach($datosProfesores as $itemPro)
            {
                $itemPro->formula = 
                    ($pesoExp * 
                        (((1 - $itemPro->novato) * $itemPro->experiencia / $maxExp) + $itemPro->novato))
                    + ($pesoAct * (($maxAct - $itemPro->actividad) / $maxAct))
                    + ($pesoCal * $itemPro->calificacion / 5)
                    + ($pesoMul * (($maxMul + 1 - $itemPro->multas) / ($maxMul + 1)));
                if ($propuestaSeleccionada == null || 
                    ($propuestaSeleccionada != null && $propuestaSeleccionada->formula < $itemPro->formula))
                    {
                        $propuestaSeleccionada = $itemPro;
                    }
            }
            if ($propuestaSeleccionada != NULL)
            {
                foreach($profesores as $aplica)
                {
                    $dataAplica['estado'] = 'Rechazado';
                    if ($aplica->id == $propuestaSeleccionada->id)
                    {
                        $dataAplica['estado'] = 'Aprobado';
                    }
                    TareaProfesor::where('id', $aplica->id )->update( $dataAplica );
                }
                $dataTarea['estado'] = 'Confirmado';
                $dataTarea['tiempo_estimado'] = $propuestaSeleccionada->tiempo;
                $dataTarea['inversion'] = $propuestaSeleccionada->inversion;
                $dataTarea['user_id_pro'] = $propuestaSeleccionada->user_id;

                $alumno = Alumno::where('user_id', $item->user_id)->first();
                $estado  = 'Por favor, realizar el pago de la Tarea de '.$item->materia.', '.$item->tema.'.';
                $estadoProf = 'El Alumno, debe realizar el Pago de la Tarea de '.$item->materia.', '.$item->tema.'.';
                $userAlumno = User::where('id', $item->user_id)->first();
                $userProfesor = User::where('id', $propuestaSeleccionada->user_id)->first();
                Tarea::where('id', $item->id )->update( $dataTarea );

                //enviar notificacion al profesor y al alumno
                $notificacion['titulo'] = 'Tarea Confirmada';
                $notificacion['tarea_id'] = $item->id;
                $notificacion['clase_id'] = 0;
                $notificacion['chat_id'] = 0;
                $notificacion['compra_id'] = 0;
                $notificacion['estado'] = $estado;
                $notificacion['texto'] = 'La Tarea de '.$item->materia.', '.$item->tema
                                            .', ha sido confirmada por el profesor '
                                            .$userProfesor->name;
                $pushClass = new NotificacionesPushFcm();
                $pushClass->enviarNotificacion($notificacion, $userAlumno);

                $notificacion['texto'] = 'La Tarea de '.$item->materia.', '.$item->tema
                                            .' le ha sido Asignada';
                $notificacion['estado'] = $estadoProf;
                $pushClass->enviarNotificacion($notificacion, $userProfesor);
            }
            else
            {
                $dataTarea['estado'] = 'Sin_Profesor';
                $dataTarea['activa'] = false;
                Tarea::where('id', $item->id )->update( $dataTarea );
            }
        }
    }
}