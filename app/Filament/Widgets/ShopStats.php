<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShopStats extends StatsOverviewWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $paid = Order::where('payment_status', 'paid');

        return [
            Stat::make('Bugünkü ciro', money((clone $paid)->whereDate('paid_at', today())->sum('total'))),
            Stat::make('Bu ayki ciro', money((clone $paid)->where('paid_at', '>=', now()->startOfMonth())->sum('total'))),
            Stat::make('Bekleyen sipariş', Order::whereIn('status', ['pending', 'processing'])->count()),
            Stat::make('Yayındaki ürün', Product::where('is_active', true)->count())
                ->description(Product::where('is_active', true)->where('price', 0)->count().' ürünün fiyatı yok'),
        ];
    }
}
