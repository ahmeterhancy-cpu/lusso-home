<p><strong>{{ $order->number }}</strong> numaralı sipariş {{ $order->created_at->format('d.m.Y H:i') }} tarihinde verildi ve şu anda <span class="status-pill">{{ $order->statusLabel() }}</span> durumunda.</p>
<table class="shop-table">
    <thead><tr><th>Ürün</th><th style="text-align:right">Toplam</th></tr></thead>
    <tbody>
    @foreach ($order->items as $item)
        <tr><td>{{ $item->name }} × {{ $item->quantity }}</td><td style="text-align:right">{{ money($item->total) }}</td></tr>
    @endforeach
    <tr><td>Kargo</td><td style="text-align:right">{{ (float) $order->shipping > 0 ? money($order->shipping) : 'Ücretsiz' }}</td></tr>
    <tr><td>Ödeme yöntemi</td><td style="text-align:right">{{ $order->methodLabel() }} ({{ $order->payment_status === 'paid' ? 'ödendi' : 'ödeme bekleniyor' }})</td></tr>
    <tr><td><strong>Toplam</strong></td><td style="text-align:right"><strong>{{ money($order->total) }}</strong></td></tr>
    </tbody>
</table>
<h4>Teslimat adresi</h4>
<p>{{ $order->first_name }} {{ $order->last_name }}<br>{{ $order->address }}<br>{{ $order->city }}<br>{{ $order->phone }}<br>{{ $order->email }}</p>
