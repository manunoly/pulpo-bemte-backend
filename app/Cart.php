<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    //
    protected $table ='cart';
    protected $fillable = [
		'id',
		'product_id',
		'user_id',
        'status_cart',
        'cant'
    ];
}