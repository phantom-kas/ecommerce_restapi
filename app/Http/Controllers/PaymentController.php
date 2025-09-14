<?php

namespace App\Http\Controllers;

use App\Helpers\JsonResponseHelper;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    //

    public function initializeTransaction(Request $request)
    {
        $cartData =  Cart::addItemsToCart([], $request->user->id);
        // $origin = $request->getSchemeAndHttpHost();
        $orderId = $cartData['cartItems'][0]->order_id;
        $orderData = DB::select("SELECT id from orders where id = ? and status = 'pending' limit 1", [$orderId]);
        if (count($orderData) < 1) {
            return  JsonResponseHelper::standardResponse(200, null, 'Payment Already made');
        }
        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))
            ->post('https://api.paystack.co/transaction/initialize', [
                'email' => $request->user->email,
                // 'callback_url' => "https://hello.pstk.xyz/callback",
                'amount' => intval($cartData['total']['total']),
            ]);
        $body  = $response->json();
        if (! $body['status']) {
            return JsonResponseHelper::standardResponse(400, null, 'Error');
        }
        DB::table('payments')->insert([
            'user_id' => $request->user()->id,
            'ref'     => $body['data']['reference'],
            'amount'  => $cartData['total']['total'],
            'status'  => 'pending',
            'channel'  => 'paystack',
            'order_id' => $orderId
        ]);

        return JsonResponseHelper::standardResponse(200, [
            'authorization_url' => $body['data']['authorization_url'],
            'reference'         => $body['data']['reference'],
            'access_code'         => $body['data']['access_code'],
        ]);
    }

    public function paystackCallBack(Request $request)
    {
        $ref = $request->query('ref');
        if(!$ref){
            return  JsonResponseHelper::standardResponse(404, null, 'ref not provided');
        }
        $paymentData = DB::select("SELECT * from payments where ref = ? limit 1",[$ref]);
        if (count($paymentData) < 1) {
            return  JsonResponseHelper::standardResponse(404, null, 'Payment not found');
        }
        $paymentData = $paymentData[0];
        $orderId = $paymentData->order_id;
        $orderData = DB::select("SELECT * from orders where id = ? limit 1", [$orderId]);
        if (count($orderData) < 1) {
            return  JsonResponseHelper::standardResponse(404, null, 'Order not found');
        }
        $orderData = $orderData[0];
        if ($orderData->status == 'paid') {
            return  JsonResponseHelper::standardResponse(200, null, 'Payment already made');
        }
        if ($orderData->status != 'pending') {
            return  JsonResponseHelper::standardResponse(400, null, 'Error');
        }
        $response = Http::withToken(env('PAYSTACK_SECRET_KEY'))->get("https://api.paystack.co/transaction/verify/{$ref}");
        $body = $response->json();
        if (!$body['status']) {
            return  JsonResponseHelper::standardResponse(400, null, 'Verification failed');
        }
        $data = $body['data'];
        $amountPaid = $data['amount'];
        $paidAt = $data['paid_at'];

        if ($amountPaid < $orderData->amount) {
            return  JsonResponseHelper::standardResponse(400, null, 'Verification failed');
        }
        DB::table('payments')->where('ref', $ref)->update(['paid_at' => $paidAt, 'status' => 'succeeded']);
        $orderItems = Cart::getItemsOfActiveCart($orderId);
        foreach ($orderItems as  $value) {
            # code...
            DB::update("UPDATE products set quantity = quantity - ? , num_purchased = num_purchased + ? ,  total_revenue = total_revenue + ? where id = ?", [$value->quantity, $value->quantity,$value->amount, $value->product_id]);
            DB::table('orders')->where('id', $orderId)->update(['status' => 'paid', 'checkout_date' => now()]);
        }
        return  JsonResponseHelper::standardResponse(200, null, 'Checkout complete');
    }
}
