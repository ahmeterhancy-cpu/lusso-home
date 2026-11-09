<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <title>{{ $order->number }} — Sipariş fişi</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; color: #222; margin: 30px; font-size: 13px; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #37383c; padding-bottom: 15px; margin-bottom: 20px; }
        .head img { width: 150px; background: #37383c; padding: 8px 12px; }
        h1 { font-size: 20px; margin: 0 0 4px; }
        .cols { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 25px; }
        h3 { font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #888; margin: 0 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; border-bottom: 1px solid #222; padding: 6px 4px; font-size: 12px; }
        td { border-bottom: 1px solid #ddd; padding: 8px 4px; }
        .r { text-align: right; }
        .tot td { border: 0; padding: 4px; }
        .tot .big td { font-size: 16px; font-weight: bold; border-top: 2px solid #222; padding-top: 8px; }
        .note { margin-top: 20px; padding: 10px; background: #f5f5f5; }
        .foot { margin-top: 40px; font-size: 11px; color: #888; text-align: center; }
        @media print { .noprint { display: none; } body { margin: 10mm; } }
    </style>
</head>
<body>
<p class="noprint"><button onclick="window.print()">Yazdır</button></p>
<div class="head">
    <div>
        <h1>Sipariş {{ $order->number }}</h1>
        <div>{{ $order->created_at->format('d.m.Y H:i') }} · {{ $order->methodLabel() }} · {{ $order->payment_status === 'paid' ? 'Ödendi' : 'Ödeme bekleniyor' }}</div>
    </div>
    <img src="{{ asset('img/logo.png') }}" alt="Lusso Home Concept">
</div>
<div class="cols">
    <div>
        <h3>Teslimat</h3>
        <strong>{{ $order->first_name }} {{ $order->last_name }}</strong><br>
        {{ $order->address }}<br>{{ $order->city }}<br>
        {{ $order->phone }}<br>{{ $order->email }}
    </div>
    <div>
        <h3>Satıcı</h3>
        <strong>Lusso Home Concept</strong><br>
        {!! nl2br(e(strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n"], (string) setting('contact_text', 'Neopark no 6, şht Orhan Durusoy sokak, Girne'))))) !!}
    </div>
</div>
<table>
    <thead><tr><th>Ürün</th><th>Stok kodu</th><th class="r">Fiyat</th><th class="r">Adet</th><th class="r">Toplam</th></tr></thead>
    <tbody>
    @foreach ($order->items as $item)
        <tr><td>{{ $item->name }}</td><td>{{ $item->sku }}</td><td class="r">{{ money($item->price) }}</td><td class="r">{{ $item->quantity }}</td><td class="r">{{ money($item->total) }}</td></tr>
    @endforeach
    </tbody>
</table>
<table class="tot" style="width:280px;margin-left:auto;margin-top:10px">
    <tr><td>Ara toplam</td><td class="r">{{ money($order->subtotal) }}</td></tr>
    @foreach ((array) $order->discount_lines as $d)
        <tr><td>{{ $d['label'] }}</td><td class="r">−{{ money($d['amount']) }}</td></tr>
    @endforeach
    <tr><td>Kargo</td><td class="r">{{ (float) $order->shipping > 0 ? money($order->shipping) : 'Ücretsiz' }}</td></tr>
    <tr class="big"><td>Toplam</td><td class="r">{{ money($order->total) }}</td></tr>
</table>
@if ($order->note)<div class="note"><strong>Müşteri notu:</strong> {{ $order->note }}</div>@endif
<div class="foot">Lusso Home Concept · {{ setting('support_phone', '+90 539 109 99 00') }} · lussohomeconcept.com</div>
<script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
</body>
</html>
