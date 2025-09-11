<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    //
    protected $table = 'brand';
    protected $fillable = ['name', 'description'];



    public static function increaseProducts($id, $by = 1)
    {
        if ($by > 0) {
            return static::where('id', $id)->increment('num_products', $by);
        } elseif ($by < 0) {
            return static::where('id', $id)->decrement('num_products', abs($by));
        }

        return true; // nothing to change if $by = 0
    }
}
