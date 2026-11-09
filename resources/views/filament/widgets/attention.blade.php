<x-filament-widgets::widget>
    <x-filament::section heading="Dikkat gerektirenler" icon="heroicon-o-bell-alert">
        <div style="display:flex;flex-direction:column;gap:2px">
            @foreach ($items as [$label, $count, $url, $icon, $color])
                <a href="{{ $url }}" style="display:flex;align-items:center;gap:12px;padding:9px 6px;border-radius:8px;text-decoration:none" class="hover:bg-gray-50 dark:hover:bg-white/5">
                    <x-filament::icon :icon="$icon" style="width:20px;height:20px;opacity:.7" />
                    <span style="flex:1" class="text-sm text-gray-700 dark:text-gray-200">{{ $label }}</span>
                    <x-filament::badge :color="$count ? $color : 'gray'">{{ number_format($count, 0, ',', '.') }}</x-filament::badge>
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
