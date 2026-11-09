<?php

namespace App\Filament\Pages;

use App\Filament\Widgets;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $title = 'Yönetim Paneli';

    protected static ?string $navigationLabel = 'Yönetim Paneli';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    public function getWidgets(): array
    {
        return [
            Widgets\ShopStats::class,
            Widgets\SalesChart::class,
            Widgets\PaymentMethodsChart::class,
            Widgets\Attention::class,
            Widgets\LatestOrders::class,
            Widgets\TopProducts::class,
        ];
    }

    public function getColumns(): int|array
    {
        return ['md' => 2, 'xl' => 3];
    }
}
