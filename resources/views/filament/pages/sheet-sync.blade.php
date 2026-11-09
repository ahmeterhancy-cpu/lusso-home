<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
        <div class="mt-6"><x-filament::button type="submit">Kaydet</x-filament::button></div>
    </form>

    @php($last = $this->lastResult())
    <x-filament::section heading="Son güncelleme" icon="heroicon-o-clock">
        @if ($last)
            <p style="margin-bottom:8px">
                <x-filament::badge :color="$last['ok'] ? 'success' : 'danger'">{{ $last['ok'] ? 'Başarılı' : 'Hata' }}</x-filament::badge>
                <span class="text-sm" style="margin-left:8px">{{ $last['at'] }} — {{ $last['summary'] }}</span>
            </p>
            @if (! empty($last['notFound']))
                <details style="margin-top:8px"><summary class="text-sm" style="cursor:pointer">Sitede bulunamayan stok kodları ({{ count($last['notFound']) }})</summary>
                    <ul class="text-sm" style="margin:6px 0 0 18px;list-style:disc">@foreach ($last['notFound'] as $n)<li>{{ $n }}</li>@endforeach</ul>
                </details>
            @endif
            @if (! empty($last['errors']))
                <details style="margin-top:8px" open><summary class="text-sm" style="cursor:pointer">Uyarılar</summary>
                    <ul class="text-sm" style="margin:6px 0 0 18px;list-style:disc">@foreach ($last['errors'] as $e)<li>{{ $e }}</li>@endforeach</ul>
                </details>
            @endif
        @else
            <p class="text-sm text-gray-500">Henüz çalıştırılmadı.</p>
        @endif
    </x-filament::section>

    <x-filament::section heading="Dosya nasıl olmalı?" icon="heroicon-o-information-circle" collapsible>
        <div class="text-sm" style="line-height:1.7">
            <p>İlk satır sütun başlıkları; her satır bir ürün. Ürünler <strong>Stok Kodu</strong> ile eşleşir (sitedeki stok kodlarını "Ürünler → İçe / dışa aktar → CSV indir" ile görebilirsiniz).</p>
            <table style="margin:10px 0;border-collapse:collapse" class="text-sm">
                <tr>@foreach (['Stok Kodu', 'Fiyat', 'İndirimli Fiyat', 'Stok'] as $h)<th style="border:1px solid #ddd;padding:4px 10px;background:#faf8f5">{{ $h }}</th>@endforeach</tr>
                <tr><td style="border:1px solid #ddd;padding:4px 10px">114-1015-1</td><td style="border:1px solid #ddd;padding:4px 10px">1900</td><td style="border:1px solid #ddd;padding:4px 10px"></td><td style="border:1px solid #ddd;padding:4px 10px">5</td></tr>
                <tr><td style="border:1px solid #ddd;padding:4px 10px">30118-006</td><td style="border:1px solid #ddd;padding:4px 10px">4500</td><td style="border:1px solid #ddd;padding:4px 10px">3990</td><td style="border:1px solid #ddd;padding:4px 10px">0</td></tr>
            </table>
            <ul style="margin-left:18px;list-style:disc">
                <li>Yalnız dosyada olan sütunlar güncellenir; örneğin sadece "Stok Kodu" ve "Stok" varsa fiyatlara dokunulmaz.</li>
                <li>Kabul edilen başlıklar: <em>Stok Kodu / SKU / Barkod</em>, <em>Fiyat / Satış Fiyatı</em>, <em>İndirimli Fiyat</em>, <em>Stok / Adet / Miktar</em>, <em>Stokta</em>, <em>Yayında</em> (Evet/Hayır).</li>
                <li>Stok 0 olan ürün "stokta yok" olur ve sepete eklenemez; adet girilince tekrar satışa açılır.</li>
                <li>Dosyada olmayan ürünlere dokunulmaz; yeni ürün oluşturulmaz, ürün silinmez.</li>
                <li>Google E-Tablo da olur, Drive'a yüklenmiş .xlsx / .csv dosyası da. Birden çok sayfa varsa ilk sayfa okunur.</li>
                <li>Bağlantıyı bilen herkes dosyayı görebilir; dosyada fiyat ve stok dışında hassas bilgi tutmayın.</li>
            </ul>
        </div>
    </x-filament::section>
</x-filament-panels::page>
