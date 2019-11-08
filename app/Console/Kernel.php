<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

use App\Tarea;
use App\Clase;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        'App\Console\Commands\AsignarProfesorTarea',
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {/*
        $schedule->command('asignar:profesor:tarea')->everyMinute()
            ->when(function () 
            {
                $newDate = date("Y-m-d H:i:s", strtotime(date("d/m/y H:i:s"). '-60 minutes'));
                $tareas = Tarea::where('estado','Solicitado')->where('activa', true)
                                ->where('updated_at','<=', $newDate)->get();
                return $tareas->count() > 0;
            })
            ->sendOutputTo('/var/www/etg/bemte-backend/cron.txt');
            
        $schedule->command('asignar:pago:tarea')->everyMinute()
            ->when(function () 
            {
                $newDate = date("Y-m-d H:i:s", strtotime(date("d/m/y H:i:s"). '-20 minutes'));
                $tareas = Tarea::whereIn('estado', ['Confirmado','Confirmando_Pago'])
                            ->where('activa', true)->where('updated_at','<=', $newDate)->get();
                return $tareas->count() > 0;
            })
            ->sendOutputTo('/var/www/etg/bemte-backend/cron.txt');
        */
        $schedule->command('terminar:tarea:clase')->everyMinute()
            ->when(function () 
            {
                $newDate = date("Y-m-d");
                $newTime = date("H:i:s");
                $listado = Tarea::where('estado','Aceptado')->where('fecha_entrega','<=', $newDate)
                                ->where('activa', true)->get();
                $tareas = [];
                foreach($listado as $item)
                {
                    if (($item->fecha_entrega != $newDate) || (($item->fecha_entrega == $newDate)
                            && ($item->hora_fin <= $newTime)))
                    {
                        $tareas[] = $item;
                    }
                }
                if (count($tareas) == 0)
                {
                    $listado = Clase::where('estado','Aceptado')->where('fecha','<=', $newDate)
                                    ->where('activa', true)->get();
                    $clases = [];
                    foreach($listado as $item)
                    {
                        if (($item->fecha != $newDate) || (($item->fecha == $newDate)
                                && ($item->hora_prof <= $newTime)))
                        {
                            $clases[] = $item;
                        }
                    }
                    return count($clases) > 0;
                }
                else
                {
                    return true;
                }
            })
            ->sendOutputTo('/var/www/etg/bemte-backend/cron.txt');

        $schedule->command('notificar:profesor:clase')->everyMinute()
            ->when(function () 
            {
                $timestamp = Carbon::now()->addMinutes(-15);
                $clases = Clase::where('estado','Solicitado')->where('activa', true)
                            ->where('seleccion_profesor', true)->where('updated_at','<=', $timestamp)->get();
                return $clases->count() > 0;
            })
            ->sendOutputTo('/var/www/etg/bemte-backend/cron.txt');
        
        $schedule->command('asignar:profesor:clase')->everyMinute()
            ->when(function () 
            {
                $timestamp = Carbon::now()->addMinutes(-60);
                $clases = Clase::whereIn('estado', ['Solicitado','Sin_Horas'])
                                ->where('activa', true)
                                ->where('updated_at','<=', $timestamp)->get();
                return $clases->count() > 0;
            })
            ->sendOutputTo('/var/www/etg/bemte-backend/cron.txt');

        $schedule->command('asignar:pago:clase')->everyMinute()
        ->when(function () 
        {
            $timestamp = Carbon::now()->addMinutes(-20);
            $clases = Clase::whereIn('estado', ['Confirmado','Confirmando_Pago'])
                            ->where('activa', true)->where('updated_at','<=', $timestamp)->get();
            return $clases->count() > 0;
        })
        ->sendOutputTo('/var/www/etg/bemte-backend/cron.txt');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
