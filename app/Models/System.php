<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class System extends Model
{
    //
    protected $table = 'system';
    protected $fillable = ['products_count', 'category_count', 'brand_count'];
}
