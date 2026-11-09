<x-filament-panels::page>
    {{ $this->form }}

    @php($r = $this->report())
    <style>
        .rp-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 12px; }
        .rp-card { background: #fff; border: 1px solid #ece7df; border-top: 3px solid #dca344; border-radius: 10px; padding: 14px 16px; }
        .rp-card .k { font-size: 12px; color: #78716c; }
        .rp-card .v { font-size: 22px; font-weight: 700; color: #1c1917; margin-top: 4px; }
        .rp-two { display: grid; grid-template-columns: repeat(auto-fit, minmax(420px, 1fr)); gap: 16px; }
        .rp-table { width: 100%; border-collapse: collapse; font-size: 13px; }
        .rp-table th { text-align: left; color: #78716c; font-weight: 600; padding: 6px 8px; border-bottom: 1px solid #ece7df; }
        .rp-table td { padding: 7px 8px; border-bottom: 1px solid #f3f0eb; }
        .rp-table td.n, .rp-table th.n { text-align: right; white-space: nowrap; }
        .rp-bar { height: 6px; background: #f3e3c4; border-radius: 3px; margin-top: 4px; }
        .rp-bar span { display: block; height: 100%; background: #db8700; border-radius: 3px; }
    </style>

    <p class="text-sm text-gray-500">{{ $r['from'] }} – {{ $r['until'] }} · yalnız ödemesi alınmış siparişler</p>

    <div class="rp-grid">
        <div class="rp-card"><div class="k">Ciro</div><div class="v">{{ money($r['revenue']) }}</div></div>
        <div class="rp-card"><div class="k">Sipariş</div><div class="v">{{ $r['count'] }}</div></div>
        <div class="rp-card"><div class="k">Ortalama sepet</div><div class="v">{{ money($r['avg']) }}</div></div>
        <div class="rp-card"><div class="k">Satılan ürün adedi</div><div class="v">{{ $r['units'] }}</div></div>
        <div class="rp-card"><div class="k">Verilen indirim</div><div class="v">{{ money($r['discount']) }}</div></div>
        <div class="rp-card"><div class="k">Kargo geliri</div><div class="v">{{ money($r['shipping']) }}</div></div>
        <div class="rp-card"><div class="k">Ödeme bekleyen</div><div class="v">{{ $r['unpaid'] }}</div></div>
        <div class="rp-card"><div class="k">İptal / başarısız</div><div class="v">{{ $r['cancelled'] }}</div></div>
    </div>

    @php($maxProd = max(1, collect($r['topProducts'])->max('revenue')))
    <div class="rp-two">
        <x-filament::section heading="En çok satan ürünler">
            <table class="rp-table">
                <tr><th>Ürün</th><th class="n">Adet</th><th class="n">Ciro</th></tr>
                @forelse ($r['topProducts'] as $p)
                    <tr><td>{{ $p['name'] }}<div class="rp-bar"><span style="width:{{ round($p['revenue'] / $maxProd * 100) }}%"></span></div></td><td class="n">{{ (int) $p['qty'] }}</td><td class="n">{{ money($p['revenue']) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-gray-500">Bu dönemde satış yok.</td></tr>
                @endforelse
            </table>
        </x-filament::section>

        <x-filament::section heading="Kategoriler">
            <table class="rp-table">
                <tr><th>Kategori</th><th class="n">Adet</th><th class="n">Ciro</th></tr>
                @forelse ($r['byCategory'] as $c)
                    <tr><td>{{ $c['name'] }}</td><td class="n">{{ (int) $c['qty'] }}</td><td class="n">{{ money($c['revenue']) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-gray-500">—</td></tr>
                @endforelse
            </table>
        </x-filament::section>

        <x-filament::section heading="Ödeme yöntemi ve kaynak">
            <table class="rp-table">
                <tr><th>Yöntem</th><th class="n">Sipariş</th><th class="n">Ciro</th></tr>
                @foreach ($r['byMethod'] as $m)<tr><td>{{ $m['label'] }}</td><td class="n">{{ $m['n'] }}</td><td class="n">{{ money($m['revenue']) }}</td></tr>@endforeach
                @foreach ($r['bySource'] as $m)<tr><td><em>{{ $m['label'] }}</em></td><td class="n">{{ $m['n'] }}</td><td class="n">{{ money($m['revenue']) }}</td></tr>@endforeach
            </table>
        </x-filament::section>

        <x-filament::section heading="Şehirler">
            <table class="rp-table">
                <tr><th>Şehir</th><th class="n">Sipariş</th><th class="n">Ciro</th></tr>
                @forelse ($r['byCity'] as $c)
                    <tr><td>{{ $c['city'] }}</td><td class="n">{{ $c['n'] }}</td><td class="n">{{ money($c['revenue']) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-gray-500">—</td></tr>
                @endforelse
            </table>
        </x-filament::section>

        <x-filament::section heading="Kuponlar">
            <table class="rp-table">
                <tr><th>Kupon</th><th class="n">Kullanım</th><th class="n">İndirim</th><th class="n">Ciro</th></tr>
                @forelse ($r['coupons'] as $c)
                    <tr><td>{{ $c['coupon_code'] }}</td><td class="n">{{ $c['n'] }}</td><td class="n">{{ money($c['discount']) }}</td><td class="n">{{ money($c['revenue']) }}</td></tr>
                @empty
                    <tr><td colspan="4" class="text-gray-500">Bu dönemde kupon kullanılmadı.</td></tr>
                @endforelse
            </table>
        </x-filament::section>

        <x-filament::section heading="Günlük döküm">
            <table class="rp-table">
                <tr><th>Gün</th><th class="n">Sipariş</th><th class="n">Ciro</th></tr>
                @forelse ($r['daily'] as $d)
                    <tr><td>{{ $d['date'] }}</td><td class="n">{{ $d['orders'] }}</td><td class="n">{{ money($d['revenue']) }}</td></tr>
                @empty
                    <tr><td colspan="3" class="text-gray-500">—</td></tr>
                @endforelse
            </table>
        </x-filament::section>
    </div>
</x-filament-panels::page>
