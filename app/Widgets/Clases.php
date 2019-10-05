<?php

namespace App\Widgets;

use Auth;
use App\Clase;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;

class Clases extends BaseDimmer
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
            $count = \App\Clase::where('user_id_pro', Auth::user()->id)
                    ->orWhere('user_id_pro', null)->count();
        else
            $count = \App\Clase::count();
        $string = $count == 1 ? 'Clase' : 'Clases';
        $plural = 'Clases';

        return view('voyager::dimmer', array_merge($this->config, [
            'icon'   => 'voyager-book',
            'title'  => "{$count} {$string}",
            'text'   => __('Tiene :count :string en su base de datos.', 
                        ['count' => $count, 'string' => Str::lower($string), 'plural' => Str::lower($plural)]),
            'button' => [
                'text' => 'Ver todos las '.$plural,
                'link' => route('voyager.clases.index'),
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
        return app('VoyagerAuth')->user()->hasPermission('browse_clases');
    }
}