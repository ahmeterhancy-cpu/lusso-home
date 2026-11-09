<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\Pages\ViewOrder;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Mail\OrderStatusChanged;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class AdminActionsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($this->admin);
    }

    private function order(array $attrs = []): Order
    {
        $o = Order::create($attrs + [
            'number' => 'LHC-T-'.Str::random(4), 'payment_method' => 'havale', 'first_name' => 'Ayşe', 'last_name' => 'Kaya',
            'email' => 'a@b.co', 'phone' => '05331234567', 'address' => 'Neopark 6', 'city' => 'Girne',
            'subtotal' => 100, 'total' => 100, 'track_token' => Str::random(32),
        ]);
        $o->items()->create(['name' => 'Kupa', 'price' => 100, 'quantity' => 1, 'total' => 100]);

        return $o;
    }

    public function test_dashboard_and_order_view_render_with_data(): void
    {
        $o = $this->order(['payment_status' => 'paid', 'paid_at' => now()]);
        $this->get('/admin')->assertOk()->assertSee('Yönetim Paneli');
        // Bileşenler tembel yüklenir; her birini ayrıca çiz.
        foreach ([\App\Filament\Widgets\ShopStats::class, \App\Filament\Widgets\SalesChart::class, \App\Filament\Widgets\PaymentMethodsChart::class,
            \App\Filament\Widgets\Attention::class, \App\Filament\Widgets\LatestOrders::class, \App\Filament\Widgets\TopProducts::class] as $w) {
            Livewire::test($w)->assertOk();
        }
        Livewire::test(\App\Filament\Widgets\Attention::class)->assertSee('Havale ödemesi bekleyen');
        Livewire::test(\App\Filament\Widgets\TopProducts::class)->assertSee('Kupa');
        $this->get('/admin/orders/'.$o->id)->assertOk()->assertSee($o->number)->assertSee('Neopark 6');
        $this->get('/admin/orders?tab=odeme-bekleyen')->assertOk();
        $this->get(route('admin.order.print', $o))->assertOk()->assertSee($o->number);
    }

    public function test_print_page_is_admin_only(): void
    {
        $o = $this->order();
        $this->actingAs(User::factory()->create())->get(route('admin.order.print', $o))->assertForbidden();
    }

    public function test_admin_can_mark_bank_transfer_order_paid(): void
    {
        Mail::fake();
        $o = $this->order();
        Livewire::test(ListOrders::class)->callTableAction('paid', $o);
        $o->refresh();
        $this->assertSame('paid', $o->payment_status);
        $this->assertSame('processing', $o->status);
    }

    public function test_status_change_emails_customer_and_logs(): void
    {
        Mail::fake();
        $o = $this->order();
        Livewire::test(ViewOrder::class, ['record' => $o->id])
            ->callAction('status', ['status' => 'shipped', 'tracking' => 'KRG123', 'note' => 'Yarın teslim', 'notify' => true]);
        $o->refresh();
        $this->assertSame('shipped', $o->status);
        $this->assertSame('KRG123', $o->tracking_no);
        Mail::assertSent(OrderStatusChanged::class);
        $this->assertTrue($o->events()->where('message', 'like', '%Kargoda%')->exists());
    }

    public function test_cancel_returns_stock(): void
    {
        Mail::fake();
        $p = Product::create(['name' => 'Vazo', 'slug' => 'vazo', 'price' => 100, 'stock_qty' => 0, 'in_stock' => false]);
        $o = $this->order();
        $o->items()->first()->update(['product_id' => $p->id]);
        Livewire::test(ViewOrder::class, ['record' => $o->id])->callAction('status', ['status' => 'cancelled', 'notify' => false]);
        $p->refresh();
        $this->assertSame(1, $p->stock_qty);
        $this->assertTrue($p->in_stock);
    }

    public function test_bulk_price_percent_and_category(): void
    {
        $a = Product::create(['name' => 'A', 'slug' => 'a', 'price' => 1000]);
        $b = Product::create(['name' => 'B', 'slug' => 'b', 'price' => 0]);
        $cat = Category::create(['name' => 'Outlet', 'slug' => 'outlet']);

        Livewire::test(ListProducts::class)->callTableBulkAction('price', [$a, $b], ['mode' => 'percent', 'value' => 10, 'round' => true]);
        $this->assertSame('1100.00', $a->refresh()->price);
        $this->assertSame('0.00', $b->refresh()->price); // fiyatsız ürün etkilenmez

        Livewire::test(ListProducts::class)->callTableBulkAction('price', [$a], ['mode' => 'sale_percent', 'value' => 20, 'round' => true]);
        $this->assertSame('880.00', $a->refresh()->sale_price);

        Livewire::test(ListProducts::class)->callTableBulkAction('category', [$a, $b], ['categories' => [$cat->id], 'replace' => false]);
        $this->assertSame(2, $cat->products()->count());
    }

    public function test_product_tabs_render(): void
    {
        Product::create(['name' => 'Fiyatsız', 'slug' => 'f', 'price' => 0]);
        foreach (['tumu', 'fiyatsiz', 'stokta-yok', 'gorselsiz', 'indirimli', 'one-cikan', 'taslak'] as $tab) {
            $this->get('/admin/products?tab='.$tab)->assertOk();
        }
    }

    public function test_user_admin_flag_can_be_saved_from_panel(): void
    {
        $u = User::factory()->create();
        Livewire::test(\App\Filament\Resources\Users\Pages\ManageUsers::class)
            ->callTableAction('edit', $u, ['name' => $u->name, 'email' => $u->email, 'is_admin' => true]);
        $this->assertTrue($u->refresh()->is_admin);
    }
}
