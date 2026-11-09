{{-- Panel giriş / şifre sıfırlama ekranının sol marka alanı (yalnız oturum açılmamışken) --}}
@guest
    @php
        $authSlide = \App\Models\Slide::where('is_active', true)->orderBy('sort')->first();
        $authImage = $authSlide ? media_url($authSlide->image) : asset('img/slide-1.jpg');
    @endphp
    <aside class="lusso-auth-side" style="--auth-img:url('{{ $authImage }}')">
        <img src="{{ asset('img/logo.png') }}" alt="Lusso Home Concept" class="lusso-auth-logo">
        <div class="lusso-auth-copy">
            <p class="lusso-auth-eyebrow">Yönetim Paneli</p>
            <h2>Mağazanız tek ekranda.</h2>
            <ul>
                <li>Siparişler, ödemeler ve kargo takibi</li>
                <li>Ürün, fiyat ve stok güncellemeleri</li>
                <li>Kampanya, kupon ve site içeriği</li>
            </ul>
        </div>
        <a href="{{ url('/') }}" class="lusso-auth-back">← Siteye dön</a>
    </aside>
@endguest
