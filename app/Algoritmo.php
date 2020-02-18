<?php

namespace App;

use Illuminate\Support\Facades\Mail;

use App\Clase;
use App\Tarea;
use App\Pago;
use App\Multa;
use App\Alumno;
use App\User;
use App\Profesore;
use App\TareaProfesor;
use App\NotificacionesPushFcm;

class Algoritmo
{
    public function AsignarProfesorTarea($tarea)
    {
        $pesoExp = 30;
        $pesoAct = 25;
        $pesoCal = 20;
        $pesoMul = 25;
        $maxExp = 0;
        $maxAct = 0;
        $maxMul = 0;
        $propuestaSeleccionada = null;
        $datosProfesores = [];
        $profesores = TareaProfesor::where('tarea_id', $tarea->id)->where('estado', 'Solicitado')->get();
        $userAlumno = User::where('id', $tarea->user_id)->first();
        
        foreach($profesores as $aplica)
        {
            $profe = Profesore::where('user_id', $aplica->user_id)->first();
            if ($profe != null && $profe->activo && $profe->disponible && $profe->tareas)
            {
                $multas = 0;
                $datosMultas = Multa::where('user_id', $aplica->user_id)->where('estado', '!=', 'Cancelado')->get();
                foreach ($datosMultas as $tareaM)
                {
                    if ($tareaM->clase_id > 0)
                    {
                        $claseMulta = Clase::where('id', $tareaM->clase_id)->first();
                        if ($claseMulta != null)
                            $multas += $claseMulta->duracion;
                    }
                    if ($tareaM->tarea_id > 0)
                    {
                        $tareaMulta = Tarea::where('id', $tareaM->tarea_id)->first();
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

                $datosProfesores[] = array("novato" => $novato, "experiencia" => $experiencia,
                                        "multas" => $multas, "calificacion" => $calificacion,
                                        "formula" => 0,
                                        "id" => $aplica->id, "user_id" => $aplica->user_id,
                                        "tiempo" => $aplica->tiempo, "inversion" => $aplica->inversion);
                if ($maxExp < $experiencia)
                    $maxExp = $experiencia;
                if ($maxMul < $multas)
                    $maxMul = $multas;
                if ($maxAct < $aplica->tiempo)
                    $maxAct = $aplica->tiempo;
            }
        }
        foreach($datosProfesores as $tareaPro)
        {
            $tareaPro["formula"] = 
                ($pesoExp * 
                    (((1 - $tareaPro["novato"]) * $tareaPro["experiencia"] / $maxExp) + $tareaPro["novato"]))
                + ($pesoAct * (($maxAct - $tareaPro["tiempo"]) / $maxAct))
                + ($pesoCal * $tareaPro["calificacion"] / 5)
                + ($pesoMul * (($maxMul + 1 - $tareaPro["multas"]) / ($maxMul + 1)));
            if ($propuestaSeleccionada == null || 
                ($propuestaSeleccionada != null && $propuestaSeleccionada["formula"] < $tareaPro["formula"]))
                {
                    $propuestaSeleccionada = $tareaPro;
                }
        }
        if ($propuestaSeleccionada != NULL)
        {
            $pushClass = new NotificacionesPushFcm();
            foreach($profesores as $aplica)
            {
                $dataAplica['estado'] = 'Rechazado';
                if ($aplica->id == $propuestaSeleccionada["id"])
                {
                    $dataAplica['estado'] = 'Aprobado';
                }
                else
                {
                    try
                    {
                        //notificacion de rechazado
                        $userProfesor = User::where('id', $aplica->id)->first();
                        $texto = 'Lo sentimos, no ha sido confirmada la Tarea de '.$tarea->materia.', '.$tarea->tema;
                        $notificacion['titulo'] = 'Tarea No Confirmada';
                        $notificacion['tarea_id'] = $tarea->id;
                        $notificacion['clase_id'] = 0;
                        $notificacion['chat_id'] = 0;
                        $notificacion['compra_id'] = 0;
                        $notificacion['color'] = "cancelar";
                        $notificacion['estado'] = "";
                        $notificacion['texto'] = $texto;
                        $pushClass->enviarNotificacion($notificacion, $userProfesor);
                    }
                    catch (Exception $e) { }
                }
                TareaProfesor::where('id', $aplica->id )->update( $dataAplica );
            }
            $dataTarea['estado'] = 'Confirmado';
            $dataTarea['tiempo_estimado'] = $propuestaSeleccionada["tiempo"];
            $dataTarea['inversion'] = $propuestaSeleccionada["inversion"];
            $dataTarea['user_id_pro'] = $propuestaSeleccionada["user_id"];
            Tarea::where('id', $tarea->id )->update( $dataTarea );

            $userProfesor = User::where('id', $propuestaSeleccionada["user_id"])->first();
            $texto = 'La Tarea de '.$tarea->materia.', '.$tarea->tema.', ha sido confirmada por el profesor '
                        .$userProfesor->name.'. Por favor, realizar el pago.';

            //enviar notificacion al profesor y al alumno
            $notificacion['titulo'] = 'Tarea Confirmada';
            $notificacion['tarea_id'] = $tarea->id;
            $notificacion['clase_id'] = 0;
            $notificacion['chat_id'] = 0;
            $notificacion['compra_id'] = 0;
            $notificacion['color'] = "alumno";
            $notificacion['estado'] = "";
            $notificacion['texto'] = $texto;
            $pushClass->enviarNotificacion($notificacion, $userAlumno);
        }
        else
        {
            $dataTarea['estado'] = 'Sin_Profesor';
            $dataTarea['activa'] = false;
            Tarea::where('id', $tarea->id )->update( $dataTarea );
            //enviar notificacion al alumno
            $notificacion['titulo'] = 'Tarea Sin Profesor';
            $notificacion['tarea_id'] = $tarea->id;
            $notificacion['clase_id'] = 0;
            $notificacion['chat_id'] = 0;
            $notificacion['compra_id'] = 0;
            $notificacion['color'] = "cancelar";
            $notificacion['estado'] = "NO";
            $notificacion['texto'] = 'Lo sentimos, no encontramos profesor para la Tarea de '
                                    .$tarea->materia.', '.$tarea->tema.', por favor intentarlo nuevamente.';
            $pushClass = new NotificacionesPushFcm();
            $pushClass->enviarNotificacion($notificacion, $userAlumno);
        }
    }
}