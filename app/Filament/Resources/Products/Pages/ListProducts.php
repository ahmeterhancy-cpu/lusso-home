<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Yeni ürün')];
    }

    /** Filament closure parametrelerini ADA göre verir: $query şart. */
    private function scopes(): array
    {
        return [
            'tumu' => ['Tümü', fn (Builder $query) => $query],
            'yayinda' => ['Yayında', fn (Builder $query) => $query->where('is_active', true)],
            'fiyatsiz' => ['Fiyatsız', fn (Builder $query) => $query->where('price', 0)],
            'stokta-yok' => ['Stokta yok', fn (Builder $query) => $query->where('in_stock', false)],
            'gorselsiz' => ['Görselsiz', fn (Builder $query) => $query->doesntHave('images')],
            'indirimli' => ['İndirimde', fn (Builder $query) => $query->whereNotNull('sale_price')->where('sale_price', '>', 0)],
            'one-cikan' => ['Öne çıkan', fn (Builder $query) => $query->where('is_featured', true)],
            'taslak' => ['Yayında değil', fn (Builder $query) => $query->where('is_active', false)],
        ];
    }

    public function getTabs(): array
    {
        return collect($this->scopes())->map(function ($t, $key) {
            $n = $t[1](Product::query())->count();

            return Tab::make($t[0])->modifyQueryUsing($t[1])->badge(number_format($n, 0, ',', '.'))
                ->badgeColor(in_array($key, ['fiyatsiz', 'stokta-yok', 'gorselsiz'], true) && $n ? 'warning' : 'gray');
        })->all();
    }
}
