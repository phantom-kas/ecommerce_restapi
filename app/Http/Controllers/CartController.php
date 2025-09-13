<?php

namespace App\Http\Controllers;

use App\Helpers\JsonResponseHelper;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    //

    public function addItemToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            '*.product_id' => 'required|integer|exists:products,id',
            '*.quantity'   => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            return JsonResponseHelper::standardResponse(400, null, 'Invalid input', ['errors' => $validator->errors()]);
        }
        // echo 'userid' . $request->user->id;
        // return JsonResponseHelper::standardResponse(200, $request->user->id, 'Invalid input');
        // die();
        $validated = $validator->validated();
        $cartData = Cart::addItemsToCart($validated, $request->user()->id);
        if (!$cartData) {

            return JsonResponseHelper::standardResponse(400, $cartData, 'Failed');
        }
        return JsonResponseHelper::standardResponse(200, $cartData, 'success');
    }


    public function getCartActive(Request $request)
    {
        return JsonResponseHelper::standardResponse(200, Cart::getItemsOfActiveCart(null, $request->user->id, true));
    }
}
