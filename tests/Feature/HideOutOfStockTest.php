<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** "Stokta olmayanları gizle": listelerde yok, ürün sayfası açılır; ayar kapalıyken listelerde görünür. */
class HideOutOfStockTest extends TestCase
{
    use RefreshDatabase;

    private function product(string $name, bool $inStock): Product
    {
        return Product::create(['name' => $name, 'slug' => \Illuminate\Support\Str::slug($name), 'price' => 100,
            'in_stock' => $inStock, 'is_active' => true, 'published_at' => now()]);
    }

    public function test_out_of_stock_hidden_from_listings_by_default_but_page_opens(): void
    {
        $this->product('Stoktaki Vazo', true);
        $out = $this->product('Tukenen Vazo', false);

        $this->get('/arama?s=Vazo')->assertOk()->assertSee('Stoktaki Vazo')->assertDontSee('Tukenen Vazo');
        $this->get($out->url())->assertOk()->assertSee('Tukenen Vazo');
    }

    public function test_setting_off_shows_out_of_stock_in_listings(): void
    {
        Setting::put(['hide_out_of_stock' => '0']);
        $this->product('Tukenen Vazo', false);

        $this->get('/arama?s=Vazo')->assertOk()->assertSee('Tukenen Vazo');
    }
}
