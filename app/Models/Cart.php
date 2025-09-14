<?php

namespace App\Models;

use App\Helpers\JsonResponseHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\Foreach_;

class Cart extends Model
{
    //
    protected $table = 'orders';

    protected $fillable = ['user_id', 'total', 'quantity', 'amount', 'num_products', 'status'];

    public static function createNewOrder($userId)
    {


        $c =  Cart::create(['user_id' => $userId]);
        return $c->id;
    }
    public static function getActiveCartID($userId)
    {


        $CartData = DB::select("SELECT id from orders where user_id = ? and status = 'pending' limit 1", [$userId]);

        if (count($CartData) < 1) {
            return self::createNewOrder($userId);
        }
        return $CartData[0]->id;
    }

    public static function getItemsOfActiveCart($currentCart = null, $userId = null, $includeMedia = false)
    {
        if (!$currentCart) {
            $currentCart = self::getActiveCartID($userId);
        }
        $sql = '';
        if ($includeMedia) {
            $sql = ' p.media , p.description , p.created_at ,';
        }
        return DB::select("SELECT $sql oi.* , p.price,p.name,p.media, p.quantity as num_available from order_items as oi inner join products as p on oi.product_id = p.id where oi.order_id = ?", [$currentCart]);
    }

    public static function calCulateTotalOfCart($CartItems)
    {
        $total = 0;
        $totalUnits = 0;
        // return response()->json( $CartItems);

        // return ['total' => $CartItems,  'totalUnits' => $CartItems];
        foreach ($CartItems as $item) {
            if ($item->quantity > $item->num_available) {
                continue;
            }
            $item->price = intval($item->price);
            $total += $item->price * $item->quantity;
            $totalUnits += $item->quantity;
        }
        return ['total' => $total, 'totalUnits' => $totalUnits];
    }

    public static function  getProduct($cartId, $productId)
    {
        $p = DB::select("SELECT oi.order_id,oi.amount, p.price,p.quantity as num_available , oi.quantity from products as p left outer join order_items as oi on (oi.product_id = p.id and oi.order_id  = ?)  or oi.product_id is null where  p.id = ? limit 1", [$cartId, $productId]);
        if (count($p) < 1) {
            return false;
        }
       
        return $p[0];
    }

    public static function updateCartTotal($cartId, $amount, $numItems)
    {
        DB::table('orders')->where('id', $cartId)->update(['amount' => $amount, 'num_products' => $numItems]);
    }



    public static function addItemsToCart(array $productArr, $userId, $currentCart = null)
    {



        if (empty($userId) || !$userId) {
            return false;
        }


        if (!$currentCart) {
            $currentCart = self::getActiveCartID($userId);
        }
        // dd(1);
        // return JsonResponseHelper::standardResponse(200, $currentCart, 'Invalid input');
        foreach ($productArr as  $value) {
            $product = self::getProduct($currentCart, $value['product_id']);
            // return response()->json( $product);
            if (!$product) {
                continue;
            }
            if ($value['quantity'] + $product->quantity >= $product->num_available) {
                continue;
            } else if ($product->order_id == null) {
                DB::table('order_items')->insert(['amount' => $product->price * $value['quantity'], 'user_id' => $userId, 'quantity' => $value['quantity'], 'product_id' => $value['product_id'], 'order_id' => $currentCart]);
            } else {
                DB::table('order_items')->where('product_id', $value['product_id'])->where('order_id', $product->order_id)->update(['quantity' => ($value['quantity'] + $product->quantity), 'amount' => (intval($product->amount) + intval($product->price))]);
            };
        }


        //  throw new \InvalidArgumentException("User ID cannot be null");
        $currentCArtItems = self::getItemsOfActiveCart($currentCart);
        // return response()->json($currentCArtItems);

        $totalData = self::calCulateTotalOfCart($currentCArtItems);
        self::updateCartTotal($currentCart, $totalData['total'], $totalData['totalUnits']);
        return ['total' => $totalData, 'cartItems' => $currentCArtItems];
    }


    /**
     * @param mixed $cartItemId
     * @param int $count Passing -1 will completely remove this item, but 1 for example will reduce quantity by 1
     * @return false|array{totaldata: array{total: mixed, totalUnits: mixed}, cartitems: array}
     */
    public  static function removeProductFromCart($cartItemId, $userId, $count = 1)
    {
        $cartItem = DB::select(" SELECT order_id , quantity from order_items where id = ? and user_id = ? limit 1", [$cartItemId, $userId]);
        if (count($cartItem) < 1) {
            return false;
        }
        $cartItem =  $cartItem[0];
        if (($cartItem->quantity - $count) < 1 || $count < 0) {
            DB::table('order_items')->delete($cartItemId);
        } else {
            DB::table('order_items')->where('id', $cartItemId)->decrement('quantity', $count);
        }
        $currentCArtItems = self::getItemsOfActiveCart($cartItem->order_id);
        $totalData = self::calCulateTotalOfCart($currentCArtItems);
        self::updateCartTotal($cartItem->order_id, $totalData['total'], $totalData['totalUnits']);
        return ['total' => $totalData, 'cartItems' => $currentCArtItems];
    }

    /**
     * @param mixed $cartItemId
     * @param int $count Passing 0 will completely remove this item, but 1 for example will set the quantity to 1
     * @return false|array{totaldata: array{total: mixed, totalUnits: mixed}, cartitems: array}
     */

    public  static function setItemCount($cartItemId, $userId, $count)
    {
        $cartItem = DB::select("SELECT oi.order_id,oi.amount, p.price,p.quantity as num_available , oi.quantity from products as p inner join order_items as oi on oi.product_id = p.id  where oi.id = ?  and oi.user_id = ? limit 1", [$cartItemId, $userId]);
        if (count($cartItem) < 1) {
            return false;
        }
        $cartItem =  $cartItem[0];
        if ($count > $cartItem->num_available) {
            return false;
        }
        if ($count < 1) {
            DB::table('order_items')->delete($cartItemId);
        } else {
            DB::table('order_items')->where('id', $cartItemId)->update(['quantity' => $count, 'amount' => $count * $cartItem->price]);
        }
        $currentCArtItems = self::getItemsOfActiveCart($cartItem->order_id, null, true);
        $totalData = self::calCulateTotalOfCart($currentCArtItems);
        self::updateCartTotal($cartItem->order_id, $totalData['total'], $totalData['totalUnits']);
        return ['total' => $totalData, 'cartItems' => $currentCArtItems];
    }
}
