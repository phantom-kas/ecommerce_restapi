<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Products extends Model
{
    //

    protected $fillable = ['name', 'description', 'price', 'media', ''];



    public static function getMediaById($id)
    {

        $media = self::find($id)?->media;
        if (!$media) {
            return false;
        }
        $mediaArr = json_decode($media);
        // dd($mediaArr);
        if ($mediaArr) {
            return $mediaArr;
        }

        return [];
    }


    public static function getProduct($id){
        $product = DB::select("SELECT p.* ,b.id as brand from products as p inner join products_brand as b on p.id = b.product_id where p.id = ? limit 1 ", [$id]);
        if(count($product) < 1){
            return false;
        }
        return $product[0];
    }
}
