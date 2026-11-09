@extends('layouts.app')

@section('body_class', 'home')

@section('content')
@php($S = \App\Support\SiteContent::class)
@php($lines = array_values(array_filter(site('header_lines', []), fn ($l) => $l && ! str_starts_with(trim($l), '#'))))
@php($trio = site('trio', []))

{{-- Giriş: solda markanın kendi cümlesi, sağda slayt görselleri mozaik olarak --}}
@if ($S::show('slider'))
    <section class="hm-hero">
        <div class="wrap hm-hero-in">
            <div class="hm-hero-text">
                <p class="eyebrow" @if (str_starts_with((string) site('subtitle'), '#')) lang="en" @endif>{{ site('subtitle') }}</p>
                <h1 class="serif">{{ $lines ? implode(' ', $lines) : 'Evinizi küçük dokunuşlarla daha güzel, daha şık ve daha lükse ulaştırın.' }}</h1>
                <div class="hm-hero-cta">
                    <a href="{{ route('shop') }}" class="mbtn mbtn-dark">Mağazayı keşfet {{ icon('arrow-right') }}</a>
                    @if (! empty($trio[0]['link']) && ($trioNames[0] ?? null))
                        <a href="{{ url($trio[0]['link']) }}" class="mbtn mbtn-line">{{ $trioNames[0] }}</a>
                    @endif
                </div>
                @if ($S::show('strip'))
                    <ul class="hm-trust">
                        @foreach (site('strip', []) as $item)
                            <li>{{ icon($item['icon'] ?? 'check') }} {{ $item['text'] }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
            @if ($slides->isNotEmpty())
                <div @class(['hm-mosaic', 'n'.min(3, $slides->count())])>
                    @foreach ($slides->take(3) as $i => $slide)
                        <a href="{{ $slide->link ?: route('shop') }}" class="m{{ $i + 1 }}">
                            <img src="{{ media_url($slide->image) }}" alt="{{ $slide->title ?: 'Lusso Home Concept' }}" @if ($i) loading="lazy" @else fetchpriority="high" @endif>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif

{{-- Kategoriler: kemerli çerçeveler --}}
@if ($S::show('trend') && $trend->isNotEmpty())
    <section class="hm-sec">
        <div class="wrap">
            @include('partials.sec-head', ['title' => site('trend_title'), 'link' => route('shop'), 'linkText' => 'Tüm ürünler'])
            <div class="hm-arches">
                @foreach ($trend as $cat)
                    <a href="{{ $cat->url() }}" class="arch">
                        <span class="arch-img"><img src="{{ $cat->imageUrl() ?? asset('img/logo.png') }}" alt="" loading="lazy"></span>
                        <span class="arch-name">{{ $cat->name }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
@endif

@if ($S::show('latest') && $latest->isNotEmpty())
    <section class="hm-sec">
        <div class="wrap">
            @include('partials.sec-head', ['title' => site('latest_title'), 'link' => route('shop')])
            <div class="pgrid">
                @foreach ($latest as $product)
                    @include('partials.product-card')
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- Koleksiyonlar: üç afiş, altında kategori adı --}}
@if ($S::show('trio') && $trio)
    <section class="hm-sec">
        <div class="wrap hm-collections">
            @foreach ($trio as $i => $block)
                <a href="{{ url($block['link'] ?: '/shop') }}" class="coll">
                    <span class="coll-img"><img src="{{ $S::image($block) }}" alt="" loading="lazy" width="670" height="670"></span>
                    <span class="coll-cap">
                        <span class="coll-name serif">{{ $trioNames[$i] ?? 'Koleksiyon' }}</span>
                        <span class="coll-go">{{ icon('arrow-right') }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    </section>
@endif

{{-- Öne çıkanlar: dama desenli şerit içinde kaydırmalı ray --}}
@if ($S::show('featured') && $featured->isNotEmpty())
    <section class="hm-feature">
        <div class="wrap">
            @include('partials.sec-head', ['title' => site('featured_title'), 'eyebrow' => 'Seçtiklerimiz'])
            <div class="carousel" data-carousel style="--per:5;--per-md:3">
                <div class="carousel-track">
                    @foreach ($featured as $product)
                        @include('partials.product-card')
                    @endforeach
                </div>
                <button class="carousel-nav prev" type="button" data-prev aria-label="Önceki">{{ icon('left') }}</button>
                <button class="carousel-nav next" type="button" data-next aria-label="Sonraki">{{ icon('right') }}</button>
                <div class="carousel-dots" data-dots></div>
            </div>
        </div>
    </section>
@endif

@if ($S::show('promo'))
    @php($promo = site('promo'))
    <section class="hm-sec">
        <div class="wrap hm-story">
            <a href="{{ url($promo['link'] ?: '/shop') }}" class="hm-story-img"><img src="{{ $S::image($promo) }}" alt="" loading="lazy"></a>
            <div class="hm-story-text">
                @if ($promo['kicker'])<p class="eyebrow">{{ $promo['kicker'] }}</p>@endif
                @if ($promo['title'])<h2 class="serif">{{ \Illuminate\Support\Str::title(mb_strtolower($promo['title'])) }}</h2>@endif
                @if ($promo['text'])<p>{{ $promo['text'] }}</p>@endif
                @if ($promo['button'])<a href="{{ url($promo['link'] ?: '/shop') }}" class="mbtn mbtn-line">{{ rtrim($promo['button'], '.') }} {{ icon('arrow-right') }}</a>@endif
            </div>
        </div>
    </section>
@endif

@if ($S::show('grid') && $tableware && $tablewareProducts->isNotEmpty())
    <section class="hm-sec">
        <div class="wrap">
            @include('partials.sec-head', ['title' => $tableware->name, 'link' => $tableware->url(), 'linkText' => 'Tüm '.$tableware->name])
            <div class="pgrid">
                @foreach ($tablewareProducts as $product)
                    @include('partials.product-card')
                @endforeach
            </div>
        </div>
    </section>
@endif

@if ($S::show('info'))
    <section class="hm-perks">
        <div class="wrap hm-perks-in">
            @foreach (site('info', []) as $box)
                <div class="perk"><span class="perk-ic">{{ icon($box['icon'] ?? 'check') }}</span><div><h3>{{ $box['title'] }}</h3><p>{{ $S::text($box['text'] ?? '') }}</p></div></div>
            @endforeach
        </div>
    </section>
@endif

{{-- Mağazalarımız: alt bilgideki şube metninden --}}
@if ($S::show('stores') && $stores->isNotEmpty())
    <section class="hm-sec hm-stores-sec">
        <div class="wrap">
            @include('partials.sec-head', ['title' => 'Mağazalarımız', 'eyebrow' => 'Gelin, yakından görün', 'link' => route('contact'), 'linkText' => 'İletişim'])
            <div class="hm-stores">
                @foreach ($stores as $store)
                    @php([$label, $place] = array_pad(array_map('trim', explode(':', $store['title'], 2)), 2, null))
                    <div class="store">
                        <span class="store-ic">{{ icon('pin') }}</span>
                        <p class="store-label">{{ $place ? $label : 'Mağaza' }}</p>
                        <h3 class="serif">{{ $place ?: $label }}</h3>
                        @foreach ($store['lines'] as $line)
                            @if (preg_match('/^(tel|telefon)\s*:?\s*(.+)$/iu', $line, $m))
                                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $m[2]) }}">{{ icon('phone') }} {{ trim($m[2]) }}</a>
                            @elseif (preg_match('/^e-?mail\s*:?\s*(.+)$/iu', $line, $m))
                                <a href="mailto:{{ trim($m[1]) }}">{{ icon('paper-plane') }} {{ trim($m[1]) }}</a>
                            @else
                                <p>{{ $line }}</p>
                            @endif
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
@endsection
