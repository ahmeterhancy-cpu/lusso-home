@extends('layouts.app')

@section('title', 'Sepetim')
@section('page_title')
    @include('partials.page-title', ['title' => 'Sepetim'])
@endsection

@section('content')
<div class="container main-page">
    @include('partials.flash')

    @if ($lines->isEmpty())
        <div class="cart-empty">
            <div class="big-icon">{{ icon('cart-x') }}</div>
            <h2>Sepetiniz şu anda boş.</h2>
            <p>Sepetiniz Şuanda Boşdur...</p>
            <a href="{{ route('shop') }}" class="btn btn-primary btn-large">Mağazaya Geri Dön</a>
        </div>
    @else
        <div class="cart-layout">
            <form method="post" action="{{ route('cart.update') }}">
                @csrf
                <table class="shop-table responsive">
                    <thead><tr><th></th><th></th><th>Ürün</th><th>Fiyat</th><th>Adet</th><th>Ara Toplam</th></tr></thead>
                    <tbody>
                    @foreach ($lines as $l)
                        <tr>
                            <td><button type="submit" form="rm-{{ $l['product']->id }}" class="remove" aria-label="Kaldır">×</button></td>
                            <td class="p-thumb"><a href="{{ $l['product']->url() }}"><img src="{{ $l['product']->mainImage() }}" alt=""></a></td>
                            <td class="p-name"><a href="{{ $l['product']->url() }}">{{ $l['product']->name }}</a></td>
                            <td>{{ money($l['price']) }}</td>
                            <td>
                                <div class="qty">
                                    <button type="button" data-qty="-1" aria-label="Azalt">−</button>
                                    <input type="number" name="qty[{{ $l['product']->id }}]" value="{{ $l['qty'] }}" min="0" max="99" aria-label="Adet">
                                    <button type="button" data-qty="1" aria-label="Arttır">+</button>
                                </div>
                            </td>
                            <td><strong>{{ money($l['total']) }}</strong></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
                <div class="cart-actions">
                    <a href="{{ route('shop') }}" class="btn">Alışverişe Devam Et</a>
                    <button type="submit" class="btn btn-black">Sepeti Güncelle</button>
                </div>
            </form>
            @foreach ($lines as $l)
                <form id="rm-{{ $l['product']->id }}" method="post" action="{{ route('cart.remove', $l['product']->id) }}">@csrf</form>
            @endforeach

            <div class="cart-totals">
                <h2>Sepet Toplamı</h2>
                <div class="totals-row"><span>Ara Toplam</span><span>{{ money($subtotal) }}</span></div>
                <div class="totals-row"><span>Kargo</span><span>{{ $shipping > 0 ? money($shipping) : 'Ücretsiz' }}</span></div>
                @if ($shipping > 0 && $subtotal < $freeMin)
                    <p class="free-ship-note">Ücretsiz kargo için {{ money($freeMin - $subtotal) }} daha ekleyin.</p>
                @endif
                <div class="totals-row total"><span>Toplam</span><span>{{ money($subtotal + $shipping) }}</span></div>
                <a href="{{ route('checkout') }}" class="btn btn-primary btn-large btn-block" style="margin-top:15px">Ödeme Sayfasına Git</a>
            </div>
        </div>
    @endif
</div>
@endsection
