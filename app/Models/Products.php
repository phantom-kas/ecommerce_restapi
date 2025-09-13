<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Products extends Model
{
    //

    protected $fillable = ['name', 'description', 'price', 'media', 'quantity'];



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


    public static function getProduct($id)
    {
        $product = DB::select("SELECT p.* ,b.id as brand from products as p inner join products_brand as b on p.id = b.product_id where p.id = ? limit 1 ", [$id]);
        if (count($product) < 1) {
            return false;
        }
        return $product[0];
    }


    public static function updateReview($review, $id)
    {
        $product = DB::select("SELECT num_review,total_reviews,num_review, review_sumary from products where id = ? ", [$id]);
        $product = $product[0];

        $summary =   $product->review_sumary ? json_decode($product->review_sumary) : [];
        if (!$summary) {
            $summary = [];
        }
        if (isset($product->review_sumary[$review])) {
            $summary[$review] = $summary[$review] + 1;
        } else {
            $summary[$review] = 1;
        }
        DB::table('products')
            ->where('id', $id)
            ->update([
                'num_review' => $product->num_review + 1,
                'review_sumary' => json_encode($summary),
                'total_reviews' => intval($product->total_reviews) + intval($review),
                '$review' => $product->num_review + 1,
            ]);
    }
}
