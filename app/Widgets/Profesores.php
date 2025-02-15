<?php

namespace App\Widgets;

use Auth;
use App\Profesore;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;

class Profesores extends BaseDimmer
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
        if (Auth::user()->tipo == 'Profesor')
            $count = \App\Profesore::where('user_id', Auth::user()->id)->count();
        else
            $count = \App\Profesore::count();

        $string = $count == 1 ? 'Profesor' : 'Profesores';
        $plural = 'Profesores';

        return view('voyager::dimmer', array_merge($this->config, [
            'icon'   => 'voyager-person',
            'title'  => "{$count} {$string}",
            'text'   => __('Tiene :count :string en su base de datos.', 
                        ['count' => $count, 'string' => Str::lower($string), 'plural' => Str::lower($plural)]),
            'button' => [
                'text' => 'Ver todos los '.$plural,
                'link' => route('voyager.profesores.index'),
            ],
            'image' => env('APP_URL').'/images/profesor.png',
        ]));
    }

    /**
     * Determine if the widget should be displayed.
     *
     * @return bool
     */
    public function shouldBeDisplayed()
    {
        return app('VoyagerAuth')->user()->hasPermission('browse_profesores');
    }
}