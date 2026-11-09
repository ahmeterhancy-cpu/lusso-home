<?php

namespace Tests\Feature;

use App\Filament\Pages\ManageAppearance;
use App\Filament\Pages\ManageSheetSync;
use App\Filament\Resources\ContactMessages\Pages\ManageContactMessages;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Order;
use App\Models\Product;
use App\Models\Redirect;
use App\Models\Setting;
use App\Models\User;
use App\Services\ProductCsv;
use App\Services\SheetSync;
use App\Support\SiteContent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Tests\TestCase;

class AdminPowerToolsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $p;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->admin = User::factory()->create(['is_admin' => true, 'role' => 'admin']);
        $cat = Category::create(['name' => 'Kupa', 'slug' => 'kupa']);
        $this->p = Product::create(['name' => 'Flamingo Kupa', 'slug' => 'flamingo', 'sku' => 'YE-1', 'price' => 1700, 'published_at' => now()]);
        $this->p->categories()->attach($cat);
        SiteContent::flush();
    }

    public function test_appearance_changes_homepage_texts_menu_and_footer(): void
    {
        $this->actingAs($this->admin);
        $cols = SiteContent::defaults()['footer_columns'];
        $cols[0]['title'] = 'Kısayollar';

        Livewire::test(ManageAppearance::class)
            ->fillForm([
                'header_lines' => [['text' => 'Yeni slogan satırı'], ['text' => 'İkinci satır']],
                'latest_title' => 'Yeni Gelenler',
                'sections' => array_replace(SiteContent::defaults()['sections'], ['promo' => false]),
                'menu_extra' => [['label' => 'Kampanyalar', 'url' => '/kampanyalar']],
                'footer_columns' => $cols,
                'credit' => 'Yapım: Amesis',
                'home_grid_category' => 'kupa',
            ])
            ->call('save')->assertHasNoErrors();

        $html = $this->get('/')->assertOk()->getContent();
        $this->assertStringContainsString('Yeni slogan satırı', $html);
        $this->assertStringContainsString('Yeni Gelenler', $html);
        $this->assertStringContainsString('Kampanyalar', $html);
        $this->assertStringContainsString('Kısayollar', $html);
        $this->assertStringContainsString('Yapım: Amesis', $html);
        $this->assertStringNotContainsString('EVİNİZİN TERCİHİ', $html);
    }

    public function test_redirects_and_robots(): void
    {
        Redirect::create(['from_path' => 'https://lussohomeconcept.com/eski-urun/?x=1', 'to_url' => '/product/flamingo']);
        $this->get('/eski-urun/')->assertRedirect(url('/product/flamingo'))->assertStatus(301);
        $this->assertSame(1, Redirect::first()->hits);
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /admin')->assertSee('sitemap.xml');
    }

    public function test_maintenance_mode_blocks_visitors_but_not_admin(): void
    {
        Setting::put(['maintenance' => '1']);
        $this->get('/')->assertStatus(503)->assertSee('Kısa bir ara veriyoruz');
        $this->actingAs($this->admin)->get('/')->assertOk();
    }

    public function test_seo_fields_override_title_and_description(): void
    {
        $this->p->update(['meta_title' => 'Flamingo Kupa Kıbrıs', 'meta_description' => 'Özel açıklama']);
        $this->get('/product/flamingo')->assertSee('<title>Flamingo Kupa Kıbrıs', false)->assertSee('content="Özel açıklama"', false);
    }

    public function test_csv_and_xlsx_import_with_turkish_headers(): void
    {
        $csv = tempnam(sys_get_temp_dir(), 'c').'.csv';
        file_put_contents($csv, "\xEF\xBB\xBFStok Kodu;Satış Fiyatı;Stok\nYE-1;1.950,50;0\nYOK-9;10;1\n");
        $r = app(ProductCsv::class)->import($csv);
        $this->assertSame(1, $r['updated']);
        $this->assertCount(1, $r['notFound']);
        $this->p->refresh();
        $this->assertSame('1950.50', $this->p->price);
        $this->assertSame(0, $this->p->stock_qty);
        $this->assertFalse($this->p->in_stock);

        $xlsx = tempnam(sys_get_temp_dir(), 'x').'.xlsx';
        $w = new XlsxWriter;
        $w->openToFile($xlsx);
        $w->addRow(Row::fromValues(['SKU', 'Fiyat', 'İndirimli Fiyat', 'Adet']));
        $w->addRow(Row::fromValues(['YE-1', 2100, 1890, 4]));
        $w->close();
        rename($xlsx, $noExt = substr($xlsx, 0, -5)); // Drive'dan uzantısız inebilir
        app(ProductCsv::class)->import($noExt);
        $this->p->refresh();
        $this->assertSame('2100.00', $this->p->price);
        $this->assertSame('1890.00', $this->p->sale_price);
        $this->assertSame(4, $this->p->stock_qty);
        $this->assertTrue($this->p->in_stock);
    }

    public function test_drive_links_are_converted_to_download_urls(): void
    {
        $s = app(SheetSync::class);
        $this->assertSame('https://docs.google.com/spreadsheets/d/ABC_123/export?format=csv&gid=77',
            $s->downloadUrl('https://docs.google.com/spreadsheets/d/ABC_123/edit#gid=77'));
        $this->assertSame('https://drive.google.com/uc?export=download&id=FILE-9',
            $s->downloadUrl('https://drive.google.com/file/d/FILE-9/view?usp=sharing'));
    }

    public function test_sheet_sync_updates_prices_and_records_result(): void
    {
        Http::fake(['docs.google.com/*' => Http::response("Stok Kodu,Fiyat,Stok\nYE-1,2500,7\n")]);
        Setting::put(['sheet_url' => 'https://docs.google.com/spreadsheets/d/X/edit', 'sheet_enabled' => '1', 'sheet_interval' => '60']);
        $this->artisan('urun:senkron')->assertSuccessful();
        $this->assertSame('2500.00', $this->p->refresh()->price);
        $this->assertSame(7, $this->p->stock_qty);
        $this->assertStringContainsString('1 ürün güncellendi', (string) setting('sheet_last_result'));

        // Süre dolmadan ikinci çalışma atlanır
        Http::fake(['docs.google.com/*' => Http::response("Stok Kodu,Fiyat\nYE-1,9999\n")]);
        $this->artisan('urun:senkron')->assertSuccessful();
        $this->assertSame('2500.00', $this->p->refresh()->price);
    }

    public function test_sheet_sync_reports_private_file(): void
    {
        Http::fake(['*' => Http::response('<html><body>Google Drive – oturum açın</body></html>')]);
        Setting::put(['sheet_url' => 'https://drive.google.com/file/d/X/view']);
        $r = app(SheetSync::class)->run(true);
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('Bağlantıya sahip herkes', $r['summary']);
    }

    public function test_city_shipping_fee_applies_at_checkout(): void
    {
        Setting::put(['shipping_zones' => json_encode([['city' => 'Girne', 'fee' => 0, 'free_min' => ''], ['city' => 'Güzelyurt', 'fee' => 250, 'free_min' => '10000']]), 'free_shipping_min' => '5000']);
        $this->postJson('/cart/add', ['product_id' => $this->p->id]);
        $this->get('/checkout')->assertSee('data-ship="250"', false)->assertDontSee('>Lefkoşa<', false);
        $this->post('/checkout', ['first_name' => 'A', 'last_name' => 'B', 'email' => 'a@b.co', 'phone' => '1', 'address' => 'x',
            'city' => 'Güzelyurt', 'payment_method' => 'havale', 'terms' => '1']);
        $o = Order::first();
        $this->assertSame('250.00', $o->shipping);
        $this->assertSame('1950.00', $o->total);
    }

    public function test_manual_order_from_panel(): void
    {
        $this->p->update(['stock_qty' => 3]);
        $this->actingAs($this->admin);
        Livewire::test(ListOrders::class)->callAction('manual', [
            'items' => [['product_id' => $this->p->id, 'quantity' => 2, 'price' => 1500]],
            'discount' => 100, 'shipping' => 0, 'first_name' => 'Mağaza', 'last_name' => 'Müşterisi', 'phone' => '05330000000',
            'payment_method' => 'magaza', 'paid' => true, 'status' => 'completed',
        ])->assertHasNoActionErrors();
        $o = Order::first();
        $this->assertSame('panel', $o->source);
        $this->assertSame('2900.00', $o->total);
        $this->assertSame('paid', $o->payment_status);
        $this->assertSame(1, $this->p->refresh()->stock_qty);
        $this->get('/admin/orders/'.$o->id)->assertOk()->assertSee('Mağazada ödendi');
    }

    public function test_reply_to_contact_message(): void
    {
        $m = ContactMessage::create(['name' => 'Zeynep', 'email' => 'z@example.com', 'message' => 'Fiyat?']);
        $this->actingAs($this->admin);
        Livewire::test(ManageContactMessages::class)->callTableAction('reply', $m, ['subject' => 'Yanıt', 'body' => 'Merhaba, 1900 ₺']);
        $m->refresh();
        $this->assertNotNull($m->replied_at);
        $this->assertSame('Merhaba, 1900 ₺', $m->reply);
    }

    public function test_new_admin_screens_render(): void
    {
        $this->actingAs($this->admin);
        foreach (['/admin/reports', '/admin/manage-appearance', '/admin/manage-sheet-sync', '/admin/redirects', '/admin/activity-logs',
            '/admin/tags', '/admin/manage-settings', '/admin/manage-appearance?tab=alt-bilgi'] as $url) {
            $this->get($url)->assertOk();
        }
        Livewire::test(ManageSheetSync::class)->set('data.sheet_url', 'https://docs.google.com/spreadsheets/d/A/edit')->set('data.sheet_enabled', true)
            ->call('save');
        $this->assertSame('1', setting('sheet_enabled'));
    }

    public function test_roles_limit_panel_areas(): void
    {
        $staff = User::factory()->create(['is_admin' => true, 'role' => 'orders']);
        $this->actingAs($staff);
        $this->get('/admin/orders')->assertOk();
        $this->get('/admin/products')->assertForbidden();
        $this->get('/admin/manage-settings')->assertForbidden();
        $this->get('/admin/coupons')->assertForbidden();

        $editor = User::factory()->create(['is_admin' => true, 'role' => 'catalog']);
        $this->actingAs($editor);
        $this->get('/admin/products')->assertOk();
        $this->get('/admin/orders')->assertForbidden();
    }

    public function test_activity_log_records_admin_changes(): void
    {
        $this->actingAs($this->admin);
        $this->p->update(['price' => 1800]);
        $log = ActivityLog::where('subject_type', Product::class)->latest('id')->first();
        $this->assertNotNull($log);
        $this->assertSame('updated', $log->action);
        $this->assertEquals(1800, (float) $log->changes['price']['yeni']);
        $this->assertEquals(1700, (float) $log->changes['price']['eski']);
        $this->assertSame($this->admin->id, $log->user_id);
    }
}
