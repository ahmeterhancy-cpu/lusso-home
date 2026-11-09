@extends('layouts.app')

@section('title', 'İletişim')

@section('content')
@php
    $cStores = \App\Support\SiteContent::stores()->map(function ($s) {
        $digits = preg_replace('/\D/', '', $s['phones'][0] ?? '');
        // cep numarası (05…) ise WhatsApp bağlantısı da verilir
        $s['wa'] = str_starts_with($digits, '05') ? '90'.substr($digits, 1) : null;
        $s['key'] = $s['label'].' – '.$s['place'];

        return $s;
    });
    $cEmail = $cStores->pluck('emails')->flatten()->first() ?: setting('email');
    $cWa = preg_replace('/\D/', '', setting('whatsapp', '905391099900'));
    $cPhone = setting('support_phone', '+90 539 109 99 00');
    // Haritadaki pimler: konumu girilmiş şubeler (i = kart sırası)
    $cPins = $cStores->values()
        ->map(fn ($s, $i) => ['i' => $i, 'label' => $s['city'] ?: $s['label'], 'sub' => $s['label'], 'lat' => $s['lat'], 'lng' => $s['lng']])
        ->filter(fn ($p) => $p['lat'] !== null)->values();
@endphp

<section class="wrap ct-head">
    <div>
        <p class="eyebrow">İletişim</p>
        <h1 class="serif">Size en yakın mağazamıza gelin ya da bize yazın</h1>
        <p class="ct-lead">{{ $cStores->count() }} mağazamızda ürünleri yakından görebilir, sipariş ve ürünlerle ilgili her sorunuz için bize dilediğiniz kanaldan ulaşabilirsiniz.</p>
    </div>
    <div class="ct-quick">
        @if ($cWa)<a href="https://wa.me/{{ $cWa }}" target="_blank" rel="noopener" class="ct-q wa"><span>{{ icon('whatsapp') }}</span><b>WhatsApp</b><small>Hızlı yanıt</small></a>@endif
        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $cPhone) }}" class="ct-q"><span>{{ icon('phone') }}</span><b>Destek hattı</b><small>{{ $cPhone }}</small></a>
        @if ($cEmail)<a href="mailto:{{ $cEmail }}" class="ct-q"><span>{{ icon('paper-plane') }}</span><b>E-posta</b><small>{{ $cEmail }}</small></a>@endif
    </div>
</section>

{{-- Şubeler: hepsi tek haritada; karta ya da pime tıklayınca o şube öne çıkar --}}
<section class="wrap ct-stores-wrap" data-ct>
    <div class="ct-stores">
        @foreach ($cStores->values() as $i => $s)
            <article class="ct-store" data-ct-store="{{ $i }}">
                <button type="button" class="ct-store-pick" data-ct-pick aria-pressed="false">
                    <span class="ct-store-label">{{ icon('pin') }} {{ $s['label'] }}@if ($s['city']) · {{ $s['city'] }}@endif</span>
                    <span class="ct-store-place serif">{{ $s['place'] }}</span>
                    @if ($s['note'])<span class="ct-store-note">{{ $s['note'] }}</span>@endif
                    @foreach ($s['lines'] as $line)<span class="ct-store-note">{{ $line }}</span>@endforeach
                    <span class="ct-store-show">Haritada göster</span>
                </button>
                <div class="ct-store-acts">
                    @foreach ($s['phones'] as $p)
                        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $p) }}" class="ct-btn dark">{{ icon('phone') }} {{ $p }}</a>
                    @endforeach
                    @if ($s['wa'])<a href="https://wa.me/{{ $s['wa'] }}" target="_blank" rel="noopener" class="ct-btn" aria-label="{{ $s['label'] }} WhatsApp">{{ icon('whatsapp') }}</a>@endif
                    <a href="{{ $s['map'] }}" target="_blank" rel="noopener" class="ct-btn">Yol tarifi {{ icon('arrow-right') }}</a>
                </div>
            </article>
        @endforeach
    </div>
    <div class="ct-map">
        <div class="ct-mapbox" role="region" aria-label="Mağazalarımızın haritası" data-ct-map data-pins="{{ json_encode($cPins, JSON_UNESCAPED_UNICODE) }}"></div>
        <p class="ct-map-hint">Bir mağazaya dokunun</p>
        <p class="ct-map-fail" hidden>Harita yüklenemedi. Mağaza kartlarındaki <b>Yol tarifi</b> bağlantısını kullanabilirsiniz.</p>
    </div>
</section>

<section class="wrap ct-form-wrap">
    <div class="ct-form-intro">
        <p class="eyebrow">Bize yazın</p>
        <h2 class="serif">Sorunuz mu var?</h2>
        <p>Ürün, stok, sipariş ya da mağaza ziyaretiyle ilgili mesajınızı bırakın; ilgili şubemiz en kısa sürede size dönsün.</p>
        <div class="ct-social">
            @if ($fb = setting('facebook', 'https://www.facebook.com/lussohomeconcept'))<a href="{{ $fb }}" target="_blank" rel="noopener" aria-label="Facebook">{{ icon('facebook') }}</a>@endif
            @if ($ig = setting('instagram', 'https://www.instagram.com/lussohomeconcept'))<a href="{{ $ig }}" target="_blank" rel="noopener" aria-label="Instagram">{{ icon('instagram') }}</a>@endif
        </div>
    </div>
    <div class="ct-form">
        @include('partials.flash')
        <form method="post" action="{{ route('contact.send') }}" class="au-form">
            @csrf
            <div style="position:absolute;left:-9999px" aria-hidden="true"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>
            <div class="au-grid">
                <div class="au-field"><label for="name">Ad soyad</label><input id="name" type="text" name="name" value="{{ old('name') }}" required autocomplete="name"></div>
                <div class="au-field"><label for="phone">Telefon <small>(isteğe bağlı)</small></label><input id="phone" type="tel" name="phone" value="{{ old('phone') }}" autocomplete="tel"></div>
            </div>
            <div class="au-field"><label for="email">E-posta</label><input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" placeholder="ornek@eposta.com"></div>
            @if ($cStores->count() > 1)
                <div class="au-field">
                    <label for="store">İlgili şube <small>(isteğe bağlı)</small></label>
                    <select id="store" name="store" class="ct-select">
                        <option value="">Fark etmez</option>
                        @foreach ($cStores as $s)
                            <option value="{{ $s['key'] }}" @selected(old('store') === $s['key'])>{{ $s['key'] }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            <div class="au-field"><label for="message">Mesajınız</label><textarea id="message" name="message" rows="5" required class="ct-textarea">{{ old('message') }}</textarea></div>
            <button type="submit" class="mbtn mbtn-dark au-submit">Gönder {{ icon('paper-plane') }}</button>
        </form>
    </div>
</section>
@endsection
