{{-- Giriş/üye ol/şifre sayfalarının sol görseli: ilk etkin slayt görseli üzerinde marka cümlesi ve faydalar --}}
@php
    $authSlide = \App\Models\Slide::where('is_active', true)->orderBy('sort')->first();
    $authImage = $authSlide ? media_url($authSlide->image) : asset('img/banner-1.jpg');
    $authLines = array_values(array_filter(site('header_lines', []), fn ($l) => $l && ! str_starts_with(trim($l), '#')));
@endphp
<aside class="au-visual" style="background-image:url('{{ $authImage }}')">
    <div class="au-visual-in">
        <p class="eyebrow" lang="en">#MyLussoLife</p>
        <p class="au-quote serif">{{ $authLines ? implode(' ', $authLines) : 'Evinizi küçük dokunuşlarla daha güzel, daha şık ve daha lükse ulaştırın.' }}</p>
        <ul class="au-perks">
            <li>{{ icon('truck') }} Siparişlerinizi adım adım takip edin</li>
            <li>{{ icon('heart') }} Beğendikleriniz her cihazda sizinle</li>
            <li>{{ icon('check') }} Adres bilgileriniz kayıtlı, ödeme daha hızlı</li>
        </ul>
    </div>
</aside>
