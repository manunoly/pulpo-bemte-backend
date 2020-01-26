<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Tarea;
use App\Algoritmo;
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
        $algoritmo = new Algoritmo();
        foreach($tareas as $item)
        {
            $algoritmo->AsignarProfesorTarea($item);
        }
    }
}