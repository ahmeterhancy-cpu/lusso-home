<?php

namespace Tests\Feature;

use App\Filament\Resources\XmlFeeds\Pages\ManageXmlFeeds;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\XmlFeed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class XmlFeedTest extends TestCase
{
    use RefreshDatabase;

    private Category $sofra;

    private Category $kupa;

    private Category $tekstil;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sofra = Category::create(['name' => 'Sofra Grubu', 'slug' => 'sofra-grubu', 'is_active' => true]);
        $this->kupa = Category::create(['name' => 'Kupa', 'slug' => 'kupa', 'parent_id' => $this->sofra->id, 'is_active' => true]);
        $this->tekstil = Category::create(['name' => 'Tekstil', 'slug' => 'tekstil', 'is_active' => true]);

        $this->product('Flamingo Kupa', 500, $this->kupa, ['sale_price' => 400, 'sku' => 'FK-1']);
        $this->product('Keten Örtü', 900, $this->tekstil);
        $this->product('Fiyatsız Kupa', 0, $this->kupa);
        $this->product('Tükenen Kupa', 300, $this->kupa, ['in_stock' => false]);
    }

    private function product(string $name, float $price, Category $cat, array $extra = []): Product
    {
        $p = Product::create($extra + ['name' => $name, 'slug' => \Illuminate\Support\Str::slug($name), 'price' => $price, 'in_stock' => true, 'is_active' => true,
            'description' => '<p>'.$name.' &amp; açıklama</p>']);
        $p->categories()->attach($cat->id);

        return $p;
    }

    public function test_google_feed_respects_categories_children_and_price(): void
    {
        XmlFeed::create(['name' => 'Google', 'slug' => 'google', 'format' => 'google', 'category_ids' => [$this->sofra->id], 'include_children' => true, 'priced_only' => true]);

        $res = $this->get('/xml/google.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string($res->getContent());
        $this->assertNotFalse($xml);
        $items = $xml->channel->item;
        $titles = [];
        foreach ($items as $it) {
            $titles[] = (string) $it->children('g', true)->title;
        }
        sort($titles);
        $this->assertSame(['Flamingo Kupa', 'Tükenen Kupa'], $titles); // tekstil ve fiyatsız yok

        $body = $res->getContent();
        $this->assertStringContainsString('<g:price>500.00 TRY</g:price>', $body);
        $this->assertStringContainsString('<g:sale_price>400.00 TRY</g:sale_price>', $body);
        $this->assertStringContainsString('<g:availability>out_of_stock</g:availability>', $body);
        $this->assertStringContainsString('<g:product_type>Sofra Grubu &gt; Kupa</g:product_type>', $body);
        $this->assertStringContainsString('Flamingo Kupa &amp; açıklama', $body); // HTML'siz açıklama
    }

    public function test_without_children_and_in_stock_only(): void
    {
        XmlFeed::create(['name' => 'Dar', 'slug' => 'dar', 'format' => 'google', 'category_ids' => [$this->sofra->id], 'include_children' => false]);
        XmlFeed::create(['name' => 'Stok', 'slug' => 'stok', 'format' => 'google', 'category_ids' => [], 'in_stock_only' => true]);

        $this->assertStringNotContainsString('<item>', $this->get('/xml/dar.xml')->getContent()); // ürünler alt kategoride
        $stok = $this->get('/xml/stok.xml')->getContent();
        $this->assertStringContainsString('Keten Örtü', $stok);
        $this->assertStringNotContainsString('Tükenen Kupa', $stok);
    }

    public function test_generic_feed_is_valid_xml(): void
    {
        XmlFeed::create(['name' => 'Bayi', 'slug' => 'bayi', 'format' => 'genel', 'category_ids' => [$this->tekstil->id]]);

        $xml = simplexml_load_string($this->get('/xml/bayi.xml')->assertOk()->getContent());
        $this->assertNotFalse($xml);
        $this->assertCount(1, $xml->urun);
        $this->assertSame('Keten Örtü', (string) $xml->urun->ad);
        $this->assertSame('900.00', (string) $xml->urun->satis_fiyati);
        $this->assertSame('var', (string) $xml->urun->stok_durumu);
        $this->assertStringContainsString('<p>', (string) $xml->urun->aciklama); // CDATA içinde HTML korunur
    }

    public function test_token_and_inactive_feeds_are_protected(): void
    {
        XmlFeed::create(['name' => 'Gizli', 'slug' => 'gizli', 'token' => 'abc123']);
        XmlFeed::create(['name' => 'Kapalı', 'slug' => 'kapali', 'is_active' => false]);

        $this->get('/xml/gizli.xml')->assertForbidden();
        $this->get('/xml/gizli.xml?key=yanlis')->assertForbidden();
        $this->get('/xml/gizli.xml?key=abc123')->assertOk();
        $this->get('/xml/kapali.xml')->assertNotFound();
    }

    public function test_admin_can_create_feed_with_categories(): void
    {
        $this->actingAs(User::factory()->create(['is_admin' => true, 'role' => 'admin']));

        Livewire::test(ManageXmlFeeds::class)
            ->callAction('create', ['name' => 'Meta Katalog', 'slug' => 'meta-katalog', 'format' => 'google',
                'category_ids' => [$this->tekstil->id], 'include_children' => true, 'in_stock_only' => false, 'priced_only' => true, 'is_active' => true])
            ->assertHasNoActionErrors();

        $feed = XmlFeed::firstWhere('slug', 'meta-katalog');
        $this->assertSame([$this->tekstil->id], array_map('intval', $feed->category_ids));
        $this->assertStringContainsString('Keten Örtü', $this->get('/xml/meta-katalog.xml')->getContent());
    }
}
