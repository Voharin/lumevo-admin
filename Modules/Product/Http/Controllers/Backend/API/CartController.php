<?php

namespace Modules\Product\Http\Controllers\Backend\API;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Product\Http\Requests\CartRequest;
use Modules\Product\Models\Cart;
use Modules\Product\Models\Product;
use Modules\Product\Models\ProductVariation;
use Modules\Product\Transformers\CartResource;

class CartController extends Controller
{
    public function getCartList(Request $request)
    {
        $user_id = $request->input('user_id') ?? Auth::id();

        $perPage = $request->input('per_page', 10);

        $cart = Cart::where('user_id', $user_id)
            ->with('product', 'product_variation')
            ->whereHas('product', function ($query) {
                $query->whereNotNull('id');
            });

        $cart = $cart->paginate($perPage);

        $sumOfPrices = $cart->sum(function ($item) {
            return $item->product_variation->price * $item->qty;
        });

        $discount_price = getDiscountAmount($cart);

        $tax_amount = $cart->isEmpty() ? null : getTaxamount($sumOfPrices - $discount_price);
        $total_payable_amount = $cart->isEmpty() ? 0 : ($sumOfPrices - $discount_price + $tax_amount['total_tax_amount']);

        $amount = [
            'tax_included_amount' => $sumOfPrices,
            'discount_amount' => $discount_price,
            'total_amount' => $sumOfPrices - $discount_price,
            'tax_data' => $tax_amount,
            'tax_amount' => $cart->isEmpty() ? 0 : $tax_amount['total_tax_amount'],
            'total_payable_amount' => $total_payable_amount,
        ];

        $cartCollection = CartResource::collection($cart);

        return response()->json([
            'status' => true,
            'message' => 'Cart list retrieved successfully',
            'data' => [
                'cart' => $cartCollection,
                'amount' => $amount,
            ],
        ]);
    }

    public function addToCart(CartRequest $request)
    {
        $user_id = $request->input('user_id') ?? Auth::id();
        $product_id = $request->input('product_id');
        $product_variation_id = $request->input('product_variation_id');
        $qty = $request->input('qty', 1);

        $product = Product::findOrFail($product_id);
        $product_variation = ProductVariation::findOrFail($product_variation_id);

        $cart = Cart::where('user_id', $user_id)
            ->where('product_id', $product_id)
            ->where('product_variation_id', $product_variation_id)
            ->first();

        if ($cart) {
            $cart->qty += $qty;
            $cart->save();
        } else {
            $cart = Cart::create([
                'user_id' => $user_id,
                'product_id' => $product_id,
                'product_variation_id' => $product_variation_id,
                'qty' => $qty,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Product added to cart successfully',
            'data' => new CartResource($cart),
        ]);
    }

    public function store(CartRequest $request)
    {
        return $this->addToCart($request);
    }

    public function updateCart(CartRequest $request, $id)
    {
        $cart = Cart::findOrFail($id);
        $cart->qty = $request->input('qty');
        $cart->save();

        return response()->json([
            'status' => true,
            'message' => 'Cart updated successfully',
            'data' => new CartResource($cart),
        ]);
    }

    // public function removeFromCart($id)
    // {
    //     $cart = Cart::findOrFail($id);
    //     $cart->delete();

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'Product removed from cart successfully',
    //     ]);
    // }


    public function removeCart(Request $request)
    {
        $cart = Cart::where('id', $request->input('cart_id'))
                   ->where('user_id', Auth::id())
                   ->firstOrFail();

        $cart->delete();

        return response()->json([
            'status' => true,
            'message' => 'Cart item removed successfully'
        ]);
    }
}
