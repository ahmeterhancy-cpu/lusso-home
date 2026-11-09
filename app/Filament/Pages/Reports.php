<?php

namespace App\Filament\Pages;

use App\Models\Order;
use App\Models\OrderItem;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use UnitEnum;

/** Satış raporları: yalnız ödemesi alınmış siparişler sayılır. */
class Reports extends Page implements HasSchemas
{
    use \App\Filament\Support\RestrictedArea;

    protected static string $area = 'reports';

    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static string|UnitEnum|null $navigationGroup = 'Satış';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Raporlar';

    protected string $view = 'filament.pages.reports';

    public ?array $filters = [];

    public function mount(): void
    {
        $this->form->fill(['preset' => 'this_month', 'from' => now()->startOfMonth()->toDateString(), 'until' => now()->toDateString()]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Grid::make(3)->schema([
                Select::make('preset')->label('Dönem')->live()->options([
                    'today' => 'Bugün', 'yesterday' => 'Dün', '7' => 'Son 7 gün', '30' => 'Son 30 gün',
                    'this_month' => 'Bu ay', 'last_month' => 'Geçen ay', 'this_year' => 'Bu yıl', 'custom' => 'Özel aralık',
                ])->afterStateUpdated(function ($state, $set) {
                    [$f, $u] = match ($state) {
                        'today' => [today(), today()],
                        'yesterday' => [today()->subDay(), today()->subDay()],
                        '7' => [today()->subDays(6), today()],
                        '30' => [today()->subDays(29), today()],
                        'last_month' => [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()],
                        'this_year' => [now()->startOfYear(), today()],
                        default => [now()->startOfMonth(), today()],
                    };
                    if ($state !== 'custom') {
                        $set('from', $f->toDateString());
                        $set('until', $u->toDateString());
                    }
                }),
                DatePicker::make('from')->label('Başlangıç')->live()->afterStateUpdated(fn ($set) => $set('preset', 'custom')),
                DatePicker::make('until')->label('Bitiş')->live()->afterStateUpdated(fn ($set) => $set('preset', 'custom')),
            ]),
        ])->statePath('filters');
    }

    private function range(): array
    {
        $from = Carbon::parse($this->filters['from'] ?? now()->startOfMonth())->startOfDay();
        $until = Carbon::parse($this->filters['until'] ?? now())->endOfDay();

        return [$from, $until];
    }

    private function paid()
    {
        [$from, $until] = $this->range();

        return Order::query()->where('payment_status', 'paid')->whereBetween('paid_at', [$from, $until]);
    }

    public function report(): array
    {
        [$from, $until] = $this->range();
        $paid = $this->paid();
        $orderIds = (clone $paid)->pluck('id');
        $revenue = (float) (clone $paid)->sum('total');
        $count = $orderIds->count();

        $daily = (clone $paid)->get(['paid_at', 'total'])->groupBy(fn ($o) => $o->paid_at->toDateString())
            ->map(fn ($g, $d) => ['date' => Carbon::parse($d)->format('d.m.Y'), 'orders' => $g->count(), 'revenue' => (float) $g->sum('total')])
            ->sortKeysDesc()->values()->all();

        $items = OrderItem::query()->whereIn('order_id', $orderIds);
        $topProducts = (clone $items)->groupBy('product_id', 'name')
            ->select('product_id', 'name', DB::raw('SUM(quantity) as qty'), DB::raw('SUM(total) as revenue'))
            ->orderByDesc('revenue')->limit(15)->get()->toArray();

        $byCategory = DB::table('order_items')->whereIn('order_items.order_id', $orderIds)
            ->join('category_product', 'category_product.product_id', '=', 'order_items.product_id')
            ->join('categories', 'categories.id', '=', 'category_product.category_id')
            ->whereNull('categories.parent_id')
            ->groupBy('categories.name')
            ->select('categories.name', DB::raw('SUM(order_items.quantity) as qty'), DB::raw('SUM(order_items.total) as revenue'))
            ->orderByDesc('revenue')->get()->map(fn ($r) => (array) $r)->all();

        $byMethod = (clone $paid)->groupBy('payment_method')->select('payment_method', DB::raw('COUNT(*) as n'), DB::raw('SUM(total) as revenue'))
            ->get()->map(fn ($r) => ['label' => (Order::METHODS + Order::PANEL_METHODS)[$r->payment_method] ?? $r->payment_method, 'n' => $r->n, 'revenue' => (float) $r->revenue])->all();

        $byCity = (clone $paid)->groupBy('city')->select('city', DB::raw('COUNT(*) as n'), DB::raw('SUM(total) as revenue'))
            ->orderByDesc('revenue')->get()->toArray();

        $bySource = (clone $paid)->groupBy('source')->select('source', DB::raw('COUNT(*) as n'), DB::raw('SUM(total) as revenue'))
            ->get()->map(fn ($r) => ['label' => $r->source === 'panel' ? 'Mağaza / telefon (panel)' : 'Web sitesi', 'n' => $r->n, 'revenue' => (float) $r->revenue])->all();

        $coupons = (clone $paid)->whereNotNull('coupon_code')->groupBy('coupon_code')
            ->select('coupon_code', DB::raw('COUNT(*) as n'), DB::raw('SUM(discount) as discount'), DB::raw('SUM(total) as revenue'))
            ->orderByDesc('n')->get()->toArray();

        $cancelled = Order::whereIn('status', ['cancelled', 'failed'])->whereBetween('created_at', [$from, $until]);

        return [
            'from' => $from->format('d.m.Y'), 'until' => $until->format('d.m.Y'),
            'revenue' => $revenue, 'count' => $count, 'avg' => $count ? $revenue / $count : 0,
            'discount' => (float) (clone $paid)->sum('discount'), 'shipping' => (float) (clone $paid)->sum('shipping'),
            'units' => (int) (clone $items)->sum('quantity'),
            'cancelled' => (clone $cancelled)->count(), 'cancelledTotal' => (float) (clone $cancelled)->sum('total'),
            'unpaid' => Order::where('payment_status', 'unpaid')->whereNotIn('status', ['cancelled', 'failed'])->whereBetween('created_at', [$from, $until])->count(),
            'daily' => $daily, 'topProducts' => $topProducts, 'byCategory' => $byCategory, 'byMethod' => $byMethod,
            'byCity' => $byCity, 'bySource' => $bySource, 'coupons' => $coupons,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('csv')->label('Siparişleri CSV indir')->icon('heroicon-o-arrow-down-tray')->color('gray')
                ->action(function () {
                    $orders = $this->paid()->with('items')->orderBy('paid_at')->get();

                    return response()->streamDownload(function () use ($orders) {
                        $out = fopen('php://output', 'w');
                        fwrite($out, "\xEF\xBB\xBF");
                        fputcsv($out, ['Sipariş', 'Ödeme tarihi', 'Müşteri', 'Telefon', 'Şehir', 'Ödeme', 'Kaynak', 'Ara toplam', 'İndirim', 'Kupon', 'Kargo', 'Toplam', 'Ürünler'], ';');
                        foreach ($orders as $o) {
                            fputcsv($out, [$o->number, $o->paid_at?->format('d.m.Y H:i'), $o->first_name.' '.$o->last_name, $o->phone, $o->city, $o->methodLabel(),
                                $o->source === 'panel' ? 'Panel' : 'Web', $o->subtotal, $o->discount, $o->coupon_code, $o->shipping, $o->total,
                                $o->items->map(fn ($i) => $i->name.' x'.$i->quantity)->join(' | ')], ';');
                        }
                        fclose($out);
                    }, 'satis-raporu-'.now()->format('Ymd-His').'.csv');
                }),
        ];
    }
}
