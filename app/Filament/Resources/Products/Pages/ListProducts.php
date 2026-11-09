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
        return [
            \Filament\Actions\ActionGroup::make([
                \Filament\Actions\Action::make('export')->label('Tüm ürünleri CSV indir')->icon('heroicon-o-arrow-down-tray')
                    ->action(fn () => app(\App\Services\ProductCsv::class)->export()),
                \Filament\Actions\Action::make('import')->label('Excel / CSV ile fiyat ve stok güncelle')->icon('heroicon-o-arrow-up-tray')
                    ->modalDescription('İlk satırda sütun başlıkları olmalı: "Stok Kodu" (veya id) ve güncellemek istediğiniz sütunlar — Fiyat, İndirimli Fiyat, Stok, Yayında. Ürünler stok koduyla eşleşir; yeni ürün oluşturulmaz, ürün silinmez. Örnek dosya için "Tüm ürünleri CSV indir".')
                    ->schema([
                        \Filament\Forms\Components\FileUpload::make('file')->label('Excel (.xlsx) veya CSV dosyası')->disk('local')->directory('csv-import')
                            ->acceptedFileTypes(['text/csv', 'text/plain', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', '.csv', '.xlsx'])
                            ->maxSize(10240)->required(),
                    ])
                    ->action(function (array $data) {
                        $path = \Illuminate\Support\Facades\Storage::disk('local')->path($data['file']);
                        $r = app(\App\Services\ProductCsv::class)->import($path);
                        @unlink($path);
                        $body = "{$r['updated']} ürün güncellendi, {$r['unchanged']} ürün değişmedi."
                            .($r['notFound'] ? ' Bulunamayan: '.count($r['notFound']).' ('.implode(', ', array_slice($r['notFound'], 0, 5)).')' : '')
                            .($r['errors'] ? ' Uyarılar: '.implode(' ', array_slice($r['errors'], 0, 3)) : '');
                        \Filament\Notifications\Notification::make()->title('İçe aktarma tamamlandı')->body($body)
                            ->{$r['errors'] || $r['notFound'] ? 'warning' : 'success'}()->persistent()->send();
                    }),
            ])->label('İçe / dışa aktar')->icon('heroicon-o-arrows-up-down')->button()->color('gray'),
            CreateAction::make()->label('Yeni ürün'),
        ];
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
