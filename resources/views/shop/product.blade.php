@extends('layouts.app')

@section('title', $product->name)
@section('body_class', 'single-product')
@section('description', \Illuminate\Support\Str::limit(trim(strip_tags($product->short_description ?: $product->description)), 155))
@section('og')
    <meta property="og:type" content="product">
    <meta property="og:title" content="{{ $product->name }}">
    @if ($product->mainImage())<meta property="og:image" content="{{ $product->mainImage() }}">@endif
@endsection

@push('head')
<script type="application/ld+json">{!! json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product->name,
    'sku' => $product->sku,
    'image' => $product->images->map->url()->all(),
    'description' => trim(strip_tags($product->short_description ?: $product->description)) ?: null,
    'offers' => $product->finalPrice() > 0 ? [
        '@type' => 'Offer',
        'price' => number_format($product->finalPrice(), 2, '.', ''),
        'priceCurrency' => 'TRY',
        'availability' => $product->in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'url' => $product->url(),
    ] : null,
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!}</script>
@endpush

@section('page_title')
<div class="single-breadcrumbs">
    <div class="container">
        <a href="{{ $primary ? $primary->url() : route('shop') }}" class="back-btn" aria-label="Geri">{{ icon('arrow-left') }}</a>
        <div class="crumbs">
            <a href="{{ route('home') }}">{{ icon('home') }} Ana Sayfa</a>
            @if ($primary)
                @foreach ($primary->trail() as $c)<span class="sep">/</span><a href="{{ $c->url() }}">{{ $c->name }}</a>@endforeach
            @endif
            <span class="sep">/</span><span>{{ $product->name }}</span>
        </div>
        <div class="products-nav">
            <a href="{{ $prev ? route('product', $prev->slug) : '#' }}" @class(['disabled' => ! $prev]) title="{{ $prev?->name }}" aria-label="Önceki ürün">{{ icon('arrow-left') }}</a>
            <a href="{{ $next ? route('product', $next->slug) : '#' }}" @class(['disabled' => ! $next]) title="{{ $next?->name }}" aria-label="Sonraki ürün">{{ icon('arrow-right') }}</a>
        </div>
    </div>
</div>
@endsection

@section('content')
<div class="container product-single">
    @include('partials.flash')
    <div class="row">
        <div class="col-6">
            <div class="gallery" data-gallery>
                <div class="main-image">
                    @if ($product->images->isNotEmpty())
                        <img src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}" data-main width="800" height="800">
                        <button type="button" class="zoom-btn" data-zoom aria-label="Büyüt">{{ icon('zoom') }}</button>
                    @else
                        <div class="no-img" style="aspect-ratio:1;display:flex;align-items:center;justify-content:center;background:#f7f7f7;color:#ccc;font-size:60px">{{ icon('image') }}</div>
                    @endif
                </div>
                @if ($product->images->count() > 1)
                    <div class="thumbs">
                        @foreach ($product->images as $i => $img)
                            <button type="button" @class(['active' => $i === 0]) data-thumb="{{ $img->url() }}" aria-label="Görsel {{ $i + 1 }}"><img src="{{ $img->url() }}" alt="" loading="lazy"></button>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="col-6 summary">
            <h1 class="entry-title">{{ $product->name }}</h1>
            @if ($product->finalPrice() > 0)
                <span class="price">
                    @if ($product->onSale())<del>{{ money($product->price) }}</del><ins>{{ money($product->finalPrice()) }}</ins>@else{{ money($product->price) }}@endif
                </span>
            @endif
            @if ($product->short_description)
                <div class="short-desc">{!! $product->short_description !!}</div>
            @endif

            @if ($product->purchasable())
                <form class="add-form" method="post" action="{{ route('cart.add') }}" data-add-form>
                    @csrf
                    <input type="hidden" name="product_id" value="{{ $product->id }}">
                    <div class="qty">
                        <button type="button" data-qty="-1" aria-label="Azalt">−</button>
                        <input type="number" name="qty" value="1" min="1" max="{{ $product->stock_qty ?? 99 }}" aria-label="Adet">
                        <button type="button" data-qty="1" aria-label="Arttır">+</button>
                    </div>
                    <button type="submit" class="btn btn-primary">Sepete Ekle</button>
                </form>
            @elseif (! $product->in_stock)
                <p class="out-of-stock">Stokta yok</p>
            @endif

            <div class="summary-links">
                <button type="button" data-wishlist="{{ $product->id }}" @class(['in-list' => in_array($product->id, $wishlistIds, true)])>{{ icon('heart') }} <span>{{ in_array($product->id, $wishlistIds, true) ? 'Beğendiklerimde' : 'Beğendiklerime ekle' }}</span></button>
                @if (! $product->purchasable())
                    <a href="https://wa.me/{{ preg_replace('/\D/', '', setting('whatsapp', '905391099900')) }}?text={{ rawurlencode('Merhaba, '.$product->name.' ('.$product->url().') hakkında bilgi almak istiyorum.') }}" target="_blank" rel="noopener">{{ icon('whatsapp') }} Fiyat ve bilgi için WhatsApp'tan yazın</a>
                @endif
            </div>

            <div class="product-meta">
                @if ($product->sku)<div><strong>Stok kodu:</strong> {{ $product->sku }}</div>@endif
                @if ($product->categories->isNotEmpty())
                    <div><strong>Kategoriler:</strong> @foreach ($product->categories as $c)<a href="{{ $c->url() }}">{{ $c->name }}</a>@if (! $loop->last), @endif @endforeach</div>
                @endif
                @if ($product->tags->isNotEmpty())
                    <div><strong>Etiketler:</strong> @foreach ($product->tags as $t)<a href="{{ route('tag', $t->slug) }}">{{ $t->name }}</a>@if (! $loop->last), @endif @endforeach</div>
                @endif
                <div class="share"><strong>Paylaş:</strong>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($product->url()) }}" target="_blank" rel="noopener" aria-label="Facebook'ta paylaş">{{ icon('facebook') }}</a>
                    <a href="https://wa.me/?text={{ rawurlencode($product->name.' '.$product->url()) }}" target="_blank" rel="noopener" aria-label="WhatsApp'ta paylaş">{{ icon('whatsapp') }}</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="product-tabs" data-tabs>
    <div class="container">
        <ul class="tabs-nav">
            <li><button type="button" class="active" data-tab="desc">Açıklama</button></li>
            <li><button type="button" data-tab="ship">Kargo &amp; Teslimat</button></li>
        </ul>
        <div class="tab-panel active" data-panel="desc">
            <h2>Açıklama</h2>
            {!! $product->description ?: $product->short_description !!}
        </div>
        <div class="tab-panel" data-panel="ship">
            <h2>Kargo &amp; Teslimat</h2>
            {!! setting('shipping_text', '<p>'.setting('free_shipping_min', 5000).' TL ve üzeri alışverişlerinizde kargo ücretsizdir. Siparişleriniz Kıbrıs genelinde en kısa sürede adresinize teslim edilir.</p>') !!}
        </div>
    </div>
</div>

@if ($related->isNotEmpty())
    <section class="container related">
        @include('partials.section-title', ['title' => 'İlgili Ürünler', 'subtitle' => false])
        <div class="carousel" data-carousel style="--per:4;--per-md:3">
            <div class="carousel-track">
                @foreach ($related as $rel)
                    @include('partials.product-card', ['product' => $rel])
                @endforeach
            </div>
            <button class="carousel-nav prev" type="button" data-prev aria-label="Önceki">{{ icon('left') }}</button>
            <button class="carousel-nav next" type="button" data-next aria-label="Sonraki">{{ icon('right') }}</button>
            <div class="carousel-dots" data-dots></div>
        </div>
    </section>
@endif

<div class="lightbox" data-lightbox>
    <button type="button" class="lb-close" data-lb-close aria-label="Kapat">{{ icon('close') }}</button>
    <button type="button" class="lb-prev" data-lb-prev aria-label="Önceki">{{ icon('left') }}</button>
    <img src="" alt="">
    <button type="button" class="lb-next" data-lb-next aria-label="Sonraki">{{ icon('right') }}</button>
</div>
@endsection
