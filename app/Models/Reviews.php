<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reviews extends Model
{
    protected $table = 'reviews';
    protected $fillable = ['user_id','product_id','rating','review'];
    //
}
