<?php

namespace App;

use Illuminate\Database\Eloquent\Model;


class Combo extends Model
{
    protected $table = 'combos';
    protected $primaryKey = 'nombre'; 
    public $incrementing = false;
}
