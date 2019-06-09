<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Tarea;
use App\Pago;
use App\Multa;
use App\Profesore;
use App\TareaProfesor;

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
        $newDate = date("Y-m-d H:i:s", strtotime(date("d/m/y H:i:s"). '-20 minutes'));
        $tareas = Tarea::where('estado','Solicitado')->where('updated_at','<=', $newDate)->get();
        foreach($tareas as $item)
        {
            $profesores = TareaProfesor::where('tarea_id', $item->id)->where('estado', 'Solicitado')->get();
            $profeSeleccionado = NULL;
            $propuestaSeleccionada = NULL;
            $experienciaSeleccionada = 0;
            foreach($profesores as $aplica)
            {
                $multas = Multa::where('user_id', $aplica->user_id)->count();
                $experiencia = Pago::where('user_id', $aplica->user_id)->where('tarea_id', '>', 0)->count()
                                - $multas;
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
                Tarea::where('id', $item->id )->update( $dataTarea );
            }
            else
            {
                $dataTarea['estado'] = 'Sin_Profesor';
                Tarea::where('id', $item->id )->update( $dataTarea );
            }
        }
    }
}