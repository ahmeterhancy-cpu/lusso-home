<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@hasSection('title')@yield('title') – @endif{{ setting('site_name', 'Lusso Home Concept') }}{{ View::hasSection('title') ? '' : ' – Kıbrıs' }}</title>
    <meta name="description" content="@yield('description', setting('meta_description', 'Lusso Home Concept – Kıbrıs\'ta ev dekorasyonu, sofra grubu, tekstil, kitap ve MacKenzie-Childs ürünleri. #MyLussoLife'))">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    @hasSection('og')
        @yield('og')
    @else
        <meta property="og:title" content="{{ setting('site_name', 'Lusso Home Concept') }}">
        <meta property="og:image" content="{{ asset('img/logo.png') }}">
    @endif
    <meta property="og:site_name" content="Lusso Home Concept">
    <meta property="og:locale" content="tr_TR">
    <link rel="icon" href="{{ asset('img/favicon.png') }}">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="stylesheet" href="https://fonts.bunny.net/css?family=lato:300,400,400i,700,700i,900|bodoni-moda:400,500&display=swap">
    <link rel="stylesheet" href="{{ asset('css/lusso.css') }}?v={{ filemtime(public_path('css/lusso.css')) }}">
    @stack('head')
    {!! setting('head_code') !!}
</head>
<body class="@yield('body_class')">
<div class="website-wrapper">

    @php
        $phone = setting('support_phone', '+90 539 109 99 00');
        $headerLines = array_values(array_filter(site('header_lines', [])));
        $slogan = array_values(array_filter($headerLines, fn ($l) => ! str_starts_with(trim($l), '#')));
        $hashtag = collect($headerLines)->first(fn ($l) => str_starts_with(trim($l), '#'));
        $banner = collect(\App\Models\Campaign::active())->pluck('banner')->filter()->first();
    @endphp
    <div class="topbar">
        <div class="tb-in">
            <a class="tb-phone" href="tel:{{ preg_replace('/[^0-9+]/', '', $phone) }}">{{ icon('phone') }} <span class="tb-label">Destek hattı</span> {{ $phone }}</a>
            @if ($banner)<p class="campaign-banner">{{ $banner }}</p>@endif
            <ul class="topbar-menu">
                @foreach (site('topbar_links', []) as $l)
                    <li><a href="{{ url($l['url'] ?? '/') }}">{{ $l['label'] }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>

    {{-- Başlık: logo koyu zeminli (#37383c) olduğu için başlık da aynı koyu tonda --}}
    <header class="main-header">
        <div class="header-row">
            <div class="header-left">
                <button class="mobile-toggle" type="button" data-open-menu aria-label="Menü">{{ icon('menu') }}</button>
                @if ($slogan || $hashtag)
                    <div class="header-text">
                        @if ($slogan)<p class="hd-slogan">{{ implode(' ', $slogan) }}</p>@endif
                        @if ($hashtag)<p class="hd-tag" lang="en">{{ $hashtag }}</p>@endif
                    </div>
                @endif
            </div>
            <div class="site-logo">
                <a href="{{ route('home') }}"><img src="{{ asset('img/logo.png') }}" alt="Lusso Home Concept" width="175" height="69"></a>
            </div>
            <div class="header-right">
                <a href="{{ route('account') }}" class="hd-account">
                    <span class="hd-ic">{{ icon('user') }}</span>
                    @auth
                        <span class="hd-account-text"><small>Merhaba</small>Hesabım</span>
                    @else
                        <span class="hd-account-text"><small>Giriş yap</small>Üye ol</span>
                    @endauth
                </a>
                <button type="button" class="hd-ic" data-open-search aria-label="Ara">{{ icon('search') }}</button>
                <a href="{{ route('wishlist') }}" class="hd-ic" aria-label="Beğendiklerim">{{ icon('heart') }}<span class="badge" data-wishlist-count>{{ count($wishlistIds) }}</span></a>
                <a href="{{ route('cart') }}" class="cart-link" data-open-cart aria-label="Sepet">{{ icon('cart') }}<span class="cart-count" data-cart-count>{{ $cartCount }}</span><span class="totals" data-cart-total>{{ money($cartTotal) }}</span></a>
            </div>
        </div>
        <div class="navigation-wrap main-nav-wrap">
            @include('partials.nav')
        </div>
    </header>

    <div class="sticky-header" data-sticky>
        <div class="inner">
            <div class="site-logo"><a href="{{ route('home') }}"><img src="{{ asset('img/logo.png') }}" alt="Lusso Home Concept"></a></div>
            @include('partials.nav')
            <div class="header-right">
                <button type="button" class="hd-ic" data-open-search aria-label="Ara">{{ icon('search') }}</button>
                <a href="{{ route('cart') }}" class="cart-link" data-open-cart aria-label="Sepet">{{ icon('cart') }}<span class="cart-count" data-cart-count>{{ $cartCount }}</span><span class="totals" data-cart-total>{{ money($cartTotal) }}</span></a>
            </div>
        </div>
    </div>

    @yield('page_title')

    <main>
        @yield('content')
    </main>

    <footer class="footer">
        <div class="footer-logo"><a href="{{ route('home') }}"><img src="{{ asset('img/logo.png') }}" alt="Lusso Home Concept" loading="lazy"></a></div>
        <div class="footer-cols">
            @foreach (site('footer_columns', []) as $col)
                <div>
                    <h5 class="widget-title">{{ $col['title'] }}</h5>
                    <ul>
                        @foreach ($col['links'] ?? [] as $l)
                            <li><a href="{{ url($l['url'] ?? '/') }}">{{ $l['label'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
            <div class="contact">
                <h5 class="widget-title">{{ site('contact_title') }}</h5>
                {!! setting('footer_contact', \App\Support\SiteContent::FOOTER_CONTACT) !!}
            </div>
        </div>
        <div class="copyrights">{{ \App\Support\SiteContent::text(site('copyright')) }}
            @if (site('credit'))
                @if (site('credit_url'))<a href="{{ site('credit_url') }}" target="_blank" rel="noopener">{{ site('credit') }}</a>@else{{ site('credit') }}@endif
            @endif
        </div>
    </footer>
</div>

<div class="side-social">
    @if ($fb = setting('facebook', 'https://www.facebook.com/lussohomeconcept'))<a href="{{ $fb }}" class="fb" target="_blank" rel="noopener" aria-label="Facebook">{{ icon('facebook') }}</a>@endif
    @if ($ig = setting('instagram', 'https://www.instagram.com/lussohomeconcept'))<a href="{{ $ig }}" class="ig" target="_blank" rel="noopener" aria-label="Instagram">{{ icon('instagram') }}</a>@endif
</div>
<button class="scroll-top" type="button" data-scroll-top aria-label="Yukarı">{{ icon('up') }}</button>

<div class="search-overlay" data-search>
    <button class="close" type="button" data-close-search aria-label="Kapat">{{ icon('close') }}</button>
    <form action="{{ route('search') }}" method="get">
        <input type="search" name="s" placeholder="Ürün ara…" autocomplete="off" aria-label="Ürün ara">
        <p class="hint">Aramak için yazın ve Enter'a basın</p>
    </form>
</div>

<div class="drawer-bg" data-close-drawer></div>
<aside class="drawer" data-drawer aria-label="Sepet">
    <div class="drawer-head"><h3>Sepet</h3><button type="button" data-close-drawer>Kapat {{ icon('close') }}</button></div>
    <div class="drawer-body" data-drawer-body></div>
    <div class="drawer-foot" data-drawer-foot>
        <div class="sub"><span>Ara Toplam:</span><span data-drawer-total></span></div>
        <a href="{{ route('cart') }}" class="btn">Sepeti Görüntüle</a>
        <a href="{{ route('checkout') }}" class="btn btn-primary">Ödeme</a>
    </div>
</aside>

<nav class="mobile-nav" aria-label="Mobil menü">
    <form action="{{ route('search') }}" method="get"><input type="search" name="s" placeholder="Ürün ara…" aria-label="Ürün ara"></form>
    <ul>
        <li><a href="{{ route('home') }}">Ana Sayfa</a></li>
        @foreach ($menu as $item)
            <li>
                @if ($item['children'])
                    <details><summary>{{ $item['name'] }} {{ icon('down') }}</summary>
                        <ul>
                            <li><a href="{{ $item['url'] }}">Tümü</a></li>
                            @foreach ($item['children'] as $ch)<li><a href="{{ $ch['url'] }}">{{ $ch['name'] }}</a></li>@endforeach
                        </ul>
                    </details>
                @else
                    <a href="{{ $item['url'] }}">{{ $item['name'] }}</a>
                @endif
            </li>
        @endforeach
        <li><a href="{{ route('wishlist') }}">Beğendiklerim</a></li>
        <li><a href="{{ route('account') }}">{{ auth()->check() ? 'Hesabım' : 'Giriş / Üye Ol' }}</a></li>
        <li><a href="{{ route('contact') }}">İletişim</a></li>
    </ul>
</nav>

<div class="cookie-bar" data-cookie hidden>
    <span>Sitemizde deneyiminizi iyileştirmek için çerezler kullanılır. Siteyi kullanarak <a href="{{ route('page', 'gizlilik-sozlesmesi') }}" style="color:#fff;text-decoration:underline">çerez kullanımını</a> kabul etmiş olursunuz.</span>
    <button type="button" data-cookie-ok>Tamam</button>
</div>
<div class="toast" data-toast role="status" aria-live="polite"></div>

<script>
    window.LUSSO = {
        cartAdd: @json(route('cart.add')),
        cartRemove: @json(url('/cart/remove')),
        cartSummary: @json(route('cart.summary')),
        wishlistToggle: @json(route('wishlist.toggle')),
    };
</script>
<script src="{{ asset('js/lusso.js') }}?v={{ filemtime(public_path('js/lusso.js')) }}" defer></script>
@stack('scripts')
</body>
</html>
