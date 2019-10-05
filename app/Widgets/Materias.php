<?php

namespace App\Widgets;

use App\Materia;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;

class Materias extends BaseDimmer
{
    /**
     * The configuration array.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Treat this method as a controller action.
     * Return view() or other content to display.
     */
    public function run()
    {
        $count = \App\Materia::count();
        $string = $count == 1 ? 'Materia' : 'Materias';
        $plural = 'Materias';

        return view('voyager::dimmer', array_merge($this->config, [
            'icon'   => 'voyager-documentation',
            'title'  => "{$count} {$string}",
            'text'   => __('Tiene :count :string en su base de datos.', 
                        ['count' => $count, 'string' => Str::lower($string), 'plural' => Str::lower($plural)]),
            'button' => [
                'text' => 'Ver todos las '.$plural,
                'link' => route('voyager.materias.index'),
            ],
            'image' => env('APP_URL').'/images/combo.png',
        ]));
    }

    /**
     * Determine if the widget should be displayed.
     *
     * @return bool
     */
    public function shouldBeDisplayed()
    {
        return app('VoyagerAuth')->user()->hasPermission('browse_tareas');
    }
}