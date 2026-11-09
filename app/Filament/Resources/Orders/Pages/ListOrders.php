<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    /** Sekme adı => [etiket, kapsam]. Filament closure parametrelerini ADA göre verir: $query şart. */
    private function scopes(): array
    {
        return [
            'tumu' => ['Tümü', fn (Builder $query) => $query],
            'bekleyen' => ['İşlem bekleyen', fn (Builder $query) => $query->whereIn('status', ['pending', 'processing'])],
            'odeme-bekleyen' => ['Ödeme bekleyen', fn (Builder $query) => $query->where('payment_status', 'unpaid')->whereNotIn('status', ['cancelled', 'failed'])],
            'hazirlaniyor' => ['Hazırlanıyor', fn (Builder $query) => $query->where('status', 'processing')],
            'kargoda' => ['Kargoda', fn (Builder $query) => $query->where('status', 'shipped')],
            'tamamlanan' => ['Tamamlanan', fn (Builder $query) => $query->where('status', 'completed')],
            'iptal' => ['İptal / başarısız', fn (Builder $query) => $query->whereIn('status', ['cancelled', 'failed'])],
        ];
    }

    public function getTabs(): array
    {
        return collect($this->scopes())->map(function ($t, $key) {
            $n = $key === 'tumu' ? 0 : $t[1](Order::query())->count();

            return Tab::make($t[0])->modifyQueryUsing($t[1])->badge($n ?: null);
        })->all();
    }
}
