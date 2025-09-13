<?php

namespace App\Http\Controllers;

use App\Helpers\JsonResponseHelper;
use App\Models\Products;
use App\Models\Reviews;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewsContoller extends Controller
{
    //

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|string|max:1',
            'review' => 'nullable|string',
        ]);

        $userId = $request->user->id;
        $hasOrdered = count(DB::select("SELECT oi.id from order_items as oi inner join orders as o on oi.order_id = o.id where oi.user_id = ? and oi.product_id = ? and o.status = 'succeeded'", [$userId, $validated['product_id']])) > 0;
        if (! $hasOrdered) {
            return response()->json(['message' => 'You can only review products you have purchased.'], 403);
        }

        if (count(DB::select("SELECT from review where user_id = ? and product_id = ? ", [$userId, $validated['product_id']])) > 0) {
            return JsonResponseHelper::standardResponse(200, null, "You can't review a product more than once");
        }




        $review = Reviews::create([
            'user_id' => $userId,
            'product_id' => $validated['product_id'],
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? null,
        ]);

        Products::updateReview($validated['review'],$validated['product_id']);

        return JsonResponseHelper::standardResponse(200, $review ,  'Review added successfully!');
    }


    public function deleteReview($id){

    }
}
