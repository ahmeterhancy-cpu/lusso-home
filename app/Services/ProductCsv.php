<?php

namespace App\Services;

use App\Models\Product;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Ürün listesini CSV olarak verir; CSV veya Excel (.xlsx) dosyasından fiyat, stok
 * ve yayın durumunu günceller. Ürün oluşturmaz/silmez (yanlış dosya kataloğu bozmasın).
 * Hem panelden elle yükleme hem Google Drive senkronu (SheetSync) bunu kullanır.
 */
class ProductCsv
{
    public const COLUMNS = ['id', 'stok_kodu', 'ad', 'fiyat', 'indirimli_fiyat', 'stok_adedi', 'stokta', 'yayinda', 'one_cikan', 'kategoriler', 'adres'];

    /** Excel'de farklı yazılmış başlıklar → iç sütun adı. */
    private const ALIASES = [
        'id' => ['id', 'urun_id'],
        'stok_kodu' => ['stok_kodu', 'stokkodu', 'sku', 'kod', 'urun_kodu', 'barkod', 'model', 'model_kodu'],
        'fiyat' => ['fiyat', 'satis_fiyati', 'fiyat_tl', 'fiyati', 'price', 'liste_fiyati', 'tutar'],
        'indirimli_fiyat' => ['indirimli_fiyat', 'indirimli', 'kampanyali_fiyat', 'sale_price', 'indirim_fiyati'],
        'stok_adedi' => ['stok_adedi', 'stok', 'adet', 'miktar', 'stock', 'stok_miktari', 'mevcut'],
        'stokta' => ['stokta', 'stok_durumu', 'in_stock'],
        'yayinda' => ['yayinda', 'aktif', 'yayin'],
        'one_cikan' => ['one_cikan', 'onecikan'],
    ];

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // Excel UTF-8
            fputcsv($out, self::COLUMNS, ';');
            Product::with('categories:id,name')->orderBy('id')->chunk(500, function ($chunk) use ($out) {
                foreach ($chunk as $p) {
                    fputcsv($out, [
                        $p->id, $p->sku, $p->name,
                        $this->num($p->price), $p->sale_price !== null ? $this->num($p->sale_price) : '',
                        $p->stock_qty ?? '', $p->in_stock ? 1 : 0, $p->is_active ? 1 : 0, $p->is_featured ? 1 : 0,
                        $p->categories->pluck('name')->join(', '), route('product', $p->slug),
                    ], ';');
                }
            });
            fclose($out);
        }, 'urunler-'.now()->format('Ymd-Hi').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function num($v): string
    {
        return str_replace('.', ',', rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.'));
    }

    private function parseNum(mixed $v): ?float
    {
        if (is_int($v) || is_float($v)) {
            return (float) $v; // Excel hücresi zaten sayı
        }
        $v = trim((string) $v);
        if ($v === '') {
            return null;
        }
        $v = str_replace([' ', '₺', 'TL', 'tl'], '', $v);
        // 1.250,50 → 1250.50 ; 1250,5 → 1250.5 ; 1250.5 → 1250.5
        if (str_contains($v, ',')) {
            $v = str_replace('.', '', $v);
            $v = str_replace(',', '.', $v);
        }

        return is_numeric($v) ? (float) $v : null;
    }

    private function parseBool(mixed $v): ?bool
    {
        if (is_bool($v)) {
            return $v;
        }
        $v = mb_strtolower(trim((string) $v), 'UTF-8');

        return match (true) {
            in_array($v, ['1', 'evet', 'e', 'true', 'var', 'x', 'doğru'], true) => true,
            in_array($v, ['0', 'hayır', 'hayir', 'h', 'false', 'yok', 'yanlış'], true) => false,
            default => null,
        };
    }

    /** "Stok Kodu" → "stok_kodu", "Satış Fiyatı (TL)" → "satis_fiyati_tl" */
    public static function headerKey(mixed $h): string
    {
        $h = mb_strtolower(trim((string) $h), 'UTF-8');
        $h = strtr($h, ['ı' => 'i', 'i̇' => 'i', 'ş' => 's', 'ğ' => 'g', 'ü' => 'u', 'ö' => 'o', 'ç' => 'c', 'â' => 'a']);
        $h = preg_replace('/[^a-z0-9]+/', '_', $h);

        return trim((string) $h, '_');
    }

    /**
     * Dosyayı okur (.csv veya .xlsx; uzantı değilse içeriğe bakar).
     *
     * @return array{0: array<int, string>, 1: iterable<int, array<int, mixed>>}
     */
    public function read(string $path): array
    {
        // .xlsx bir zip dosyasıdır: ilk iki bayt "PK". Drive'dan inen dosyanın uzantısı olmayabilir.
        $fh = @fopen($path, 'r');
        $magic = $fh ? (string) fread($fh, 2) : '';
        if ($fh) {
            fclose($fh);
        }
        $isXlsx = $magic === 'PK';

        if ($isXlsx) {
            $reader = new XlsxReader;
            $reader->open($path);
            $rows = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = $row->toArray();
                }
                break; // yalnız ilk sayfa
            }
            $reader->close();
            $head = array_shift($rows) ?? [];

            return [$head, $rows];
        }

        $content = (string) file_get_contents($path);
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        if (! mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1254'); // Türkçe Excel CSV'si
        }
        $lines = preg_split('/\r\n|\n|\r/', $content);
        $first = (string) array_shift($lines);
        $delim = substr_count($first, ';') >= substr_count($first, ',') ? ';' : (substr_count($first, "\t") > substr_count($first, ',') ? "\t" : ',');
        $rows = array_map(fn ($l) => str_getcsv($l, $delim), array_filter($lines, fn ($l) => trim($l) !== ''));

        return [str_getcsv($first, $delim), $rows];
    }

    /**
     * @param  array<int>  $skipCategoryIds  bu kategorilerdeki ürünlere dokunma (Drive senkronu)
     * @return array{updated: int, unchanged: int, skipped: int, notFound: array<int, string>, errors: array<int, string>}
     */
    public function import(string $path, array $skipCategoryIds = []): array
    {
        [$head, $rows] = $this->read($path);

        return $this->apply($head, $rows, $skipCategoryIds);
    }

    /** @return array{updated: int, unchanged: int, skipped: int, notFound: array<int, string>, errors: array<int, string>} */
    public function apply(array $head, iterable $rows, array $skipCategoryIds = []): array
    {
        $idx = [];
        foreach ($head as $i => $h) {
            $key = self::headerKey($h);
            foreach (self::ALIASES as $col => $aliases) {
                if (! isset($idx[$col]) && in_array($key, $aliases, true)) {
                    $idx[$col] = $i;
                }
            }
        }
        if (! isset($idx['id']) && ! isset($idx['stok_kodu'])) {
            return ['updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'notFound' => [], 'errors' => ['Dosyada "Stok Kodu" (veya id) sütunu bulunamadı. İlk satır sütun başlıkları olmalı: Stok Kodu, Fiyat, Stok …']];
        }

        $res = ['updated' => 0, 'unchanged' => 0, 'skipped' => 0, 'notFound' => [], 'errors' => []];
        $line = 1;
        foreach ($rows as $row) {
            $line++;
            $row = array_values((array) $row);
            if (count(array_filter($row, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }
            $get = fn (string $col) => isset($idx[$col]) ? ($row[$idx[$col]] ?? null) : null;
            $p = null;
            if (($id = trim((string) $get('id'))) !== '') {
                $p = Product::find((int) $id);
            }
            if (! $p && ($sku = trim((string) $get('stok_kodu'))) !== '') {
                $p = Product::where('sku', $sku)->first();
            }
            if (! $p) {
                $res['notFound'][] = "Satır $line: ".(trim((string) $get('stok_kodu')) ?: trim((string) $get('id')) ?: '?');

                continue;
            }
            if ($skipCategoryIds && array_intersect($skipCategoryIds, $p->categoryIds())) {
                $res['skipped']++; // kategorisi Drive güncellemesine kapalı

                continue;
            }

            $changes = [];
            if (isset($idx['fiyat']) && ($v = $this->parseNum($get('fiyat'))) !== null) {
                $changes['price'] = max(0, $v);
            }
            if (isset($idx['indirimli_fiyat'])) {
                $v = $this->parseNum($get('indirimli_fiyat'));
                $changes['sale_price'] = $v && $v > 0 ? $v : null;
            }
            if (isset($idx['stok_adedi'])) {
                $raw = $get('stok_adedi');
                if (trim((string) $raw) === '') {
                    $changes['stock_qty'] = null;
                } else {
                    $changes['stock_qty'] = max(0, (int) $this->parseNum($raw));
                    // Adet sütunu varsa stok durumu ondan türer (ayrı "stokta" sütunu yoksa).
                    if (! isset($idx['stokta'])) {
                        $changes['in_stock'] = $changes['stock_qty'] > 0;
                    }
                }
            }
            foreach (['stokta' => 'in_stock', 'yayinda' => 'is_active', 'one_cikan' => 'is_featured'] as $col => $field) {
                if (isset($idx[$col]) && ($b = $this->parseBool($get($col))) !== null) {
                    $changes[$field] = $b;
                }
            }
            $price = $changes['price'] ?? (float) $p->price;
            if (! empty($changes['sale_price']) && $changes['sale_price'] >= $price) {
                $res['errors'][] = "Satır $line ({$p->name}): indirimli fiyat normal fiyattan düşük olmalı, indirim yok sayıldı.";
                $changes['sale_price'] = null;
            }

            $p->fill($changes);
            if ($p->isDirty()) {
                $p->save();
                $res['updated']++;
            } else {
                $res['unchanged']++;
            }
        }

        return $res;
    }
}
