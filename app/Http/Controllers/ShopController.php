<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\Slide;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public const SORTS = [
        'menu_order' => 'Varsayılan sıralama',
        'date' => 'En yeniye göre sırala',
        'price' => 'Fiyata göre sırala: Düşükten yükseğe',
        'price-desc' => 'Fiyata göre sırala: Yüksekten düşüğe',
    ];

    public function home(Request $request)
    {
        // Eski WordPress arama adresi: /?s=...&post_type=product
        if ($request->filled('s')) {
            return redirect()->route('search', ['s' => $request->query('s')]);
        }

        $latest = Product::visible()->with(['images', 'categories:id'])->latest('published_at')->take((int) site('latest_count', 8))->get();
        $featured = Product::visible()->with(['images', 'categories:id'])->where('is_featured', true)->orderBy('sort')->latest('published_at')->take((int) site('featured_count', 12))->get();
        if ($featured->isEmpty()) {
            $featured = Product::visible()->with(['images', 'categories:id'])->whereHas('categories', fn ($q) => $q->where('slug', 'mackenzie-childs'))->latest('published_at')->take((int) site('featured_count', 12))->get();
        }
        $trend = Category::active()->where('is_trend', true)->orderBy('trend_sort')->get();
        $tableware = Category::where('slug', setting('home_grid_category', 'sofra-grubu'))->first();
        $tablewareProducts = $tableware
            ? $this->inCategory($tableware)->with(['images', 'categories:id'])->where('price', '>', 0)->latest('published_at')->take((int) site('grid_count', 12))->get()
            : collect();

        return view('shop.home', [
            'slides' => Slide::where('is_active', true)->orderBy('sort')->get(),
            'latest' => $latest,
            'featured' => $featured,
            'trend' => $trend,
            'tableware' => $tableware,
            'tablewareProducts' => $tablewareProducts,
        ]);
    }

    public function shop(Request $request)
    {
        return $this->listing($request, Product::visible(), null, 'Mağaza');
    }

    public function category(Request $request, string $path)
    {
        $slug = last(explode('/', trim($path, '/')));
        $category = Category::active()->where('slug', $slug)->firstOrFail();

        return $this->listing($request, $this->inCategory($category), $category, $category->name);
    }

    public function tag(Request $request, string $slug)
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        return $this->listing($request, Product::visible()->whereHas('tags', fn ($q) => $q->whereKey($tag->id)), null, $tag->name);
    }

    public function search(Request $request)
    {
        $s = trim((string) $request->query('s'));
        $q = Product::visible();
        if ($s !== '') {
            $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('sku', 'like', "%{$s}%"));
        }

        return $this->listing($request, $q, null, $s !== '' ? "“{$s}” için sonuçlar" : 'Arama');
    }

    private function inCategory(Category $category): Builder
    {
        $ids = $category->descendantIds();

        return Product::visible()->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $ids));
    }

    private function listing(Request $request, Builder $query, ?Category $category, string $title)
    {
        if ($request->filled('min')) {
            $query->where('price', '>=', (float) $request->query('min'));
        }
        if ($request->filled('max')) {
            $query->where('price', '<=', (float) $request->query('max'));
        }
        $sort = array_key_exists($request->query('orderby'), self::SORTS) ? $request->query('orderby') : 'menu_order';
        match ($sort) {
            'price' => $query->orderByRaw('price = 0')->orderBy('price'),
            'price-desc' => $query->orderByDesc('price'),
            default => $query->orderBy('sort')->latest('published_at'),
        };

        $products = $query->with(['images', 'categories:id'])->paginate((int) setting('per_page', 12))->withQueryString();

        return view('shop.listing', [
            'products' => $products,
            'category' => $category,
            'title' => $title,
            'sort' => $sort,
            'sidebarCategories' => Category::active()->whereNull('parent_id')->where('in_menu', true)->with('children')->withCount('products')->orderBy('sort')->get(),
        ]);
    }

    public function product(string $slug)
    {
        $product = Product::visible()->where('slug', $slug)->with(['images', 'categories.parent', 'tags'])->firstOrFail();

        $primary = $product->categories->sortByDesc(fn ($c) => $c->parent_id ? 1 : 0)->first();
        $related = $primary
            ? Product::visible()->with(['images', 'categories:id'])->whereKeyNot($product->id)
                ->whereHas('categories', fn ($q) => $q->whereKey($primary->id))->inRandomOrder()->take(8)->get()
            : collect();

        $prev = Product::visible()->where('published_at', '>', $product->published_at)->oldest('published_at')->first(['slug', 'name']);
        $next = Product::visible()->where('published_at', '<', $product->published_at)->latest('published_at')->first(['slug', 'name']);

        return view('shop.product', compact('product', 'primary', 'related', 'prev', 'next'));
    }
}
