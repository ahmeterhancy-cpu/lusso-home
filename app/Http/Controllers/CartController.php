<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\CartService;
use App\Services\WishlistService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(private CartService $cart) {}

    public function index()
    {
        $lines = $this->cart->lines();
        $subtotal = (float) $lines->sum('total');

        return view('shop.cart', [
            'lines' => $lines,
            'subtotal' => $subtotal,
            'shipping' => $this->cart->shipping($subtotal),
            'freeMin' => (float) setting('free_shipping_min', 5000),
        ]);
    }

    public function add(Request $request)
    {
        $data = $request->validate(['product_id' => 'required|integer', 'qty' => 'nullable|integer|min:1|max:99']);
        $product = Product::visible()->findOrFail($data['product_id']);

        if (! $product->purchasable()) {
            $msg = $product->in_stock ? 'Bu ürün için fiyat bilgisi almak üzere bizimle iletişime geçin.' : 'Bu ürün stokta yok.';

            return $request->expectsJson()
                ? response()->json(['ok' => false, 'message' => $msg], 422)
                : back()->with('error', $msg);
        }
        $this->cart->add($product, (int) ($data['qty'] ?? 1));

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'message' => '“'.$product->name.'” sepetinize eklendi.', 'cart' => $this->cart->summary()])
            : redirect()->route('cart')->with('success', '“'.$product->name.'” sepetinize eklendi.');
    }

    public function update(Request $request)
    {
        foreach ((array) $request->input('qty', []) as $id => $qty) {
            $this->cart->update((int) $id, (int) $qty);
        }

        return redirect()->route('cart')->with('success', 'Sepet güncellendi.');
    }

    public function remove(Request $request, int $id)
    {
        $this->cart->remove($id);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'cart' => $this->cart->summary()])
            : redirect()->route('cart')->with('success', 'Ürün sepetten çıkarıldı.');
    }

    public function summary()
    {
        return response()->json($this->cart->summary());
    }

    public function toggleWishlist(Request $request, WishlistService $wishlist)
    {
        $data = $request->validate(['product_id' => 'required|integer|exists:products,id']);
        $added = $wishlist->toggle((int) $data['product_id']);

        return $request->expectsJson()
            ? response()->json(['ok' => true, 'added' => $added, 'count' => count($wishlist->ids())])
            : back()->with('success', $added ? 'Ürün favorilerinize eklendi.' : 'Ürün favorilerinizden çıkarıldı.');
    }

    public function wishlist(WishlistService $wishlist)
    {
        $products = Product::visible()->with('images')->whereIn('id', $wishlist->ids())->get();

        return view('shop.wishlist', compact('products'));
    }
}
