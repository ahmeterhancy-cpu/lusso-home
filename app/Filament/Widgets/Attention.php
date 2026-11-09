<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Products\ProductResource;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use Filament\Widgets\Widget;

/** Panelde ilk bakışta "yapılacaklar" listesi. */
class Attention extends Widget
{
    protected static ?int $sort = 3;

    protected string $view = 'filament.widgets.attention';

    protected function getViewData(): array
    {
        $items = [
            ['Havale ödemesi bekleyen sipariş', Order::where('payment_method', 'havale')->where('payment_status', 'unpaid')->whereNotIn('status', ['cancelled', 'failed'])->count(), OrderResource::getUrl('index', ['tab' => 'odeme-bekleyen']), 'heroicon-o-banknotes', 'warning'],
            ['Hazırlanacak sipariş', Order::where('status', 'processing')->count(), OrderResource::getUrl('index', ['tab' => 'hazirlaniyor']), 'heroicon-o-archive-box', 'info'],
            ['Okunmamış mesaj', ContactMessage::where('is_read', false)->count(), ContactMessageResource::getUrl(), 'heroicon-o-envelope', 'info'],
            ['Stokta yok', Product::where('is_active', true)->where('in_stock', false)->count(), ProductResource::getUrl('index', ['tab' => 'stokta-yok']), 'heroicon-o-exclamation-triangle', 'danger'],
            ['Fiyatı girilmemiş ürün', Product::where('is_active', true)->where('price', 0)->count(), ProductResource::getUrl('index', ['tab' => 'fiyatsiz']), 'heroicon-o-currency-dollar', 'gray'],
            ['Görseli olmayan ürün', Product::where('is_active', true)->doesntHave('images')->count(), ProductResource::getUrl('index', ['tab' => 'gorselsiz']), 'heroicon-o-photo', 'gray'],
        ];

        return ['items' => $items];
    }
}
