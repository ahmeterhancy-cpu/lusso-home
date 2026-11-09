{{-- Sipariş tablosunun alt satırları (teşekkür, hesabım, takip). $order --}}
<tr><td>Ara toplam</td><td style="text-align:right">{{ money($order->subtotal) }}</td></tr>
@foreach ((array) $order->discount_lines as $d)
    <tr class="discount"><td>{{ $d['label'] }}</td><td style="text-align:right">−{{ money($d['amount']) }}</td></tr>
@endforeach
<tr><td>Kargo</td><td style="text-align:right">{{ (float) $order->shipping > 0 ? money($order->shipping) : 'Ücretsiz' }}</td></tr>
