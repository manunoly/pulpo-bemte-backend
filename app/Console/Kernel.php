<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

USE App\Tarea;

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
    {
        $schedule->command('asignar:profesor:tarea')->everyMinute()
            ->when(function () 
            {
                $newDate = date("Y-m-d H:i:s", strtotime(date("d/m/y H:i:s"). '-20 minutes'));
                $tareas = Tarea::where('estado','Solicitado')->where('updated_at','<=', $newDate)->get();
                return $tareas->count() > 0;
            })
            ->sendOutputTo('C:\\virtual\\cron.txt');
            
        $schedule->command('asignar:pago:tarea')->everyMinute()
        ->when(function () 
        {
            $newDate = date("Y-m-d H:i:s", strtotime(date("d/m/y H:i:s"). '-20 minutes'));
            $tareas = Tarea::where('estado','Confirmado')->where('updated_at','<=', $newDate)->get();
            return $tareas->count() > 0;
        })
        ->sendOutputTo('C:\\virtual\\cron.txt');
        
        $schedule->command('terminar:tarea')->everyMinute()
        ->when(function () 
        {
            $newDate = date("Y-m-d");
            $newTime = date("H:i:s");
            $listado = Tarea::where('estado','Aceptado')->where('fecha_entrega','<=', $newDate)->get();
            $tareas = [];
            foreach($listado as $item)
            {
                if (($item->fecha_entrega != $newDate) || (($item->fecha_entrega == $newDate)
                        && ($item->hora_fin <= $newTime)))
                {
                    $tareas[] = $item;
                }
            }
            return count($tareas) > 0;
        })
        ->sendOutputTo('C:\\virtual\\cron.txt');
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
