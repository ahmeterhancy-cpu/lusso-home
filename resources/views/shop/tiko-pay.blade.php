@extends('layouts.app')

@section('title', 'Kartla Ödeme')
@section('page_title')
    @include('partials.page-title', ['title' => 'Kartla Ödeme', 'crumbs' => [['Sepetim', route('cart')], ['Ödeme', null]]])
@endsection

@section('content')
<div class="container main-page">
    @include('partials.flash')
    <div class="card-form">
        <h3 style="text-transform:uppercase;margin-bottom:5px">Sipariş {{ $order->number }}</h3>
        <p style="color:#777;margin-bottom:25px">Ödenecek tutar: <strong style="color:var(--primary);font-size:20px">{{ money($order->total) }}</strong></p>

        @if ($simulation)
            <div class="alert alert-info">Geliştirme modu: Tiko bilgileri girilmediği için ödeme <strong>benzetimi</strong> yapılıyor. Gerçek kart çekimi olmaz.</div>
        @endif

        {{-- Bu form doğrudan Tiko'ya gider; kart bilgileri bizim sunucumuza uğramaz. --}}
        <form method="post" action="{{ $action }}" autocomplete="on">
            @if ($simulation) @csrf @endif
            @foreach ($fields as $name => $value)
                <input type="hidden" name="{{ $name }}" value="{{ $value }}">
            @endforeach
            <div class="form-row"><label for="CardName">Kart üzerindeki isim</label><input id="CardName" type="text" name="CardName" required autocomplete="cc-name" value="{{ $order->first_name }} {{ $order->last_name }}"></div>
            <div class="form-row"><label for="CardNo">Kart numarası</label><input id="CardNo" type="text" name="CardNo" required inputmode="numeric" autocomplete="cc-number" maxlength="19" placeholder="0000 0000 0000 0000" data-card-number></div>
            <div class="form-grid">
                <div class="form-row"><label for="CardExpireMonth">Ay</label>
                    <select id="CardExpireMonth" name="CardExpireMonth" required autocomplete="cc-exp-month">@for ($m = 1; $m <= 12; $m++)<option value="{{ sprintf('%02d', $m) }}">{{ sprintf('%02d', $m) }}</option>@endfor</select>
                </div>
                <div class="form-row"><label for="CardExpireYear">Yıl</label>
                    <select id="CardExpireYear" name="CardExpireYear" required autocomplete="cc-exp-year">@for ($y = (int) date('Y'); $y <= (int) date('Y') + 12; $y++)<option value="{{ $y }}">{{ $y }}</option>@endfor</select>
                </div>
                <div class="form-row full-sm"><label for="CardCvv">CVV</label><input id="CardCvv" type="text" name="CardCvv" required inputmode="numeric" autocomplete="cc-csc" maxlength="4" placeholder="000"></div>
            </div>
            @if ($simulation)
                <div style="display:flex;gap:10px">
                    <button type="submit" name="result" value="ok" class="btn btn-primary btn-large" style="flex:1">Ödemeyi Onayla (benzetim)</button>
                    <button type="submit" name="result" value="fail" class="btn btn-large">Reddet</button>
                </div>
            @else
                <button type="submit" class="btn btn-primary btn-large btn-block">{{ money($order->total) }} Öde</button>
            @endif
        </form>
        <p class="secure-note">{{ icon('lock') }} 256-bit SSL ve 3D Secure ile güvenli ödeme · Tiko</p>
    </div>
    <p style="text-align:center;margin-top:20px;font-size:13px;color:#777">Kartla ödemek istemiyor musunuz? Bize <a href="{{ route('contact') }}">ulaşın</a>, siparişinizi havale veya kapıda ödemeye çevirelim.</p>
</div>
@endsection
