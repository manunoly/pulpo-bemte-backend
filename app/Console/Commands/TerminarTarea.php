<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Tarea;

class TerminarTarea extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'terminar:tarea';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Al pasar la fecha de entrega de la tarea se procede a Terminarla';

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
    { $newDate = date("Y-m-d");
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
        foreach($tareas as $item)
        {
            $dataTarea['estado'] = 'Terminado';
            Tarea::where('id', $item->id )->update( $dataTarea );
        }
    }
}
