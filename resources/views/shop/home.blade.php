@extends('layouts.app')

@section('body_class', 'home')

@section('content')
@php($S = \App\Support\SiteContent::class)
@php($lines = array_values(array_filter(site('header_lines', []), fn ($l) => $l && ! str_starts_with(trim($l), '#'))))
@php($trio = site('trio', []))

{{-- Giriş: büyük yuvarlak slayt; marka cümlesi beyaz kartta sabit, arkada görseller döner.
     JS kapalıyken ilk görsel görünür kalır. --}}
@if ($S::show('slider'))
    <section class="hm-hero">
        <div class="wrap">
            <div class="hs" data-hero>
                <div class="hs-slides" data-hs-stage>
                    @forelse ($slides as $i => $slide)
                        <a href="{{ $slide->link ?: route('shop') }}" @class(['hs-slide', 'active' => $i === 0]) data-hs-slide @if ($i) aria-hidden="true" tabindex="-1" @endif>
                            <img src="{{ media_url($slide->image) }}" alt="{{ $slide->title ?: 'Lusso Home Concept' }}" @if ($i) loading="lazy" @else fetchpriority="high" @endif>
                        </a>
                    @empty
                        <span class="hs-slide active"></span>
                    @endforelse
                </div>

                <div class="hs-card">
                    <p class="eyebrow" @if (str_starts_with((string) site('subtitle'), '#')) lang="en" @endif>{{ site('subtitle') }}</p>
                    <h1 class="serif">{{ $lines ? implode(' ', $lines) : 'Evinizi küçük dokunuşlarla daha güzel, daha şık ve daha lükse ulaştırın.' }}</h1>
                    <div class="hm-hero-cta">
                        <a href="{{ route('shop') }}" class="mbtn mbtn-dark">Ürünleri Keşfet {{ icon('arrow-right') }}</a>
                        @if (! empty($trio[0]['link']) && ($trioNames[0] ?? null))
                            <a href="{{ url($trio[0]['link']) }}" class="mbtn mbtn-line">{{ $trioNames[0] }}</a>
                        @endif
                    </div>
                </div>

                @if ($slides->count() > 1)
                    <div class="hs-ctrl">
                        <span class="hs-count"><b data-hs-cur>01</b> / {{ str_pad($slides->count(), 2, '0', STR_PAD_LEFT) }}</span>
                        <div class="hs-bars">
                            @foreach ($slides as $i => $slide)
                                <button type="button" @class(['active' => $i === 0]) data-hs-go="{{ $i }}" aria-label="Görsel {{ $i + 1 }}"><i></i></button>
                            @endforeach
                        </div>
                        <button type="button" class="hs-nav" data-hs-step="-1" aria-label="Önceki görsel">{{ icon('left') }}</button>
                        <button type="button" class="hs-nav" data-hs-step="1" aria-label="Sonraki görsel">{{ icon('right') }}</button>
                    </div>
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
    <section class="hm-stores-sec">
        <div class="wrap"><div class="hm-stores-in">
            <div class="hm-stores-intro">
                <h2 class="serif">Mağazalarımız</h2>
                <p class="hm-stores-lead">Dokusunu, rengini, ağırlığını görmek için {{ $stores->count() }} mağazamızdan birine uğrayın. Gelmeden önce aramanız yeterli; aradığınız ürünü hazır ederiz.</p>
                <div class="hm-stores-links">
                    @if ($storeEmail)<a href="mailto:{{ $storeEmail }}">{{ icon('paper-plane') }} {{ $storeEmail }}</a>@endif
                    <a href="{{ route('contact') }}">İletişim sayfası {{ icon('arrow-right') }}</a>
                </div>
            </div>
            <ol class="hm-stores">
                @foreach ($stores as $store)
                    <li class="store">
                        <div class="store-text">
                            <p class="store-label">{{ icon('pin') }} {{ $store['label'] }}</p>
                            <h3 class="serif">{{ $store['place'] }}</h3>
                            @if ($store['note'])<p class="store-note">{{ $store['note'] }}</p>@endif
                            @foreach ($store['lines'] as $line)<p class="store-note">{{ $line }}</p>@endforeach
                        </div>
                        <div class="store-acts">
                            @foreach ($store['phones'] as $phone)
                                <a class="store-btn" href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ icon('phone') }} {{ $phone }}</a>
                            @endforeach
                            <a class="store-btn line" href="{{ $store['map'] }}" target="_blank" rel="noopener">Yol tarifi {{ icon('arrow-right') }}</a>
                        </div>
                    </li>
                @endforeach
            </ol>
        </div></div>
    </section>
@endif
@endsection
