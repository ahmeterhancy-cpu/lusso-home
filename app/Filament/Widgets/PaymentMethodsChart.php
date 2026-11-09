<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class PaymentMethodsChart extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'Ödeme yöntemleri (bu ay)';

    protected ?string $maxHeight = '280px';

    protected function getData(): array
    {
        $rows = Order::where('created_at', '>=', now()->startOfMonth())->whereNotIn('status', ['cancelled', 'failed'])
            ->selectRaw('payment_method, count(*) as n')->groupBy('payment_method')->pluck('n', 'payment_method');

        return [
            'datasets' => [[
                'data' => array_map(fn ($m) => (int) ($rows[$m] ?? 0), array_keys(Order::METHODS)),
                'backgroundColor' => ['#db8700', '#37383c', '#9ca3af'],
            ]],
            'labels' => ['Kart (Tiko)', 'Havale / EFT', 'Kapıda'],
        ];
    }

    protected function getOptions(): array
    {
        return ['plugins' => ['legend' => ['position' => 'bottom']], 'scales' => ['x' => ['display' => false], 'y' => ['display' => false]]];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
