<?php

namespace App\Filament\Widgets;

use App\Models\Order;
use Filament\Widgets\ChartWidget;

class SalesChart extends ChartWidget
{
    protected static ?int $sort = 1;

    protected ?string $heading = 'Ciro';

    protected int|string|array $columnSpan = ['md' => 2, 'xl' => 2];

    protected ?string $maxHeight = '280px';

    public ?string $filter = '30';

    protected function getFilters(): ?array
    {
        return ['7' => 'Son 7 gün', '30' => 'Son 30 gün', '90' => 'Son 90 gün'];
    }

    protected function getData(): array
    {
        $days = (int) $this->filter;
        $orders = Order::where('payment_status', 'paid')->whereNotNull('paid_at')
            ->where('paid_at', '>=', now()->subDays($days - 1)->startOfDay())
            ->get(['paid_at', 'total'])
            ->groupBy(fn (Order $o) => $o->paid_at->toDateString());

        $labels = $revenue = $count = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $labels[] = $d->format('d.m');
            $group = $orders[$d->toDateString()] ?? collect();
            $revenue[] = round((float) $group->sum('total'), 2);
            $count[] = $group->count();
        }

        return [
            'datasets' => [
                ['label' => 'Ciro (₺)', 'data' => $revenue, 'borderColor' => '#db8700', 'backgroundColor' => 'rgba(219,135,0,.12)', 'fill' => true, 'tension' => .35, 'yAxisID' => 'y'],
                ['label' => 'Sipariş', 'data' => $count, 'type' => 'bar', 'backgroundColor' => 'rgba(55,56,60,.18)', 'yAxisID' => 'y1'],
            ],
            'labels' => $labels,
        ];
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'y' => ['beginAtZero' => true, 'position' => 'left'],
                'y1' => ['beginAtZero' => true, 'position' => 'right', 'grid' => ['drawOnChartArea' => false], 'ticks' => ['precision' => 0]],
            ],
            'plugins' => ['legend' => ['display' => true, 'position' => 'bottom']],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
