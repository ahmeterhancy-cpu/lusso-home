@extends('layouts.app')

@section('body_class', 'home')

@section('content')
@php($S = \App\Support\SiteContent::class)
<div class="container main-page" style="padding-top:0">

    {{-- Slider: yazı yarısı + görsel yarısı, sırayla yer değiştirir --}}
    @if ($S::show('slider') && $slides->isNotEmpty())
        <section class="hero" data-hero>
            @foreach ($slides as $i => $slide)
                <div @class(['slide', 'active' => $i === 0, 'reverse' => $i % 2 === 1])>
                    <div class="slide-text">
                        @if ($slide->kicker)<span class="kicker">{{ $slide->kicker }}</span>@endif
                        @if ($slide->title)<h2>{{ $slide->title }}</h2>@endif
                        @if ($slide->show_logo)
                            <a href="{{ $slide->link ?: route('shop') }}" class="slide-logo"><img src="{{ asset('img/logo.png') }}" alt="Lusso Home Concept"></a>
                        @endif
                    </div>
                    <div class="slide-img">
                        <a href="{{ $slide->link ?: route('shop') }}"><img src="{{ media_url($slide->image) }}" alt="{{ $slide->title }}" @if ($i) loading="lazy" @endif></a>
                    </div>
                </div>
            @endforeach
            @if ($slides->count() > 1)
                <div class="hero-dots">
                    @foreach ($slides as $i => $slide)<button type="button" @class(['active' => $i === 0]) aria-label="Slayt {{ $i + 1 }}"></button>@endforeach
                </div>
            @endif
        </section>
    @endif

    @if ($S::show('trio'))
        <section class="image-trio">
            @foreach (site('trio', []) as $block)
                <a href="{{ $block['link'] ?: route('shop') }}"><img src="{{ $S::image($block) }}" alt="Lusso Home Concept" width="670" height="670"></a>
            @endforeach
        </section>
    @endif

    @if ($S::show('trend') && $trend->isNotEmpty())
        <section class="home-section">
            @include('partials.section-title', ['title' => site('trend_title')])
            <div class="carousel" data-carousel style="--per:5;--per-md:3">
                <div class="carousel-track">
                    @foreach ($trend as $cat)
                        <div class="cat-card">
                            <a href="{{ $cat->url() }}" class="cat-img"><img src="{{ $cat->imageUrl() ?? asset('img/logo.png') }}" alt="{{ $cat->name }}" loading="lazy"></a>
                            <h3><a href="{{ $cat->url() }}">{{ $cat->name }}</a></h3>
                            <a href="{{ $cat->url() }}" class="more">Ürünlere Bak</a>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($S::show('latest') && $latest->isNotEmpty())
        <section class="home-section">
            @include('partials.section-title', ['title' => site('latest_title'), 'bordered' => true])
            <div class="products-grid">
                @foreach ($latest as $product)
                    @include('partials.product-card')
                @endforeach
            </div>
            <div class="more-wrap"><a href="{{ route('shop') }}" class="btn btn-bordered">Daha Fazla Ürün</a></div>
        </section>
    @endif
</div>

@if ($S::show('info'))
    <section class="info-boxes">
        @foreach (site('info', []) as $box)
            <div class="info-box"><div class="ic">{{ icon($box['icon'] ?? 'check') }}</div><h3>{{ $box['title'] }}</h3><p>{{ $S::text($box['text'] ?? '') }}</p></div>
        @endforeach
    </section>
@endif

<div class="container">
    @if ($S::show('featured') && $featured->isNotEmpty())
        <section class="home-section">
            @include('partials.section-title', ['title' => site('featured_title')])
            <div class="carousel" data-carousel style="--per:6;--per-md:3">
                <div class="carousel-track">
                    @foreach ($featured as $product)
                        @include('partials.product-card')
                    @endforeach
                </div>
                <button class="carousel-nav prev" type="button" data-prev aria-label="Önceki">{{ icon('left') }}</button>
                <button class="carousel-nav next" type="button" data-next aria-label="Sonraki">{{ icon('right') }}</button>
                <div class="carousel-dots" data-dots></div>
            </div>
        </section>
    @endif

    @if ($S::show('promo'))
        @php($promo = site('promo'))
        <section class="promo-row">
            <a href="{{ $promo['link'] ?: route('shop') }}" class="promo-img"><img src="{{ $S::image($promo) }}" alt="{{ $promo['title'] }}" loading="lazy"></a>
            <div class="promo-text">
                @if ($promo['kicker'])<h5>{{ $promo['kicker'] }}</h5>@endif
                @if ($promo['title'])<h1>{{ $promo['title'] }}</h1>@endif
                @if ($promo['text'])<p>{{ $promo['text'] }}</p>@endif
                @if ($promo['button'])<a href="{{ $promo['link'] ?: route('shop') }}" class="link-btn">{{ $promo['button'] }}</a>@endif
            </div>
        </section>
    @endif

    @if ($S::show('strip'))
        <section class="gold-strip">
            @foreach (site('strip', []) as $item)
                <h5>{{ icon($item['icon'] ?? 'check') }} {{ $item['text'] }}</h5>
            @endforeach
        </section>
    @endif

    @if ($S::show('grid') && $tableware && $tablewareProducts->isNotEmpty())
        <section class="home-section" style="margin-bottom:80px">
            @include('partials.section-title', ['title' => $tableware->name, 'bordered' => true])
            <div class="products-grid">
                @foreach ($tablewareProducts as $product)
                    @include('partials.product-card')
                @endforeach
            </div>
            <div class="more-wrap"><a href="{{ $tableware->url() }}" class="btn btn-bordered">Daha Fazla Ürün</a></div>
        </section>
    @endif
</div>
@endsection
