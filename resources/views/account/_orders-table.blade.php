<table class="shop-table">
    <thead><tr><th>Sipariş</th><th>Tarih</th><th>Durum</th><th>Toplam</th><th></th></tr></thead>
    <tbody>
    @foreach ($orders as $o)
        <tr>
            <td><a href="{{ route('account.order', $o->number) }}">{{ $o->number }}</a></td>
            <td>{{ $o->created_at->format('d.m.Y') }}</td>
            <td><span class="status-pill">{{ $o->statusLabel() }}</span></td>
            <td>{{ money($o->total) }} <small style="color:#999">({{ $o->items()->sum('quantity') }} ürün)</small></td>
            <td><a href="{{ route('account.order', $o->number) }}" class="btn" style="padding:6px 12px;font-size:12px">Görüntüle</a></td>
        </tr>
    @endforeach
    </tbody>
</table>
