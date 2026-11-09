<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Adresler eski WordPress sitesiyle aynı tutuldu (arama motoru sıralaması korunsun).
Route::get('/', [ShopController::class, 'home'])->name('home');
Route::get('/shop', [ShopController::class, 'shop'])->name('shop');
Route::get('/product-category/{path}', [ShopController::class, 'category'])->where('path', '.*')->name('category');
Route::get('/product/{slug}', [ShopController::class, 'product'])->name('product');
Route::get('/product-tag/{slug}', [ShopController::class, 'tag'])->name('tag');
Route::get('/arama', [ShopController::class, 'search'])->name('search');
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/xml/{slug}.xml', \App\Http\Controllers\XmlFeedController::class)->where('slug', '[a-z0-9-]+')->middleware('throttle:60,1')->name('xml.feed');
Route::get('/robots.txt', fn () => response(
    trim((string) setting('robots_txt', "User-agent: *\nDisallow: /admin\nDisallow: /cart\nDisallow: /checkout\nDisallow: /hesabim")).
    "\n\nSitemap: ".route('sitemap')."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']))->name('robots');

Route::get('/cart', [CartController::class, 'index'])->name('cart');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove/{id}', [CartController::class, 'remove'])->name('cart.remove');
Route::get('/cart/summary', [CartController::class, 'summary'])->name('cart.summary');
Route::post('/cart/kupon', [CartController::class, 'applyCoupon'])->middleware('throttle:15,1')->name('cart.coupon');
Route::post('/cart/kupon/kaldir', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');
Route::get('/begendiklerim', [CartController::class, 'wishlist'])->name('wishlist');
Route::post('/begendiklerim/toggle', [CartController::class, 'toggleWishlist'])->name('wishlist.toggle');

Route::get('/checkout', [CheckoutController::class, 'show'])->name('checkout');
Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('throttle:10,1')->name('checkout.store');
Route::get('/siparis/{order}/tesekkurler', [CheckoutController::class, 'thanks'])->name('order.thanks');
Route::get('/siparis/{order}/odeme', [CheckoutController::class, 'tikoPay'])->name('tiko.pay');
Route::match(['get', 'post'], '/tiko/donus/{order}', [CheckoutController::class, 'tikoReturn'])->name('tiko.return');
Route::post('/tiko/bildirim', [CheckoutController::class, 'tikoCallback'])->name('tiko.callback');
Route::post('/tiko/simulasyon/{order}', [CheckoutController::class, 'tikoSimulate'])->name('tiko.simulate');

Route::get('/hesabim', [AccountController::class, 'index'])->name('account');
Route::middleware('guest')->group(function () {
    Route::post('/hesabim/giris', [AccountController::class, 'login'])->middleware('throttle:8,1')->name('login');
    Route::post('/hesabim/kayit', [AccountController::class, 'register'])->middleware('throttle:5,1')->name('register');
    Route::get('/hesabim/lost-password', [AccountController::class, 'forgot'])->name('password.request');
    Route::post('/hesabim/lost-password', [AccountController::class, 'sendReset'])->middleware('throttle:5,1')->name('password.email');
    Route::get('/hesabim/sifre-sifirla/{token}', [AccountController::class, 'resetForm'])->name('password.reset');
    Route::post('/hesabim/sifre-sifirla', [AccountController::class, 'reset'])->name('password.update');
});
Route::middleware('auth')->group(function () {
    Route::post('/hesabim/cikis', [AccountController::class, 'logout'])->name('logout');
    Route::get('/hesabim/orders', [AccountController::class, 'orders'])->name('account.orders');
    Route::get('/hesabim/orders/{number}', [AccountController::class, 'order'])->name('account.order');
    Route::get('/hesabim/edit-account', [AccountController::class, 'edit'])->name('account.edit');
    Route::post('/hesabim/edit-account', [AccountController::class, 'update'])->name('account.update');
});
Route::match(['get', 'post'], '/siparis-takibi', [AccountController::class, 'track'])->middleware('throttle:20,1')->name('track');

Route::get('/iletisim', [PageController::class, 'contact'])->name('contact');
Route::post('/iletisim', [PageController::class, 'sendContact'])->middleware('throttle:5,1')->name('contact.send');
Route::get('/blog', [PageController::class, 'blog'])->name('blog');
Route::get('/blog/{slug}', [PageController::class, 'post'])->name('post');

// Panelden sipariş fişi yazdırma (yalnız yönetici).
Route::get('/admin/siparis/{order}/yazdir', function (\App\Models\Order $order) {
    abort_unless(auth()->user()?->is_admin, 403);

    return view('admin.order-print', ['order' => $order->load('items')]);
})->middleware('auth')->name('admin.order.print');

// İçerik sayfaları (hakkimizda, uyelik, teslimat-kargo …) — en sonda kalmalı.
Route::get('/{slug}', [PageController::class, 'show'])->where('slug', '[a-z0-9-]+')->name('page');
