<x-filament-widgets::widget>
    <x-filament::section heading="Çok satanlar (30 gün)" icon="heroicon-o-trophy">
        @if ($rows->isEmpty())
            <p class="text-sm text-gray-500">Son 30 günde ödenmiş sipariş yok.</p>
        @else
            <div style="display:flex;flex-direction:column;gap:2px">
                @foreach ($rows as $i => $row)
                    <a href="{{ $editUrl($row->product_id) ?? '#' }}" style="display:flex;align-items:center;gap:10px;padding:8px 6px;border-radius:8px;text-decoration:none" class="hover:bg-gray-50 dark:hover:bg-white/5">
                        <span class="text-xs text-gray-400" style="width:16px">{{ $i + 1 }}</span>
                        <span class="text-sm text-gray-700 dark:text-gray-200" style="flex:1">{{ \Illuminate\Support\Str::limit($row->name, 40) }}</span>
                        <span class="text-xs text-gray-500">{{ (int) $row->qty }} adet</span>
                        <span class="text-sm font-semibold" style="min-width:80px;text-align:right">{{ money($row->revenue) }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
