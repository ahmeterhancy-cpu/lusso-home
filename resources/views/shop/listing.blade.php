@extends('layouts.app')

@section('title', $title)
@if ($category && $category->description)
    @section('description', \Illuminate\Support\Str::limit(strip_tags($category->description), 155))
@endif

@section('page_title')
    @include('partials.page-title', [
        'title' => $title,
        'crumbs' => $category
            ? collect($category->trail())->map(fn ($c) => [$c->name, $c->is($category) ? null : $c->url()])->all()
            : [[$title, null]],
    ])
@endsection

@section('content')
<div class="container main-page">
    <div class="shop-layout">
        <aside class="sidebar">
            <div class="widget">
                <h5 class="widget-title">Ürün Kategorileri</h5>
                <ul class="cat-list">
                    @foreach ($sidebarCategories as $root)
                        <li @class(['current' => $category && ($category->id === $root->id || $category->parent_id === $root->id)])>
                            <a href="{{ $root->url() }}">{{ $root->name }}</a>
                            @if ($root->children->isNotEmpty() && $category && ($category->id === $root->id || $category->parent_id === $root->id))
                                <ul>
                                    @foreach ($root->children as $child)
                                        <li @class(['current' => $category->id === $child->id])><a href="{{ $child->url() }}">{{ $child->name }}</a></li>
                                    @endforeach
                                </ul>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="widget">
                <h5 class="widget-title">Fiyata Göre Filtrele</h5>
                <form method="get" class="price-filter">
                    @if (request('s'))<input type="hidden" name="s" value="{{ request('s') }}">@endif
                    <input type="number" name="min" min="0" placeholder="En az" value="{{ request('min') }}" aria-label="En az fiyat">
                    <input type="number" name="max" min="0" placeholder="En çok" value="{{ request('max') }}" aria-label="En çok fiyat">
                    <button class="btn btn-black" type="submit">Süz</button>
                </form>
            </div>
        </aside>

        <div>
            @if ($category && $category->description)
                <div class="cat-intro">{!! $category->description !!}</div>
            @endif
            <div class="shop-head">
                <span class="result-count">
                    @if ($products->total())
                        {{ $products->total() }} sonucun {{ $products->firstItem() }}–{{ $products->lastItem() }} arası gösteriliyor
                    @endif
                </span>
                <form method="get">
                    @foreach (request()->except(['orderby', 'page']) as $k => $v)
                        @if (is_string($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                    @endforeach
                    <select name="orderby" onchange="this.form.submit()" aria-label="Sıralama">
                        @foreach (\App\Http\Controllers\ShopController::SORTS as $key => $label)
                            <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            @if ($products->isEmpty())
                <p class="empty-note">Seçiminize uygun ürün bulunamadı.</p>
            @else
                <div class="products-grid cols-3">
                    @foreach ($products as $product)
                        @include('partials.product-card')
                    @endforeach
                </div>
                {{ $products->links() }}
            @endif
        </div>
    </div>
</div>
@endsection
