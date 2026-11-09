<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;
use Tests\TestCase;

class ImportWooOrdersTest extends TestCase
{
    use RefreshDatabase;

    private string $dir = 'woo-test';

    protected function setUp(): void
    {
        parent::setUp();
        $path = storage_path('app/'.$this->dir);
        File::ensureDirectoryExists($path);

        Product::create(['name' => 'Flamingo Kupa', 'slug' => 'flamingo-kupa', 'price' => 500, 'in_stock' => true, 'is_active' => true, 'wc_id' => 900]);

        File::put($path.'/customers.json', json_encode([
            ['id' => 11, 'em' => 'Ayse@Example.com', 'fn' => 'Ayşe', 'ln' => 'Kaya', 'role' => 'customer', 'c' => '2022-03-01T10:00:00', 'ph' => '0533 111 22 33', 'a1' => 'Kordon', 'city' => 'Girne'],
            ['id' => 1, 'em' => 'patron@example.com', 'fn' => 'Patron', 'ln' => '', 'role' => 'administrator', 'c' => '2021-01-01T10:00:00'],
        ]));
        File::put($path.'/orders.json', json_encode([
            ['id' => 501, 'no' => '10847', 'st' => 'completed', 'c' => '2022-03-02T09:00:00', 'm' => '2022-03-03T09:00:00', 'paid' => '2022-03-02T09:05:00',
                'cid' => 11, 'pm' => 'payment_paynet', 'tx' => 'PN-1', 'b' => ['fn' => 'Ayşe', 'ln' => 'Kaya', 'a1' => 'Kordon', 'city' => 'Girne', 'em' => 'ayse@example.com', 'ph' => '0533'],
                'tot' => '1100.00', 'ship' => '100.00', 'disc' => '0.00',
                'li' => [[900, 'Flamingo Kupa', '', 2, '1000.00', '1000.00']]],
            ['id' => 502, 'no' => '10900', 'st' => 'cancelled', 'c' => '2023-05-05T09:00:00', 'cid' => 0, 'pm' => 'cod',
                'b' => ['fn' => 'Misafir', 'ln' => 'Alıcı', 'a1' => 'Sahil', 'city' => 'İskele', 'em' => 'misafir@example.com'],
                'tot' => '300.00', 'ship' => '0.00', 'disc' => '0.00', 'fees' => [['Paketleme', '50.00']],
                'li' => [[777, 'Silinmiş Ürün &amp; Tabak', 'X1', 1, '250.00', '250.00']]],
        ]));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(storage_path('app/'.$this->dir));
        parent::tearDown();
    }

    public function test_imports_customers_and_orders_idempotently(): void
    {
        $this->artisan('lusso:aktar-siparis', ['--klasor' => $this->dir])->assertSuccessful();
        $this->artisan('lusso:aktar-siparis', ['--klasor' => $this->dir])->assertSuccessful(); // ikinci kez: çift kayıt yok

        $this->assertSame(1, User::count()); // personel hesabı atlandı
        $ayse = User::firstWhere('wc_id', 11);
        $this->assertSame('ayse@example.com', $ayse->email);
        $this->assertFalse((bool) $ayse->is_admin);

        $this->assertSame(2, Order::count());
        $paid = Order::firstWhere('number', '10847');
        $this->assertSame($ayse->id, $paid->user_id);
        $this->assertSame('completed', $paid->status);
        $this->assertSame('paid', $paid->payment_status);
        $this->assertSame('paynet', $paid->payment_method);
        $this->assertSame('eski', $paid->source);
        $this->assertSame(1, $paid->items()->count());
        $this->assertNotNull($paid->items()->first()->product_id);
        $this->assertSame('2022-03-02', $paid->created_at->toDateString());

        $guest = Order::firstWhere('number', '10900');
        $this->assertNull($guest->user_id);
        $this->assertSame('unpaid', $guest->payment_status);
        $this->assertSame('kapida', $guest->payment_method);
        $this->assertSame('Silinmiş Ürün & Tabak', $guest->items()->first()->name);
        $this->assertNull($guest->items()->first()->product_id);
        $this->assertStringContainsString('Paketleme', $guest->admin_note);
    }

    public function test_imported_orders_show_in_panel_with_legacy_method(): void
    {
        $this->artisan('lusso:aktar-siparis', ['--klasor' => $this->dir])->assertSuccessful();
        $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => 'admin']));

        Livewire::test(ListOrders::class)
            ->assertCanSeeTableRecords(Order::all())
            ->assertSee('Kredi Kartı (Paynet, eski site)');
    }
}
