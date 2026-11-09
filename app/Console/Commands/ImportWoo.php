<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Product;
use App\Models\Tag;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Eski lussohomeconcept.com (WooCommerce) mağazasından kategori, ürün ve
 * görselleri açık Store API üzerinden aktarır. Tekrar çalıştırılabilir:
 * kayıtlar wc_id ile eşleşir, inmiş görsel yeniden indirilmez.
 */
class ImportWoo extends Command
{
    protected $signature = 'lusso:aktar
        {--kaynak=https://lussohomeconcept.com : Eski sitenin adresi}
        {--baslangic=1 : İlk ürün sayfası (sayfa başına 50 ürün)}
        {--bitis=0 : Son ürün sayfası, 0 = sonuna kadar}
        {--kategorisiz : Kategorileri yeniden aktarma (paralel çalışmada)}
        {--gorselsiz : Görselleri indirme}
        {--bekle=5 : Sayfalar arası bekleme (sn)}
        {--liste : Görselleri indirmek yerine storage/app/gorsel-listesi.txt dosyasına yaz}';

    protected $description = 'Eski WooCommerce sitesinden kategori, ürün ve görselleri aktarır';

    private string $base;

    public function handle(): int
    {
        ini_set('memory_limit', '1024M');
        $this->base = rtrim($this->option('kaynak'), '/').'/wp-json/wc/store/v1';

        $catMap = $this->option('kategorisiz')
            ? Category::whereNotNull('wc_id')->pluck('id', 'wc_id')->all()
            : $this->importCategories();
        $this->importProducts($catMap);

        $this->info('Bitti: '.Category::count().' kategori, '.Product::count().' ürün.');

        return self::SUCCESS;
    }

    private function get(string $path, array $query = []): array
    {
        $res = Http::timeout(240)->connectTimeout(30)->retry(4, 5000, throw: true)
            ->withHeaders(['User-Agent' => 'LussoImporter/1.0'])
            ->get($this->base.$path, $query);

        return [$res->json() ?? [], (int) $res->header('X-WP-TotalPages')];
    }

    /** @return array<int,int> wc_id => yerel id */
    private function importCategories(): array
    {
        $all = [];
        $page = 1;
        do {
            [$rows, $pages] = $this->get('/products/categories', ['per_page' => 100, 'page' => $page]);
            $all = array_merge($all, $rows);
            $page++;
        } while ($page <= max(1, $pages));

        $map = [];
        foreach ($all as $i => $c) {
            $cat = Category::updateOrCreate(['wc_id' => $c['id']], [
                'name' => html_entity_decode($c['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'slug' => $this->uniqueSlug(Category::class, urldecode($c['slug']), $c['id']),
                'description' => $c['description'] ?: null,
                'sort' => $i,
            ]);
            if (! empty($c['image']['src']) && ! $cat->image && ! $this->option('gorselsiz')) {
                $path = $this->download([$c['image']['src']], 'kategoriler/'.$c['id'])[0] ?? null;
                if ($path) {
                    $cat->update(['image' => $path]);
                }
            }
            $map[$c['id']] = $cat->id;
        }
        foreach ($all as $c) {
            Category::where('id', $map[$c['id']])->update(['parent_id' => $c['parent'] ? ($map[$c['parent']] ?? null) : null]);
        }
        $this->info(count($all).' kategori aktarıldı.');

        return $map;
    }

    private function importProducts(array $catMap): void
    {
        $from = max(1, (int) $this->option('baslangic'));
        [$first, $pages] = $this->get('/products', ['per_page' => 50, 'page' => $from, 'orderby' => 'date', 'order' => 'desc']);
        $to = (int) $this->option('bitis') ?: $pages;
        $to = min($to, $pages);
        $bar = $this->output->createProgressBar(max(0, $to - $from + 1) * 50);
        // Sabit taban: paralel işlerde de eski sitedeki sıra (en yeni önce) korunur.
        $base = \Illuminate\Support\Carbon::parse('2026-11-09 00:00:00');

        for ($page = $from; $page <= $to; $page++) {
            $rows = $page === $from ? $first : $this->get('/products', ['per_page' => 50, 'page' => $page, 'orderby' => 'date', 'order' => 'desc'])[0];

            foreach (array_values($rows) as $i => $p) {
                $when = $base->copy()->subMinutes(($page - 1) * 50 + $i);
                try {
                    $this->importOne($p, $catMap, $when);
                } catch (\Throwable $e) {
                    // Paralel işlerde etiket benzersizlik yarışı olabilir: bir kez daha dene.
                    usleep(300000);
                    try {
                        $this->importOne($p, $catMap, $when);
                    } catch (\Throwable $e) {
                        $this->warn("Ürün {$p['id']} aktarılamadı: ".$e->getMessage());
                    }
                }
                $bar->advance();
            }
            gc_collect_cycles();
            sleep((int) $this->option('bekle')); // eski canlı siteyi yormamak için
        }
        $bar->finish();
        $this->newLine();
    }

    private function importOne(array $p, array $catMap, $publishedAt): void
    {
        $unit = 10 ** (int) ($p['prices']['currency_minor_unit'] ?? 0);
        $price = (float) ($p['prices']['regular_price'] ?? 0) / $unit;
        $sale = (float) ($p['prices']['sale_price'] ?? 0) / $unit;
        if ($p['type'] === 'variable' && ! empty($p['prices']['price_range'])) {
            $price = (float) $p['prices']['price_range']['min_amount'] / $unit;
            $sale = 0;
        }

        $product = DB::transaction(function () use ($p, $catMap, $publishedAt, $price, $sale) {
            $product = Product::updateOrCreate(['wc_id' => $p['id']], [
                'name' => html_entity_decode($p['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                'slug' => $this->uniqueSlug(Product::class, urldecode($p['slug']), $p['id']),
                'sku' => $p['sku'] ?: null,
                'short_description' => trim($p['short_description']) ?: null,
                'description' => trim($p['description']) ?: null,
                'price' => $price,
                'sale_price' => $sale > 0 && $sale < $price ? $sale : null,
                'in_stock' => (bool) $p['is_in_stock'],
                'published_at' => $publishedAt,
            ]);

            $product->categories()->sync(collect($p['categories'])->pluck('id')->map(fn ($id) => $catMap[$id] ?? null)->filter()->all());

            $tagIds = [];
            foreach ($p['tags'] as $t) {
                $tagIds[] = Tag::firstOrCreate(['slug' => urldecode($t['slug'])], ['name' => html_entity_decode($t['name'], ENT_QUOTES | ENT_HTML5, 'UTF-8')])->id;
            }
            $product->tags()->sync($tagIds);

            return $product;
        });

        // --liste: görsel kaydını hemen aç, dosyayı indirmek için listeye yaz
        // (eski sunucuda PHP indirmesi takılıyor; liste curl ile paralel indirilir: tools/gorsel-indir.sh).
        if ($this->option('liste') && $product->images()->count() === 0 && $p['images']) {
            foreach (array_values($p['images']) as $i => $img) {
                $url = $this->pickSize($img);
                $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'jpg';
                $rel = 'urunler/'.$p['id'].($i ? '-'.$i : '').'.'.$ext;
                $product->images()->create(['path' => $rel, 'source_url' => $url, 'sort' => $i]);
                if (! is_file(public_path('medya/'.$rel))) {
                    file_put_contents(storage_path('app/gorsel-listesi.txt'), $url."\t".$rel."\n", FILE_APPEND | LOCK_EX);
                }
            }

            return;
        }

        // İndirme işlem (transaction) dışında: uzun süren indirme tablo kilidi tutmasın.
        if (! $this->option('gorselsiz') && $product->images()->count() === 0 && $p['images']) {
            $urls = array_map(fn ($img) => $this->pickSize($img), $p['images']);
            foreach ($this->download($urls, 'urunler/'.$p['id']) as $i => $path) {
                if ($path) {
                    $product->images()->create(['path' => $path, 'source_url' => $p['images'][$i]['src'], 'sort' => $i]);
                }
            }
        }
    }

    /** 800px'i geçmeyen en büyük boy; yoksa asıl dosya. */
    private function pickSize(array $img): string
    {
        $best = null;
        $bestW = 0;
        foreach (explode(',', $img['srcset'] ?? '') as $part) {
            if (preg_match('/(\S+)\s+(\d+)w/', trim($part), $m) && (int) $m[2] <= 800 && (int) $m[2] > $bestW) {
                [$best, $bestW] = [$m[1], (int) $m[2]];
            }
        }

        return $best && $bestW >= 600 ? $best : $img['src'];
    }

    /** @return array<int,?string> medya yolları (sıra korunur) */
    private function download(array $urls, string $prefix): array
    {
        $dir = public_path('medya/'.dirname($prefix));
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $out = [];
        $todo = [];
        foreach (array_values($urls) as $i => $url) {
            $ext = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) ?: 'jpg';
            $rel = $prefix.($i ? '-'.$i : '').'.'.$ext;
            $out[$i] = $rel;
            if (! is_file(public_path('medya/'.$rel)) || filesize(public_path('medya/'.$rel)) === 0) {
                $todo[$i] = $url;
            }
        }
        // Sıralı indirme: eski sunucuda Http::pool her istekte 60 sn zaman aşımına takılıyordu
        // (tekli istek ~0,3 sn). Hız için komut sayfa aralıklarıyla paralel çalıştırılır.
        foreach ($todo as $i => $url) {
            try {
                // Eski sunucu bazı istekleri askıda bırakıyor: kısa zaman aşımı + yeniden deneme.
                $r = Http::timeout(12)->connectTimeout(8)->retry(4, 500, throw: false)
                    ->withHeaders(['User-Agent' => 'LussoImporter/1.0', 'Connection' => 'close'])->get($url);
                $ok = $r->successful() && strlen($r->body()) > 0;
            } catch (\Throwable) {
                $ok = false;
            }
            if ($ok) {
                file_put_contents(public_path('medya/'.$out[$i]), $r->body());
            } else {
                $this->warn("Görsel inmedi: $url");
                $out[$i] = null;
            }
        }

        return $out;
    }

    private function uniqueSlug(string $model, string $slug, int $wcId): string
    {
        $slug = Str::limit($slug ?: Str::slug((string) $wcId), 180, '');
        $clash = $model::where('slug', $slug)->where(fn ($q) => $q->whereNull('wc_id')->orWhere('wc_id', '!=', $wcId))->exists();

        return $clash ? $slug.'-'.$wcId : $slug;
    }
}
