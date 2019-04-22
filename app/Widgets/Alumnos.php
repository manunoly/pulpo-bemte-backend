<?php

namespace App\Widgets;

use App\Alumno;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;

class Alumnos extends BaseDimmer
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
        $count = \App\Alumno::count();
        $string = $count == 1 ? 'Alumno' : 'Alumnos';
        $plural = 'Alumnos';

        return view('voyager::dimmer', array_merge($this->config, [
            'icon'   => 'voyager-study',
            'title'  => "{$count} {$string}",
            'text'   => __('Tiene :count :string en su base de datos. Haga clic en el botón de abajo para ver todos los :plural. ', 
                        ['count' => $count, 'string' => Str::lower($string), 'plural' => Str::lower($plural)]),
            'button' => [
                'text' => 'Ver todos los '.$plural,
                'link' => route('voyager.alumnos.index'),
            ],
            'image' => env('APP_URL').'/images/alumno.png',
        ]));
    }

    /**
     * Determine if the widget should be displayed.
     *
     * @return bool
     */
    public function shouldBeDisplayed()
    {
        return app('VoyagerAuth')->user()->hasPermission('browse_alumnos');
    }
}