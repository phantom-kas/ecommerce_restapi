<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Category extends Model
{
    //
    protected $table = 'category';
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

    public static function getCategoriesOfProduct($productId)
    {
        return DB::select('select category_id ,product_id from products_category where product_id = ?', [$productId]);
    }

    public static function increaseCategoryCountOfProduct($productId, $by = -1)
    {
        $categories = Category::getCategoriesOfProduct($productId);
        foreach ($categories as  $value) {
            # code...
            Category::increaseProducts($value->category_id, $by);
        }
        return count($categories);
    }
}
