<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\XmlFeed;
use Illuminate\Support\Str;
use XMLWriter;

/**
 * Ürün XML akışını üretir.
 *  - google: RSS 2.0 + g: alanları (Google Merchant Center, Meta katalog, TikTok, Pinterest okur)
 *  - genel : Türkçe etiketli düz ürün listesi (bayi / pazar yeri entegrasyonları)
 */
class FeedBuilder
{
    /** @var array<int, array{name:string,parent:?int}> */
    private array $cats = [];

    public function build(XmlFeed $feed): string
    {
        $this->cats = Category::get(['id', 'name', 'parent_id'])
            ->mapWithKeys(fn ($c) => [$c->id => ['name' => $c->name, 'parent' => $c->parent_id]])->all();

        $w = new XMLWriter;
        $w->openMemory();
        $w->setIndent(true);
        $w->startDocument('1.0', 'UTF-8');

        $feed->format === 'genel' ? $this->genel($w, $feed) : $this->google($w, $feed);

        $w->endDocument();

        return $w->outputMemory();
    }

    private function google(XMLWriter $w, XmlFeed $feed): void
    {
        $w->startElement('rss');
        $w->writeAttribute('version', '2.0');
        $w->writeAttribute('xmlns:g', 'http://base.google.com/ns/1.0');
        $w->startElement('channel');
        $w->writeElement('title', $feed->name);
        $w->writeElement('link', url('/'));
        $w->writeElement('description', 'Lusso Home Concept ürün akışı');

        $this->each($feed, function (Product $p, float $final) use ($w, $feed) {
            $images = $p->images->map(fn ($i) => $i->url())->values();
            $w->startElement('item');
            $w->writeElement('g:id', (string) $p->id);
            $w->writeElement('g:title', Str::limit($p->name, 150, ''));
            $w->writeElement('g:description', Str::limit($this->plain($p->description ?: $p->short_description) ?: $p->name, 5000, ''));
            $w->writeElement('g:link', $p->url());
            if ($images->isNotEmpty()) {
                $w->writeElement('g:image_link', $images->first());
                foreach ($images->slice(1, 10) as $img) {
                    $w->writeElement('g:additional_image_link', $img);
                }
            }
            $w->writeElement('g:availability', $p->in_stock ? 'in_stock' : 'out_of_stock');
            $w->writeElement('g:price', $this->money((float) $p->price).' TRY');
            if ($final < (float) $p->price) {
                $w->writeElement('g:sale_price', $this->money($final).' TRY');
            }
            $w->writeElement('g:condition', 'new');
            $w->writeElement('g:brand', $feed->brand ?: 'Lusso Home Concept');
            if ($p->sku) {
                $w->writeElement('g:mpn', $p->sku);
            }
            $w->writeElement('g:identifier_exists', 'no');
            if ($type = $this->trail($p)) {
                $w->writeElement('g:product_type', $type);
            }
            if ($feed->google_category) {
                $w->writeElement('g:google_product_category', $feed->google_category);
            }
            $w->endElement();
        });

        $w->endElement(); // channel
        $w->endElement(); // rss
    }

    private function genel(XMLWriter $w, XmlFeed $feed): void
    {
        $w->startElement('urunler');
        $w->writeAttribute('magaza', 'Lusso Home Concept');
        $w->writeAttribute('olusturma', now()->toIso8601String());

        $this->each($feed, function (Product $p, float $final) use ($w) {
            $w->startElement('urun');
            $w->writeElement('id', (string) $p->id);
            $w->writeElement('stok_kodu', (string) $p->sku);
            $w->writeElement('ad', $p->name);
            $w->startElement('aciklama');
            $w->writeCdata((string) ($p->description ?: $p->short_description));
            $w->endElement();
            $w->writeElement('kategori', $this->trail($p));
            $w->startElement('kategoriler');
            foreach ($p->categories as $c) {
                $w->startElement('kategori');
                $w->writeAttribute('id', (string) $c->id);
                $w->text($c->name);
                $w->endElement();
            }
            $w->endElement();
            $w->writeElement('fiyat', $this->money((float) $p->price));
            $w->writeElement('satis_fiyati', $this->money($final));
            $w->writeElement('para_birimi', 'TRY');
            $w->writeElement('stok_durumu', $p->in_stock ? 'var' : 'yok');
            $w->writeElement('stok_adedi', $p->stock_qty === null ? '' : (string) $p->stock_qty);
            $w->writeElement('url', $p->url());
            $w->startElement('resimler');
            foreach ($p->images as $img) {
                $w->writeElement('resim', $img->url());
            }
            $w->endElement();
            $w->writeElement('guncelleme', $p->updated_at?->toIso8601String() ?? '');
            $w->endElement();
        });

        $w->endElement();
    }

    /** Akıştaki ürünleri parça parça dolaşır; fiyatı 0 olanları (istenmişse) atlar. */
    private function each(XmlFeed $feed, callable $write): void
    {
        $feed->productQuery()
            ->with(['images' => fn ($q) => $q->orderBy('sort'), 'categories:id,name,parent_id'])
            ->orderBy('id')
            ->chunkById(200, function ($products) use ($feed, $write) {
                foreach ($products as $p) {
                    $final = $p->finalPrice();
                    if ($feed->priced_only && $final <= 0) {
                        continue;
                    }
                    $write($p, $final);
                }
            });
    }

    /** Ürünün en derin kategorisinin kökten zinciri: "Sofra Grubu > Kupa". */
    private function trail(Product $p): string
    {
        $best = [];
        foreach ($p->categories as $c) {
            $chain = [];
            $id = $c->id;
            $guard = 0;
            while ($id && isset($this->cats[$id]) && $guard++ < 10) {
                array_unshift($chain, $this->cats[$id]['name']);
                $id = $this->cats[$id]['parent'];
            }
            if (count($chain) > count($best)) {
                $best = $chain;
            }
        }

        return implode(' > ', $best);
    }

    private function plain(?string $html): string
    {
        $text = html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>'], "\n", (string) $html)), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace("/[ \t]+/u", ' ', preg_replace("/\n{3,}/u", "\n\n", $text)));
    }

    private function money(float $v): string
    {
        return number_format($v, 2, '.', '');
    }
}
