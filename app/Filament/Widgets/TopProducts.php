<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\OrderItem;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;

class TopProducts extends Widget
{
    protected static ?int $sort = 5;

    protected string $view = 'filament.widgets.top-products';

    protected function getViewData(): array
    {
        $rows = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.payment_status', 'paid')
            ->where('orders.paid_at', '>=', now()->subDays(30))
            ->groupBy('order_items.product_id', 'order_items.name')
            ->select('order_items.product_id', 'order_items.name', DB::raw('SUM(order_items.quantity) as qty'), DB::raw('SUM(order_items.total) as revenue'))
            ->orderByDesc('qty')->limit(6)->get();

        return ['rows' => $rows, 'editUrl' => fn ($id) => $id ? ProductResource::getUrl('edit', ['record' => $id]) : null];
    }
}
