<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use App\Tarea;
use App\Pago;
use App\Multa;
use App\Alumno;
use App\User;
use App\Profesore;
use App\TareaProfesor;
use App\Mail\NotificacionTareas;
use App\NotificacionesPushFcm;

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
        $newDate = date("Y-m-d H:i:s", strtotime(date("d/m/y H:i:s"). '-60 minutes'));
        $tareas = Tarea::where('estado','Solicitado')->where('activa', true)
                        ->where('updated_at','<=', $newDate)->get();
        foreach($tareas as $item)
        {
            $profesores = TareaProfesor::where('tarea_id', $item->id)->where('estado', 'Solicitado')->get();
            $profeSeleccionado = NULL;
            $propuestaSeleccionada = NULL;
            $experienciaSeleccionada = 0;
            foreach($profesores as $aplica)
            {
                $multas = Multa::where('user_id', $aplica->user_id)->where('estado', '!=', 'Cancelado')->count();
                $experiencia = Pago::where('user_id', $aplica->user_id)->where('estado', '!=', 'Cancelado')
                                ->where('tarea_id', '>', 0)->count() - $multas;
                $valoracion = 0;
                $profe = Profesore::where('user_id', $aplica->user_id)->first();
                if ($profe != null && $profe->activo && $profe->disponible && $profe->tareas)
                {
                    if ($profeSeleccionado == null)
                    {
                        $profeSeleccionado = $profe;
                        $propuestaSeleccionada = $aplica;
                        $propuestaSeleccionada->tarea_id = $valoracion;
                        $experienciaSeleccionada = $experiencia;
                    }
                    else 
                    {
                        if ($experiencia == 0 && $multas == 0)
                        {
                            $valoracion += 3;
                        }
                        else if ($experiencia < 0)
                        {
                            $valoracion -= 2;
                        }
                        else if ($experiencia == 0)
                        {
                            $valoracion -= 1;
                        }
                        if ($experiencia >= $experienciaSeleccionada)
                        {
                            $valoracion += 2;
                        }
                        else
                        {
                            $valoracion -= 2;
                        }
                        if ($aplica->tiempo < $propuestaSeleccionada->tiempo)
                        {
                            $valoracion += 2;
                        }
                        else
                        {
                            $valoracion -= 2;
                        }
                        if ($aplica->inversion < $propuestaSeleccionada->inversion)
                        {
                            $valoracion += 1;
                        }
                        else
                        {
                            $valoracion -= 1;
                        }
                        if ($profeSeleccionado->calificacion < $profe->calificacion)
                        {
                            $valoracion += 2;
                        }
                        else
                        {
                            $valoracion -= 2;
                        }
                        $valoracion = $valoracion / 5;
                        if ($propuestaSeleccionada->tarea_id < $valoracion)
                        {
                            $profeSeleccionado = $profe;
                            $propuestaSeleccionada = $aplica;
                            $propuestaSeleccionada->tarea_id = $valoracion;
                            $experienciaSeleccionada = $experiencia;
                        }
                    }
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
                $estado  = 'Por favor, realizar el pago de su Tarea.';
                $estadoProf = 'El Alumno, debe realizar el Pago de la Tarea.';
                $userAlumno = User::where('id', $item->user_id)->first();
                $userProfesor = User::where('id', $propuestaSeleccionada->user_id)->first();
                if ($alumno->billtera >= $propuestaSeleccionada->tiempo)
                {
                    //quitar las horas al alumno
                    $dataAlumno['billetera'] = $alumno->billetera - $propuestaSeleccionada->tiempo;
                    $actualizado = Alumno::where('user_id', $item->user_id )->update( $dataAlumno );
                    if ($actualizado)
                    {
                        $dataTarea['estado'] = 'Aceptado';
                        $estado = 'Su Tarea ha sido asignada.';
                        $estadoProf = 'El Alumno ha realizado el Pago de la Tarea Exitosamente.';
                        //pagar al profesor
                        $profe = Profesore::where('user_id', $propuestaSeleccionada->user_id)->first();
                        $pagoProf = Pago::create([
                                    'user_id' => $propuestaSeleccionada->user_id,
                                    'tarea_id' => $item->id,
                                    'clase_id' => 0,
                                    'valor' => $propuestaSeleccionada->tiempo * $profe->valor_tarea,
                                    'horas' => $propuestaSeleccionada->tiempo,
                                    'estado' => 'Solicitado'
                                    ]);  
                        try 
                        {
                            Mail::to($alumno->email)->send(new NotificacionTareas($item, $userAlumno->name, 
                                                        $userProfesor->name, env('EMPRESA'), true));
                            Mail::to($profesor->email)->send(new NotificacionTareas($item, $userAlumno->name, 
                                                        $userProfesor->name, env('EMPRESA'), false));
                        }
                        catch (Exception $e) 
                        {
                        }      
                    }                    
                }
                Tarea::where('id', $item->id )->update( $dataTarea );

                //enviar notificacion al profesor y al alumno
                $dateTime = date("Y-m-d H:i:s");
                $notificacion['titulo'] = 'Tarea Confirmada';
                $notificacion['estado'] = $estado;
                $notificacion['texto'] = 'La Tarea '.$item->id.' ha sido confirmada por el profesor '
                                            .$userProfesor->name.', '.$dateTime;
                $pushClass = new NotificacionesPushFcm();
                $pushClass->enviarNotificacion($notificacion, $userAlumno);

                $notificacion['texto'] = 'La Tarea '.$item->id.' le ha sido Asignada, '.$dateTime;
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