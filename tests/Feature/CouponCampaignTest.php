<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class CouponCampaignTest extends TestCase
{
    use RefreshDatabase;

    private Category $sofra;

    private Category $kupa;

    private Category $kitap;

    private Product $mug;

    private Product $book;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->sofra = Category::create(['name' => 'Sofra Grubu', 'slug' => 'sofra-grubu']);
        $this->kupa = Category::create(['name' => 'Kupa', 'slug' => 'kupa', 'parent_id' => $this->sofra->id]);
        $this->kitap = Category::create(['name' => 'Kitap', 'slug' => 'kitap']);
        $this->mug = Product::create(['name' => 'Kupa', 'slug' => 'kupa-1', 'price' => 2000, 'published_at' => now()]);
        $this->mug->categories()->attach($this->kupa);
        $this->book = Product::create(['name' => 'Kitap', 'slug' => 'kitap-1', 'price' => 1000, 'published_at' => now()]);
        $this->book->categories()->attach($this->kitap);
    }

    private function add(Product $p, int $qty = 1): void
    {
        $this->postJson('/cart/add', ['product_id' => $p->id, 'qty' => $qty])->assertOk();
    }

    private function checkout(string $email = 'ayse@example.com'): \Illuminate\Testing\TestResponse
    {
        return $this->post('/checkout', [
            'first_name' => 'Ayşe', 'last_name' => 'Kaya', 'email' => $email, 'phone' => '05331234567',
            'address' => 'Neopark 6', 'city' => 'Girne', 'payment_method' => 'havale', 'terms' => '1',
        ]);
    }

    public function test_percent_coupon_with_cap_applies_and_is_recorded(): void
    {
        Coupon::create(['code' => 'lusso10', 'type' => 'percent', 'value' => 10, 'max_discount' => 250]);
        $this->add($this->mug, 2); // 4000
        $this->post('/cart/kupon', ['code' => ' Lusso10 '])->assertSessionHas('success');
        $this->get('/cart')->assertSee('Kupon LUSSO10')->assertSee('−250 ₺')->assertSee('3750 ₺');

        $this->checkout();
        $o = Order::first();
        $this->assertSame('250.00', $o->discount);
        $this->assertSame('3750.00', $o->total);
        $this->assertSame('LUSSO10', $o->coupon_code);
        $this->assertSame(1, Coupon::first()->used_count);
        $this->assertNull(session('coupon'));
    }

    public function test_coupon_rules_reject_with_reason(): void
    {
        Coupon::create(['code' => 'MIN5K', 'type' => 'fixed', 'value' => 300, 'min_subtotal' => 5000]);
        Coupon::create(['code' => 'ESKI', 'type' => 'fixed', 'value' => 300, 'ends_at' => now()->subDay()]);
        Coupon::create(['code' => 'BITTI', 'type' => 'fixed', 'value' => 300, 'usage_limit' => 1, 'used_count' => 1]);
        Coupon::create(['code' => 'KAPALI', 'type' => 'fixed', 'value' => 300, 'is_active' => false]);
        $this->add($this->mug);

        $this->post('/cart/kupon', ['code' => 'MIN5K'])->assertSessionHas('error', fn ($m) => str_contains($m, '5000 ₺'));
        $this->post('/cart/kupon', ['code' => 'ESKI'])->assertSessionHas('error', fn ($m) => str_contains($m, 'süresi dolmuş'));
        $this->post('/cart/kupon', ['code' => 'BITTI'])->assertSessionHas('error', fn ($m) => str_contains($m, 'hakkı dolmuş'));
        $this->post('/cart/kupon', ['code' => 'KAPALI'])->assertSessionHas('error');
        $this->post('/cart/kupon', ['code' => 'YOK'])->assertSessionHas('error');
        $this->assertNull(session('coupon'));
    }

    public function test_category_restricted_coupon_only_discounts_matching_items(): void
    {
        Coupon::create(['code' => 'SOFRA20', 'type' => 'percent', 'value' => 20, 'category_ids' => [$this->sofra->id]]);
        $this->add($this->mug);   // 2000, alt kategori Kupa → dahil
        $this->add($this->book);  // 1000, hariç
        $this->post('/cart/kupon', ['code' => 'SOFRA20']);
        $this->checkout();
        $this->assertSame('400.00', Order::first()->discount);
    }

    public function test_per_customer_limit(): void
    {
        Coupon::create(['code' => 'TEK', 'type' => 'fixed', 'value' => 100, 'per_customer_limit' => 1]);
        $this->add($this->mug);
        $this->post('/cart/kupon', ['code' => 'TEK']);
        $this->checkout('ali@example.com');
        $this->assertSame('100.00', Order::first()->discount);

        $this->add($this->mug);
        $this->post('/cart/kupon', ['code' => 'TEK']); // e-posta henüz bilinmiyor → kabul
        $this->checkout('ali@example.com')->assertRedirect('/cart')->assertSessionHas('error', fn ($m) => str_contains($m, 'kullanım hakkınızı'));
        $this->assertSame(1, Order::count());
    }

    public function test_failed_payment_and_cancel_release_coupon_usage(): void
    {
        $c = Coupon::create(['code' => 'GERI', 'type' => 'fixed', 'value' => 100, 'usage_limit' => 1]);
        $this->add($this->mug);
        $this->post('/cart/kupon', ['code' => 'GERI']);
        $this->checkout();
        $this->assertSame(1, $c->refresh()->used_count);
        app(\App\Services\OrderService::class)->changeStatus(Order::first(), 'cancelled', null, false);
        $this->assertSame(0, $c->refresh()->used_count);
    }

    public function test_free_shipping_coupon(): void
    {
        \App\Models\Setting::put(['shipping_fee' => '150', 'free_shipping_min' => '99999']);
        Coupon::create(['code' => 'KARGO', 'type' => 'free_shipping', 'value' => 0]);
        $this->add($this->book);
        $this->get('/cart')->assertSee('150 ₺');
        $this->post('/cart/kupon', ['code' => 'KARGO']);
        $this->checkout();
        $o = Order::first();
        $this->assertSame('0.00', $o->shipping);
        $this->assertSame('1000.00', $o->total);
    }

    public function test_category_campaign_changes_displayed_and_charged_price(): void
    {
        Campaign::create(['name' => 'Sofra %25', 'type' => 'category_percent', 'value' => 25, 'category_ids' => [$this->sofra->id], 'banner_text' => 'Sofra Grubunda %25 indirim!', 'badge_text' => 'KAMPANYA']);
        Cache::flush();
        $this->get('/product/kupa-1')->assertSee('1500 ₺')->assertSee('2000 ₺');
        $this->get('/product-category/sofra-grubu')->assertSee('KAMPANYA')->assertSee('-25%');
        $this->get('/')->assertSee('Sofra Grubunda %25 indirim!');
        $this->get('/product/kitap-1')->assertDontSee('1500 ₺');

        $this->add($this->mug);
        $this->checkout();
        $this->assertSame('1500.00', Order::first()->total);
    }

    public function test_cart_campaign_with_threshold_and_schedule(): void
    {
        Campaign::create(['name' => '3000 TL üzeri %10', 'type' => 'cart_percent', 'value' => 10, 'min_subtotal' => 3000]);
        Campaign::create(['name' => 'Gelecek', 'type' => 'cart_fixed', 'value' => 999, 'starts_at' => now()->addDay()]);
        Cache::flush();
        $this->add($this->mug); // 2000 → eşik altı
        $this->get('/cart')->assertDontSee('3000 TL üzeri %10');
        $this->add($this->book); // 3000
        $this->get('/cart')->assertSee('3000 TL üzeri %10')->assertSee('−300 ₺')->assertDontSee('Gelecek');
        $this->checkout();
        $this->assertSame('2700.00', Order::first()->total);
    }

    public function test_campaign_and_coupon_stack(): void
    {
        Campaign::create(['name' => 'Sepet %10', 'type' => 'cart_percent', 'value' => 10]);
        Coupon::create(['code' => 'EK100', 'type' => 'fixed', 'value' => 100]);
        Cache::flush();
        $this->add($this->mug);
        $this->post('/cart/kupon', ['code' => 'EK100']);
        $this->checkout();
        $this->assertSame('300.00', Order::first()->discount);
        $this->assertSame('1700.00', Order::first()->total);
    }

    public function test_admin_coupon_and_campaign_screens(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $c = Coupon::create(['code' => 'X', 'type' => 'percent', 'value' => 5]);
        $k = Campaign::create(['name' => 'K', 'type' => 'free_shipping', 'min_subtotal' => 1000]);
        $this->actingAs($admin);
        foreach (['/admin/coupons', '/admin/coupons/create', '/admin/coupons/'.$c->id.'/edit', '/admin/campaigns', '/admin/campaigns/create', '/admin/campaigns/'.$k->id.'/edit'] as $url) {
            $this->get($url)->assertOk();
        }

        \Livewire\Livewire::test(\App\Filament\Resources\Coupons\Pages\CreateCoupon::class)
            ->fillForm(['code' => 'yeni15', 'type' => 'percent', 'value' => 15, 'is_active' => true, 'per_customer_limit' => 1])
            ->call('create')->assertHasNoFormErrors();
        $this->assertDatabaseHas('coupons', ['code' => 'YENI15']);

        \Livewire\Livewire::test(\App\Filament\Resources\Campaigns\Pages\CreateCampaign::class)
            ->fillForm(['name' => 'Kitap %10', 'type' => 'category_percent', 'value' => 10, 'category_ids' => [$this->kitap->id], 'is_active' => true])
            ->call('create')->assertHasNoFormErrors();
        Cache::flush();
        $this->get('/product/kitap-1')->assertSee('900 ₺');
    }
}
