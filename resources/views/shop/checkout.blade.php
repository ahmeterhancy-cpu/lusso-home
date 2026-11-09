@extends('layouts.app')

@section('title', 'Ödeme')
@section('page_title')
    @include('partials.page-title', ['title' => 'Ödeme', 'crumbs' => [['Sepetim', route('cart')], ['Ödeme', null]]])
@endsection

@section('content')
<div class="container main-page">
    @include('partials.flash')
    @guest
        <div class="alert alert-info">Üye misiniz? <a href="{{ route('account') }}"><strong>Giriş yapmak için tıklayın</strong></a> — üye olmadan da sipariş verebilirsiniz.</div>
    @endguest

    <form method="post" action="{{ route('checkout.store') }}" class="checkout-layout" novalidate>
        @csrf
        <div>
            <h3>Fatura ve Teslimat Bilgileri</h3>
            <div class="form-grid">
                <div class="form-row"><label for="first_name">Ad <span class="req">*</span></label><input id="first_name" type="text" name="first_name" value="{{ old('first_name', $user ? explode(' ', $user->name)[0] : '') }}" required autocomplete="given-name"></div>
                <div class="form-row"><label for="last_name">Soyad <span class="req">*</span></label><input id="last_name" type="text" name="last_name" value="{{ old('last_name', $user ? \Illuminate\Support\Str::after($user->name, ' ') : '') }}" required autocomplete="family-name"></div>
                <div class="form-row"><label for="phone">Telefon <span class="req">*</span></label><input id="phone" type="tel" name="phone" value="{{ old('phone', $user?->phone) }}" required autocomplete="tel"></div>
                <div class="form-row"><label for="email">E-posta adresi <span class="req">*</span></label><input id="email" type="email" name="email" value="{{ old('email', $user?->email) }}" required autocomplete="email"></div>
                <div class="form-row full"><label for="address">Adres <span class="req">*</span></label><input id="address" type="text" name="address" value="{{ old('address', $user?->address) }}" required autocomplete="street-address" placeholder="Mahalle, sokak, bina ve daire no"></div>
                <div class="form-row full"><label for="city">Şehir / Bölge <span class="req">*</span></label>
                    <select id="city" name="city" required>
                        <option value="">Seçin…</option>
                        @foreach (['Lefkoşa', 'Girne', 'Gazimağusa', 'Güzelyurt', 'İskele', 'Lefke'] as $c)
                            <option value="{{ $c }}" @selected(old('city', $user?->city) === $c)>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-row full"><label for="note">Sipariş notu (isteğe bağlı)</label><textarea id="note" name="note" rows="4" placeholder="Teslimatla ilgili özel notlar…">{{ old('note') }}</textarea></div>
            </div>
        </div>

        <div class="order-review">
            <h3>Siparişiniz</h3>
            <table class="shop-table">
                <thead><tr><th>Ürün</th><th style="text-align:right">Ara Toplam</th></tr></thead>
                <tbody>
                @foreach ($lines as $l)
                    <tr><td>{{ $l['product']->name }} <strong>× {{ $l['qty'] }}</strong></td><td style="text-align:right">{{ money($l['total']) }}</td></tr>
                @endforeach
                </tbody>
            </table>
            <div class="totals-row"><span>Ara Toplam</span><span>{{ money($subtotal) }}</span></div>
            <div class="totals-row"><span>Kargo</span><span>{{ $shipping > 0 ? money($shipping) : 'Ücretsiz' }}</span></div>
            <div class="totals-row total"><span>Toplam</span><span>{{ money($subtotal + $shipping) }}</span></div>

            <ul class="pay-methods">
                @foreach ($methods as $key => $label)
                    <li>
                        <label><input type="radio" name="payment_method" value="{{ $key }}" @checked(old('payment_method', array_key_first($methods)) === $key)> {{ $label }}</label>
                        <div class="desc">
                            @switch($key)
                                @case('tiko') Siparişi onayladıktan sonra güvenli ödeme sayfasında kartınızla (3D Secure) ödeme yaparsınız. Kart bilgileriniz bizim sunucularımızda saklanmaz. @break
                                @case('havale') Sipariş numaranızı açıklamaya yazarak toplam tutarı banka hesabımıza gönderin. Ödemeniz onaylanınca siparişiniz hazırlanır. @break
                                @case('kapida') Ödemeyi teslimat sırasında nakit veya kartla yaparsınız. @break
                            @endswitch
                        </div>
                    </li>
                @endforeach
            </ul>
            <label class="check" style="margin-bottom:20px"><input type="checkbox" name="terms" value="1" @checked(old('terms'))> <span><a href="{{ route('page', 'alisveris') }}" target="_blank">Mesafeli satış sözleşmesini</a> ve <a href="{{ route('page', 'gizlilik-sozlesmesi') }}" target="_blank">gizlilik sözleşmesini</a> okudum, kabul ediyorum.</span></label>
            <button type="submit" class="btn btn-primary btn-large btn-block">Siparişi Onayla</button>
        </div>
    </form>
</div>
@endsection
