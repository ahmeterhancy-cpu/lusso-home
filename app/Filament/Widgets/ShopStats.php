<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Carbon\CarbonInterface;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ShopStats extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    protected ?string $pollingInterval = '60s';

    private function revenue(CarbonInterface $from, CarbonInterface $to): float
    {
        return (float) Order::where('payment_status', 'paid')->whereBetween('paid_at', [$from, $to])->sum('total');
    }

    /** Son 14 günün günlük ciro dizisi (kartlardaki küçük grafik). */
    private function spark(): array
    {
        $rows = Order::where('payment_status', 'paid')->where('paid_at', '>=', now()->subDays(13)->startOfDay())
            ->get(['paid_at', 'total'])->groupBy(fn ($o) => $o->paid_at->toDateString());

        return collect(range(13, 0))->map(fn ($i) => (float) ($rows[now()->subDays($i)->toDateString()] ?? collect())->sum('total'))->all();
    }

    private function change(float $now, float $before): array
    {
        if ($before <= 0) {
            return [$now > 0 ? 'Önceki dönemde satış yok' : 'Henüz satış yok', 'heroicon-m-minus', 'gray'];
        }
        $pct = round(($now - $before) / $before * 100);

        return $pct >= 0
            ? ["%{$pct} artış (önceki döneme göre)", 'heroicon-m-arrow-trending-up', 'success']
            : ['%'.abs($pct).' düşüş (önceki döneme göre)', 'heroicon-m-arrow-trending-down', 'danger'];
    }

    protected function getStats(): array
    {
        $today = $this->revenue(today(), now());
        $yesterday = $this->revenue(today()->subDay(), today()->subSecond());
        $month = $this->revenue(now()->startOfMonth(), now());
        $lastMonth = $this->revenue(now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow());

        $paidCount = Order::where('payment_status', 'paid')->where('paid_at', '>=', now()->startOfMonth())->count();
        $avg = $paidCount ? $month / $paidCount : 0;

        $waiting = Order::whereIn('status', ['pending', 'processing'])->count();
        $unpaidTransfers = Order::where('payment_method', 'havale')->where('payment_status', 'unpaid')->whereNotIn('status', ['cancelled', 'failed'])->count();

        [$dText, $dIcon, $dColor] = $this->change($today, $yesterday);
        [$mText, $mIcon, $mColor] = $this->change($month, $lastMonth);

        return [
            Stat::make('Bugünkü ciro', money($today))->description($dText)->descriptionIcon($dIcon)->color($dColor)->chart($this->spark()),
            Stat::make('Bu ayki ciro', money($month))->description($mText)->descriptionIcon($mIcon)->color($mColor),
            Stat::make('Ortalama sepet (bu ay)', money($avg))->description($paidCount.' ödenmiş sipariş')->descriptionIcon('heroicon-m-shopping-bag'),
            Stat::make('İşlem bekleyen sipariş', $waiting)
                ->description($unpaidTransfers ? $unpaidTransfers.' havale ödemesi bekleniyor' : 'Bekleyen havale yok')
                ->descriptionIcon('heroicon-m-clock')->color($waiting ? 'warning' : 'gray')
                ->url(OrderResource::getUrl('index', ['tab' => 'bekleyen'])),
        ];
    }
}
