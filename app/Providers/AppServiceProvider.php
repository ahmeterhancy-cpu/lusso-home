<?php

namespace App\Providers;

use App\Models\Category;
use App\Services\CartService;
use App\Services\WishlistService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(CartService::class);
        $this->app->scoped(WishlistService::class);
    }

    public function boot(): void
    {
        Paginator::defaultView('partials.pagination');

        View::composer(['partials.product-card', 'shop.product'], fn ($view) => $view->with('wishlistIds', app(WishlistService::class)->ids()));

        View::composer('layouts.app', function ($view) {
            // Menü ağacı önbellekte DİZİ olarak tutulur (Eloquent nesnesi değil).
            $menu = Cache::remember('menu-tree', 600, fn () => Category::active()->whereNull('parent_id')->where('in_menu', true)
                ->with(['children' => fn ($q) => $q->active()->where('in_menu', true)])
                ->orderBy('sort')->get()
                ->map(fn ($c) => ['name' => $c->name, 'url' => $c->url(), 'children' => $c->children->map(fn ($ch) => ['name' => $ch->name, 'url' => $ch->url()])->all()])
                ->all());

            $cart = app(CartService::class);
            $view->with([
                'menu' => $menu,
                'cartCount' => $cart->count(),
                'cartTotal' => $cart->count() ? $cart->subtotal() : 0,
                'wishlistIds' => app(WishlistService::class)->ids(),
            ]);
        });
    }
}
