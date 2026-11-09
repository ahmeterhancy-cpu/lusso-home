@extends('layouts.app')

@section('title', 'Siparişiniz alındı')
@section('page_title')
    @include('partials.page-title', ['title' => 'Sipariş Alındı'])
@endsection

@section('content')
<div class="container main-page thanks">
    @include('partials.flash')
    <div class="ok">{{ icon('check') }}</div>
    <h2>Teşekkürler, siparişiniz alındı.</h2>
    @if ($order->payment_method === 'tiko' && $order->payment_status !== 'paid')
        <p>Ödemeniz henüz onaylanmadı. <a href="{{ route('tiko.pay', ['order' => $order->number, 't' => $order->track_token]) }}">Ödeme sayfasına dönmek için tıklayın.</a></p>
    @endif

    <ul class="order-overview">
        <li>Sipariş no<strong>{{ $order->number }}</strong></li>
        <li>Tarih<strong>{{ $order->created_at->translatedFormat('d F Y') }}</strong></li>
        <li>E-posta<strong>{{ $order->email }}</strong></li>
        <li>Toplam<strong>{{ money($order->total) }}</strong></li>
        <li>Ödeme<strong>{{ $order->methodLabel() }}</strong></li>
    </ul>

    @if ($order->payment_method === 'havale')
        <div class="bank-box">
            <h4>Banka hesap bilgilerimiz</h4>
            {!! nl2br(e(setting('bank_info', "Banka bilgileri yakında eklenecek.\nLütfen bizimle iletişime geçin: ".setting('support_phone', '+90 539 109 99 00')))) !!}
            <p style="margin:12px 0 0">Açıklama kısmına <strong>{{ $order->number }}</strong> yazmayı unutmayın.</p>
        </div>
    @elseif ($order->payment_method === 'kapida')
        <div class="bank-box"><p style="margin:0">Ödemeyi teslimatta nakit veya kartla yapabilirsiniz. Siparişiniz hazırlandığında sizi arayacağız.</p></div>
    @endif

    <div style="max-width:640px;margin:0 auto;text-align:left">
        <table class="shop-table">
            <thead><tr><th>Ürün</th><th style="text-align:right">Toplam</th></tr></thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr><td>{{ $item->name }} × {{ $item->quantity }}</td><td style="text-align:right">{{ money($item->total) }}</td></tr>
                @endforeach
                <tr><td>Kargo</td><td style="text-align:right">{{ (float) $order->shipping > 0 ? money($order->shipping) : 'Ücretsiz' }}</td></tr>
                <tr><td><strong>Toplam</strong></td><td style="text-align:right"><strong>{{ money($order->total) }}</strong></td></tr>
            </tbody>
        </table>
    </div>
    <a href="{{ route('shop') }}" class="btn btn-primary btn-large">Alışverişe Devam Et</a>
</div>
@endsection
