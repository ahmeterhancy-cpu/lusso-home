@extends('layouts.app')

@section('body_class', 'home')

@section('content')
<div class="container main-page" style="padding-top:0">

    {{-- Slider: yazı yarısı + görsel yarısı, sırayla yer değiştirir --}}
    @if ($slides->isNotEmpty())
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

    <section class="image-trio">
        @foreach ([['img/banner-1.jpg', setting('banner1_link', route('category', 'mackenzie-childs'))], ['img/banner-2.jpg', setting('banner2_link', route('category', 'sofra-grubu'))], ['img/banner-3.jpg', setting('banner3_link', route('category', 'aksesuar'))]] as [$img, $link])
            <a href="{{ $link }}"><img src="{{ asset($img) }}" alt="Lusso Home Concept" width="670" height="670"></a>
        @endforeach
    </section>

    @if ($trend->isNotEmpty())
        <section class="home-section">
            @include('partials.section-title', ['title' => 'Trend Kategoriler'])
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

    <section class="home-section">
        @include('partials.section-title', ['title' => 'Son Eklenen Ürünler', 'bordered' => true])
        <div class="products-grid">
            @foreach ($latest as $product)
                @include('partials.product-card')
            @endforeach
        </div>
        <div class="more-wrap"><a href="{{ route('shop') }}" class="btn btn-bordered">Daha Fazla Ürün</a></div>
    </section>
</div>

<section class="info-boxes">
    <div class="info-box"><div class="ic">{{ icon('truck') }}</div><h3>Ücretsiz Kargo</h3><p>{{ setting('free_shipping_min', 5000) }} TL ve üzeri alışverişlerinizde kargo bedava.</p></div>
    <div class="info-box"><div class="ic">{{ icon('diamond') }}</div><h3>Mükemmel Ürünler</h3><p>#MyLussoLife</p></div>
    <div class="info-box"><div class="ic">{{ icon('magic') }}</div><h3>Evinizin Modası</h3><p>Lusso Home Concept</p></div>
</section>

<div class="container">
    @if ($featured->isNotEmpty())
        <section class="home-section">
            @include('partials.section-title', ['title' => 'Öne Çıkan Ürünler'])
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

    <section class="promo-row">
        <a href="{{ route('shop') }}" class="promo-img"><img src="{{ asset('img/promo.jpg') }}" alt="Evinizin tercihi" loading="lazy"></a>
        <div class="promo-text">
            <h5>EVİNİZİN MODASINI YAKINDAN TAKİP EDİN</h5>
            <h1>EVİNİZİN TERCİHİ</h1>
            <p>Trend modellerimizi görmek için</p>
            <a href="{{ route('shop') }}" class="link-btn">Koleksiyonumuzu İnceleyin.</a>
        </div>
    </section>

    <section class="gold-strip">
        <h5>{{ icon('truck') }} Hızlı teslimat</h5>
        <h5>{{ icon('support') }} Destek Paneli</h5>
        <h5>{{ icon('card') }} Güvenli Ödeme</h5>
    </section>

    @if ($tableware && $tablewareProducts->isNotEmpty())
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
