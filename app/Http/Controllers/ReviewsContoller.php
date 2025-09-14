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
        $hasOrdered = count(DB::select("SELECT oi.id from order_items as oi inner join orders as o on oi.order_id = o.id where oi.user_id = ? and oi.product_id = ? and (o.status = 'succeeded' || o.status = 'paid')", [$userId, $validated['product_id']])) > 0;
        if (! $hasOrdered) {
            // return response()->json(['message' => 'You can only review products you have purchased.'], 403);
            return JsonResponseHelper::standardResponse(400, null, 'You can only review products you have purchased.');
        }

        if (count(DB::select("SELECT id from reviews where user_id = ? and product_id = ? ", [$userId, $validated['product_id']])) > 1) {
            return JsonResponseHelper::standardResponse(400, null, "You can't review a product more than once");
        }




        $review = Reviews::create([
            'user_id' => $userId,
            'product_id' => $validated['product_id'],
            'rating' => $validated['rating'],
            'review' => $validated['review'] ?? null,
        ]);

        Products::updateReview($validated['rating'], $validated['product_id']);

        return JsonResponseHelper::standardResponse(200, $review,  'Review added successfully!');
    }


    public function getProductReviews($id)
    {
        $cursor = request()->query('cursor', 1);
        $page = max(1, (int) $cursor);
        $perPage = request()->query('perpage', 20);
        $offset = ($page - 1) * $perPage;
        $reviews = DB::table('reviews as r')
            ->select('r.review', 'r.rating', 'u.name', 'u.image' ,'r.created_at')
            ->join('users as u', 'r.user_id', '=', 'u.id')
            ->where('r.product_id', $id)
            ->orderBy('r.id', 'desc')
            ->offset($offset)
            ->limit($perPage)
            ->get();
        return JsonResponseHelper::standardResponse(200, $reviews, 'Reviews fetched successfully!');
    }


    public function deleteReview($id) {

    }
}
