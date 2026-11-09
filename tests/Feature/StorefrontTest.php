<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\Slide;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    private Category $cat;

    private Category $child;

    private Product $priced;

    private Product $noPrice;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cat = Category::create(['name' => 'Sofra Grubu', 'slug' => 'sofra-grubu', 'is_trend' => true]);
        $this->child = Category::create(['name' => 'Kupa', 'slug' => 'kupa', 'parent_id' => $this->cat->id]);
        $this->priced = Product::create(['name' => 'Flamingo Kupa', 'slug' => 'flamingo-kupa', 'sku' => 'YE-1', 'price' => 1700, 'published_at' => now()]);
        $this->priced->categories()->attach($this->child);
        $this->priced->images()->create(['path' => 'urunler/1.jpg', 'sort' => 0]);
        $this->noPrice = Product::create(['name' => 'Courtly Vazo', 'slug' => 'courtly-vazo', 'price' => 0, 'published_at' => now()->subDay()]);
        $this->noPrice->categories()->attach($this->cat);
        Page::create(['title' => 'Hakkımızda', 'slug' => 'hakkimizda', 'content' => '<p>Lusso 2018</p>']);
        Slide::create(['image' => 'slider/slide-1.jpg', 'title' => 'LUSSO HOME CONCEPT']);
    }

    public function test_storefront_pages_render(): void
    {
        foreach (['/', '/shop', '/product-category/sofra-grubu', '/product-category/sofra-grubu/kupa', '/product/flamingo-kupa',
            '/product/courtly-vazo', '/cart', '/hesabim', '/hesabim?action=register', '/iletisim', '/hakkimizda', '/blog',
            '/begendiklerim', '/siparis-takibi', '/arama?s=kupa', '/sitemap.xml', '/hesabim/lost-password'] as $url) {
            $res = $this->get($url);
            $res->assertOk();
            $this->assertStringNotContainsString('Undefined', $res->getContent(), $url);
        }
    }

    public function test_child_category_products_appear_in_parent(): void
    {
        $this->get('/product-category/sofra-grubu')->assertSee('Flamingo Kupa')->assertSee('Courtly Vazo');
    }

    public function test_old_wordpress_search_url_redirects(): void
    {
        $this->get('/?s=kupa&post_type=product')->assertRedirect(route('search', ['s' => 'kupa']));
    }

    public function test_product_without_price_cannot_be_added(): void
    {
        $this->get('/product/courtly-vazo')->assertDontSee('Sepete Ekle</button>', false);
        $this->postJson('/cart/add', ['product_id' => $this->noPrice->id])->assertStatus(422);
        $this->assertSame([], session('cart', []));
    }

    public function test_cart_add_update_remove(): void
    {
        $this->postJson('/cart/add', ['product_id' => $this->priced->id, 'qty' => 2])
            ->assertOk()->assertJsonPath('cart.count', 2)->assertJsonPath('cart.subtotal_text', '3400 ₺');
        $this->post('/cart/update', ['qty' => [$this->priced->id => 3]])->assertRedirect('/cart');
        $this->get('/cart')->assertSee('5100 ₺');
        $this->postJson('/cart/remove/'.$this->priced->id)->assertJsonPath('cart.count', 0);
    }

    private function checkout(string $method): \Illuminate\Testing\TestResponse
    {
        $this->postJson('/cart/add', ['product_id' => $this->priced->id, 'qty' => 1]);

        return $this->post('/checkout', [
            'first_name' => 'Ayşe', 'last_name' => 'Kaya', 'email' => 'ayse@example.com', 'phone' => '05331234567',
            'address' => 'Neopark 6', 'city' => 'Girne', 'payment_method' => $method, 'terms' => '1',
        ]);
    }

    public function test_havale_checkout_creates_order_and_mails(): void
    {
        Mail::fake();
        $res = $this->checkout('havale');
        $order = Order::first();
        $this->assertNotNull($order);
        $res->assertRedirect(route('order.thanks', ['order' => $order->number, 't' => $order->track_token]));
        $this->assertSame('1700.00', $order->total);
        $this->assertSame('pending', $order->status);
        $this->assertSame([], session('cart', []));
        Mail::assertSent(\App\Mail\OrderPlaced::class);
        $this->get(route('order.thanks', ['order' => $order->number, 't' => $order->track_token]))->assertOk()->assertSee($order->number);
        $this->get(route('order.thanks', ['order' => $order->number, 't' => 'yanlis']))->assertNotFound();
    }

    public function test_kapida_checkout(): void
    {
        $this->checkout('kapida');
        $this->assertSame('kapida', Order::first()->payment_method);
    }

    public function test_checkout_requires_terms(): void
    {
        $this->postJson('/cart/add', ['product_id' => $this->priced->id]);
        $this->post('/checkout', ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.co', 'phone' => '1', 'address' => 'x', 'city' => 'Girne', 'payment_method' => 'havale'])
            ->assertSessionHasErrors('terms');
        $this->assertSame(0, Order::count());
    }

    public function test_stock_quantity_is_decremented(): void
    {
        $this->priced->update(['stock_qty' => 1]);
        $this->checkout('havale');
        $this->priced->refresh();
        $this->assertSame(0, $this->priced->stock_qty);
        $this->assertFalse($this->priced->in_stock);
    }

    private function configureTiko(): void
    {
        config(['services.tiko.merchant_id' => 'M1', 'services.tiko.secret_key' => 'gizli', 'services.tiko.password' => 'sifre', 'services.tiko.allow_simulation' => false]);
    }

    public function test_tiko_checkout_shows_card_form_posting_to_tiko(): void
    {
        $this->configureTiko();
        $this->checkout('tiko')->assertRedirect();
        $order = Order::first();
        $this->get(route('tiko.pay', ['order' => $order->number, 't' => $order->track_token]))
            ->assertOk()->assertSee('tikokart.com/api-sanalpos', false)->assertSee('name="Hash"', false);
    }

    public function test_tiko_return_verifies_status_before_marking_paid(): void
    {
        $this->configureTiko();
        $this->checkout('tiko');
        $order = Order::first();
        $tiko = app(\App\Services\TikoService::class);
        $result = ['MerchantId' => 'M1', 'OrderId' => $order->number, 'Amount' => '1700.00', 'Currency' => 'TRY', 'Installment' => '0', 'Status' => '200', 'TransId' => 'T9'];
        $result['Hash'] = $tiko->hash('M1'.$order->number.'1700.00TRY0200T9');
        Http::fake(['*payment/status' => Http::response(['Status' => '200', 'Result' => $result])]);
        Mail::fake();

        $this->post(route('tiko.return', ['order' => $order->number, 'status' => 'ok']))
            ->assertRedirect(route('order.thanks', ['order' => $order->number, 't' => $order->track_token]));
        $order->refresh();
        $this->assertSame('paid', $order->payment_status);
        $this->assertSame('T9', $order->payment_ref);
    }

    public function test_tiko_return_rejects_forged_status(): void
    {
        $this->configureTiko();
        $this->checkout('tiko');
        $order = Order::first();
        Http::fake(['*payment/status' => Http::response(['Status' => '200', 'Result' => [
            'MerchantId' => 'M1', 'OrderId' => $order->number, 'Amount' => '1700.00', 'Currency' => 'TRY', 'Installment' => '0', 'Status' => '200', 'TransId' => 'T9', 'Hash' => 'sahte',
        ]])]);
        $this->post(route('tiko.return', ['order' => $order->number, 'status' => 'ok']));
        $this->assertSame('unpaid', $order->refresh()->payment_status);
    }

    public function test_tiko_callback_requires_valid_signature(): void
    {
        $this->configureTiko();
        $this->checkout('tiko');
        $order = Order::first();
        $this->post('/tiko/bildirim', ['OrderId' => $order->number, 'Status' => '200', 'Hash' => 'x'])->assertStatus(400);
        $p = ['MerchantId' => 'M1', 'OrderId' => $order->number, 'Amount' => '1700.00', 'Currency' => 'TRY', 'Installment' => '0', 'Status' => '200', 'TransId' => 'T1'];
        $p['Hash'] = app(\App\Services\TikoService::class)->hash(implode('', $p));
        $this->post('/tiko/bildirim', $p)->assertOk();
        $this->assertSame('paid', $order->refresh()->payment_status);
    }

    public function test_register_login_and_orders(): void
    {
        $this->post('/hesabim/kayit', ['name' => 'Ali Veli', 'email' => 'ali@example.com', 'password' => 'Gizli12345', 'password_confirmation' => 'Gizli12345', 'terms' => '1'])
            ->assertRedirect('/hesabim');
        $this->assertAuthenticated();
        $this->get('/hesabim')->assertSee('Ali Veli');
        $this->get('/hesabim/orders')->assertOk();
        $this->post('/hesabim/cikis');
        $this->assertGuest();
        $this->post('/hesabim/giris', ['email' => 'ali@example.com', 'password' => 'yanlis'])->assertSessionHasErrors('email');
        $this->post('/hesabim/giris', ['email' => 'ali@example.com', 'password' => 'Gizli12345'])->assertRedirect('/hesabim');
    }

    public function test_wishlist_toggle_for_guest(): void
    {
        $this->postJson('/begendiklerim/toggle', ['product_id' => $this->priced->id])->assertJsonPath('added', true);
        $this->get('/begendiklerim')->assertSee('Flamingo Kupa');
    }

    public function test_contact_form_stores_message(): void
    {
        $this->post('/iletisim', ['name' => 'Zeynep', 'email' => 'z@example.com', 'message' => 'Merhaba'])->assertSessionHas('success');
        $this->assertDatabaseHas('contact_messages', ['name' => 'Zeynep']);
    }

    public function test_guest_order_tracking(): void
    {
        $this->checkout('havale');
        $order = Order::first();
        $this->post('/siparis-takibi', ['number' => $order->number, 'email' => 'baska@example.com'])->assertSessionHas('error');
        $this->post('/siparis-takibi', ['number' => $order->number, 'email' => 'ayse@example.com'])->assertOk()->assertSee('Flamingo Kupa');
    }

    public function test_admin_pages_render_for_admin_only(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin')->assertForbidden();

        $admin = User::factory()->create();
        $admin->forceFill(['is_admin' => true])->save();
        $this->checkout('havale');
        $order = Order::first();
        $this->actingAs($admin);
        foreach (['/admin', '/admin/products', '/admin/products/create', '/admin/products/'.$this->priced->id.'/edit',
            '/admin/categories', '/admin/categories/'.$this->cat->id.'/edit', '/admin/orders', '/admin/orders/'.$order->id.'/edit',
            '/admin/slides', '/admin/pages', '/admin/posts', '/admin/contact-messages', '/admin/users', '/admin/manage-settings'] as $url) {
            $this->get($url)->assertOk();
        }
    }
}
