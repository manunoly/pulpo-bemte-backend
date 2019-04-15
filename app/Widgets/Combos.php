<?php

namespace App\Widgets;

use App\Combo;
use Illuminate\Support\Str;
use TCG\Voyager\Facades\Voyager;

class Combos extends BaseDimmer
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
        $count = \App\Combo::count();
        $string = $count == 1 ? 'Combo' : 'Combos';
        $plural = 'Combos';

        return view('voyager::dimmer', array_merge($this->config, [
            'icon'   => 'voyager-basket',
            'title'  => "{$count} {$string}",
            'text'   => __('Tiene :count :string en su base de datos. Haga clic en el botón de abajo para ver todos los :plural. ', 
                        ['count' => $count, 'string' => Str::lower($string), 'plural' => Str::lower($plural)]),
            'button' => [
                'text' => 'Ver todos los '.$plural,
                'link' => route('voyager.combos.index'),
            ],
            'image' => voyager_asset('images/widget-backgrounds/01.jpg'),
        ]));
    }

    /**
     * Determine if the widget should be displayed.
     *
     * @return bool
     */
    public function shouldBeDisplayed()
    {
        return app('VoyagerAuth')->user()->hasPermission('browse_combos');
    }
}