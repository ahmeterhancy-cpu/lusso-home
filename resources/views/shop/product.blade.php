@extends('layouts.app')

@section('title', $product->meta_title ?: $product->name)
@section('body_class', 'single-product')
@section('description', $product->meta_description ?: \Illuminate\Support\Str::limit(trim(strip_tags($product->short_description ?: $product->description)), 155))
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

@php
    $wa = preg_replace('/\D/', '', setting('whatsapp', '905391099900'));
    $waText = rawurlencode('Merhaba, '.$product->name.' ('.$product->url().') hakkında bilgi almak istiyorum.');
    $inList = in_array($product->id, $wishlistIds, true);
    $badge = $product->in_stock ? ($product->campaign()['badge'] ?? null) : null;
    $description = trim((string) ($product->description ?: $product->short_description));
    $lowStock = $product->in_stock && $product->stock_qty !== null && $product->stock_qty > 0 && $product->stock_qty <= 3;
@endphp

@section('page_title')
<nav class="pd-crumbs" aria-label="Sayfa yolu">
    <div class="wrap">
        <div class="crumbs">
            <a href="{{ route('home') }}">Ana Sayfa</a>
            @if ($primary)
                @foreach ($primary->trail() as $c)<span class="sep">/</span><a href="{{ $c->url() }}">{{ $c->name }}</a>@endforeach
            @endif
            <span class="sep">/</span><span class="here">{{ $product->name }}</span>
        </div>
        <div class="products-nav">
            <a href="{{ $prev ? route('product', $prev->slug) : '#' }}" @class(['disabled' => ! $prev]) title="{{ $prev?->name }}" aria-label="Önceki ürün">{{ icon('left') }}</a>
            <a href="{{ $next ? route('product', $next->slug) : '#' }}" @class(['disabled' => ! $next]) title="{{ $next?->name }}" aria-label="Sonraki ürün">{{ icon('right') }}</a>
        </div>
    </div>
</nav>
@endsection

@section('content')
<div class="wrap">@include('partials.flash')</div>

<section class="wrap pd">
    {{-- Galeri: masaüstünde solda küçük resimler, telefonda kaydırmalı --}}
    <div class="pd-gallery" data-gallery>
        @if ($product->images->count() > 1)
            <div class="pd-thumbs">
                @foreach ($product->images as $i => $img)
                    <button type="button" @class(['active' => $i === 0]) data-thumb="{{ $i }}" aria-label="Görsel {{ $i + 1 }}"><img src="{{ $img->url() }}" alt="" loading="lazy"></button>
                @endforeach
            </div>
        @endif
        <div class="pd-stage">
            @if ($product->images->isNotEmpty())
                <div class="pd-track" data-track>
                    @foreach ($product->images as $i => $img)
                        <figure class="pd-slide"><img src="{{ $img->url() }}" alt="{{ $product->name }}{{ $i ? ' – görsel '.($i + 1) : '' }}" width="800" height="800" @if ($i) loading="lazy" @endif></figure>
                    @endforeach
                </div>
                <button type="button" class="pd-zoom" data-zoom aria-label="Büyüt">{{ icon('zoom') }}</button>
                @if ($product->images->count() > 1)
                    <button type="button" class="pd-arrow prev" data-step="-1" aria-label="Önceki görsel">{{ icon('left') }}</button>
                    <button type="button" class="pd-arrow next" data-step="1" aria-label="Sonraki görsel">{{ icon('right') }}</button>
                    <div class="pd-dots">@foreach ($product->images as $i => $img)<span @class(['active' => $i === 0])></span>@endforeach</div>
                @endif
            @else
                <div class="pd-noimg">{{ icon('image') }}</div>
            @endif
            <span class="labels">
                @if (! $product->in_stock)<span class="label out">Tükendi</span>
                @elseif ($product->onSale())<span class="label onsale">-{{ round((1 - $product->finalPrice() / (float) $product->price) * 100) }}%</span>@endif
            </span>
        </div>
    </div>

    <div class="pd-info">
        @if ($primary)<a href="{{ $primary->url() }}" class="eyebrow pd-cat">{{ $primary->name }}</a>@endif
        <h1 class="pd-title serif">{{ $product->name }}</h1>

        @if ($product->finalPrice() > 0)
            <div class="pd-price">
                @if ($product->onSale())
                    <ins>{{ money($product->finalPrice()) }}</ins><del>{{ money($product->price) }}</del>
                @else
                    <span>{{ money($product->price) }}</span>
                @endif
                @if ($badge)<span class="pd-badge">{{ $badge }}</span>@endif
            </div>
        @endif

        <p @class(['pd-stock', 'out' => ! $product->in_stock, 'low' => $lowStock])>
            <i></i>
            @if (! $product->in_stock) Stokta yok
            @elseif ($lowStock) Son {{ $product->stock_qty }} ürün
            @else Stokta, hemen gönderime hazır
            @endif
        </p>

        @if ($product->short_description && $product->description)
            <div class="pd-short">{!! $product->short_description !!}</div>
        @endif

        @if ($product->purchasable())
            <form class="pd-buy add-form" method="post" action="{{ route('cart.add') }}" data-add-form>
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <div class="qty">
                    <button type="button" data-qty="-1" aria-label="Azalt">−</button>
                    <input type="number" name="qty" value="1" min="1" max="{{ $product->stock_qty ?? 99 }}" aria-label="Adet">
                    <button type="button" data-qty="1" aria-label="Arttır">+</button>
                </div>
                <button type="submit" class="mbtn mbtn-gold pd-add">Sepete Ekle</button>
                <button type="button" class="pd-fav" data-wishlist="{{ $product->id }}" @class(['in-list' => $inList]) aria-label="Beğendiklerime ekle">{{ icon('heart') }}</button>
            </form>
            <a class="pd-ask" href="https://wa.me/{{ $wa }}?text={{ $waText }}" target="_blank" rel="noopener">{{ icon('whatsapp') }} Bu ürün hakkında soru sorun</a>
        @else
            <div class="pd-buy">
                <a class="mbtn mbtn-wa pd-add" href="https://wa.me/{{ $wa }}?text={{ $waText }}" target="_blank" rel="noopener">{{ icon('whatsapp') }} {{ $product->in_stock ? 'Fiyat ve bilgi için yazın' : 'Gelince haber verin' }}</a>
                <button type="button" class="pd-fav" data-wishlist="{{ $product->id }}" @class(['in-list' => $inList]) aria-label="Beğendiklerime ekle">{{ icon('heart') }}</button>
            </div>
        @endif

        <ul class="pd-perks">
            <li>{{ icon('truck') }}<span><strong>{{ setting('free_shipping_min', 5000) }} TL</strong> ve üzeri siparişlerde kargo ücretsiz</span></li>
            <li>{{ icon('shield') }}<span>Kartla, havale ile ya da kapıda ödeme</span></li>
            <li>{{ icon('pin') }}<span>Girne ve İskele mağazalarımızda yakından görebilirsiniz</span></li>
        </ul>

        <div class="pd-acc">
            @if ($description)
                <details open>
                    <summary>Açıklama {{ icon('down') }}</summary>
                    <div class="pd-acc-body">{!! $description !!}</div>
                </details>
            @endif
            <details>
                <summary>Kargo &amp; Teslimat {{ icon('down') }}</summary>
                <div class="pd-acc-body">{!! setting('shipping_text', '<p>'.setting('free_shipping_min', 5000).' TL ve üzeri alışverişlerinizde kargo ücretsizdir. Siparişleriniz Kıbrıs genelinde en kısa sürede adresinize teslim edilir.</p>') !!}</div>
            </details>
            <details>
                <summary>Ürün bilgileri {{ icon('down') }}</summary>
                <div class="pd-acc-body pd-meta">
                    @if ($product->sku)<div><span>Stok kodu</span>{{ $product->sku }}</div>@endif
                    @if ($product->categories->isNotEmpty())
                        <div><span>Kategoriler</span><p>@foreach ($product->categories as $c)<a href="{{ $c->url() }}">{{ $c->name }}</a>@if (! $loop->last), @endif @endforeach</p></div>
                    @endif
                    @if ($product->tags->isNotEmpty())
                        <div><span>Etiketler</span><p>@foreach ($product->tags as $t)<a href="{{ route('tag', $t->slug) }}">{{ $t->name }}</a>@if (! $loop->last), @endif @endforeach</p></div>
                    @endif
                </div>
            </details>
        </div>

        <div class="pd-share">
            <span>Paylaş</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($product->url()) }}" target="_blank" rel="noopener" aria-label="Facebook'ta paylaş">{{ icon('facebook') }}</a>
            <a href="https://wa.me/?text={{ rawurlencode($product->name.' '.$product->url()) }}" target="_blank" rel="noopener" aria-label="WhatsApp'ta paylaş">{{ icon('whatsapp') }}</a>
        </div>
    </div>
</section>

@if ($related->isNotEmpty())
    <section class="hm-sec pd-related">
        <div class="wrap">
            @include('partials.sec-head', ['title' => 'Bunları da sevebilirsiniz', 'eyebrow' => $primary?->name ?? site('subtitle'), 'link' => $primary?->url(), 'linkText' => 'Tümünü gör'])
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
        </div>
    </section>
@endif

{{-- Telefonda aşağı inince alttan çıkan satın alma çubuğu --}}
@if ($product->purchasable())
    <div class="pd-bar" data-pd-bar>
        @if ($product->mainImage())<img src="{{ $product->mainImage() }}" alt="">@endif
        <div class="pd-bar-info"><strong>{{ $product->name }}</strong><span>{{ money($product->finalPrice()) }}</span></div>
        <button type="button" class="mbtn mbtn-gold" data-add-to-cart="{{ $product->id }}">Sepete ekle</button>
    </div>
@endif

<div class="lightbox" data-lightbox>
    <button type="button" class="lb-close" data-lb-close aria-label="Kapat">{{ icon('close') }}</button>
    <button type="button" class="lb-prev" data-lb-prev aria-label="Önceki">{{ icon('left') }}</button>
    <img src="" alt="">
    <button type="button" class="lb-next" data-lb-next aria-label="Sonraki">{{ icon('right') }}</button>
</div>
@endsection
